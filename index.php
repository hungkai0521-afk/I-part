<?php
// index.php
session_start();
date_default_timezone_set('Asia/Taipei'); 
require_once 'functions.php';

$route = $_GET['route'] ?? 'dashboard';

// Auth Check
if (!isset($_SESSION['user_id']) && $route !== 'login') {
    header('Location: index.php?route=login');
    exit;
}

switch ($route) {
    // --- 1. 登入 ---
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $_POST['username'] ?? '';
            $pwd = $_POST['password'] ?? '';
            if (in_array($user, DEPARTMENTS) && $pwd === $user) {
                $_SESSION['user_id'] = $user;
                header('Location: index.php?route=ops');
                exit;
            } else {
                $error = "帳號或密碼錯誤";
            }
        }
        require 'views/login.php';
        break;

    // --- 2. 登出 ---
    case 'logout':
        session_destroy();
        header('Location: index.php?route=login');
        break;

    // --- 3. Dashboard (看板) ---
    case 'dashboard':
        $target_dept = $_GET['dept'] ?? 'ALL';
        $trend_daily = get_trend_data($target_dept, 'daily');
        $trend_weekly = get_trend_data($target_dept, 'weekly');
        $trend_monthly = get_trend_data($target_dept, 'monthly');

        $dept_stats = [];
        $today_str = date('Y-m-d');
        $scan_depts = ($target_dept !== 'ALL') ? [$target_dept] : DEPARTMENTS;

        foreach ($scan_depts as $d) {
            $db = get_db($d);
            $stmtOn = $db->prepare("SELECT COUNT(*) FROM part_lifecycle WHERE status='ON' AND date(created_at, 'localtime')=?");
            $stmtOn->execute([$today_str]);
            $on = $stmtOn->fetchColumn();

            $stmtLog = $db->prepare("SELECT COUNT(*) FROM part_lifecycle WHERE status='ON' AND ipart_logged=1 AND date(created_at, 'localtime')=?");
            $stmtLog->execute([$today_str]);
            $logged = $stmtLog->fetchColumn();

            $rate = ($on > 0) ? round(($logged / $on * 100), 1) : -1;
            $dept_stats[] = ['name' => $d, 'on' => $on, 'logged' => $logged, 'rate' => $rate];
        }
        require 'views/dashboard.php';
        break;

    // --- 4. 作業中心 (列表 + 日期篩選) ---
    case 'ops':
        $curr_dept = $_SESSION['user_id'];
        $default_start = date('Y-m-d', strtotime('-7 days'));
        $default_end   = date('Y-m-d');
        $start_date = $_GET['start_date'] ?? $default_start;
        $end_date   = $_GET['end_date'] ?? $default_end;

        $logs = get_logs_by_date($curr_dept, $start_date, $end_date);
        $inventory_items = get_current_inventory($curr_dept);
        $inv_count = count($inventory_items);
        require 'views/ops_center.php';
        break;

    // --- 5. CSV 匯出 (流水帳) ---
    case 'ops_export_csv':
        if (ob_get_level()) ob_end_clean();
        $curr_dept = $_SESSION['user_id'];
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $end_date   = $_GET['end_date'] ?? date('Y-m-d');
        
        $data = get_csv_lifecycle_data($curr_dept, $start_date, $end_date);
        
        $filename = "Logbook_{$curr_dept}_{$start_date}_to_{$end_date}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['PARTNO', 'Name', 'S/N', 'Tool ID', 'IN Date', 'ON Date', 'OUT Date'], ",", "\"", "\\");
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['part_no'], $row['name'], $row['sn'], $row['tool_id'],
                $row['in_date'], $row['on_date'], $row['out_date']
            ], ",", "\"", "\\");
        }
        fclose($output);
        exit;
        break;

    // --- 6. 資料庫編輯 (單筆) ---
    case 'ops_edit':
        $curr_dept = $_SESSION['user_id'];
        $id = $_GET['id'] ?? null;
        if (!$id) { header('Location: index.php?route=ops'); exit; }
        $db = get_db($curr_dept);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "UPDATE part_lifecycle SET 
                    status = ?, part_no = ?, part_name = ?, vendor = ?, sn = ?, 
                    location = ?, remark = ?, ipart_logged = ? 
                    WHERE id = ? AND dept = ?";
            $stmt = $db->prepare($sql);
            $ipart_val = isset($_POST['ipart_logged']) ? 1 : 0;
            $stmt->execute([
                $_POST['status'], $_POST['part_no'], $_POST['part_name'], $_POST['vendor'], $_POST['sn'],
                $_POST['location'], $_POST['remark'], $ipart_val, $id, $curr_dept
            ]);
            header('Location: index.php?route=ops');
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM part_lifecycle WHERE id = ? AND dept = ?");
        $stmt->execute([$id, $curr_dept]);
        $record = $stmt->fetch();
        if (!$record) { die("無權限或查無資料"); }
        $h_locs = get_location_master(); 
        require 'views/ops_edit.php';
        break;

    // --- 7. 資料庫刪除 (單筆) ---
    case 'ops_delete':
        $curr_dept = $_SESSION['user_id'];
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = get_db($curr_dept);
            $stmt = $db->prepare("DELETE FROM part_lifecycle WHERE id = ? AND dept = ?");
            $stmt->execute([$id, $curr_dept]);
        }
        header('Location: index.php?route=admin');
        exit;
        break;

    // --- 8. 庫存明細 ---
    case 'inventory':
        $curr_dept = $_SESSION['user_id'];
        $items = get_current_inventory($curr_dept);
        require 'views/inventory.php';
        break;

    // --- 9. 批次退料 ---
    case 'ops_batch_out':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['out_ids'])) {
            $ids = $_POST['out_ids']; 
            $remark = $_POST['batch_remark'] ?? 'Batch Return';
            $findStmt = $db->prepare("SELECT * FROM part_lifecycle WHERE id = ?");
            $insertStmt = $db->prepare("INSERT INTO part_lifecycle (dept, status, part_no, part_name, vendor, sn, location, ipart_logged, remark) VALUES (?, 'OUT', ?, ?, ?, ?, ?, 0, ?)");
            foreach ($ids as $id) {
                $findStmt->execute([$id]);
                $origin = $findStmt->fetch();
                if ($origin) {
                    $insertStmt->execute([$curr_dept, $origin['part_no'], $origin['part_name'], $origin['vendor'], $origin['sn'], $origin['location'], $remark]);
                }
            }
        }
        header('Location: index.php?route=ops');
        exit;
        break;

    // --- 10. 退料歷史 ---
    case 'return_history':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle WHERE status='OUT' ORDER BY created_at DESC LIMIT 100");
        $returns = $stmt->fetchAll();
        require 'views/return_history.php';
        break;

    // --- 11. 新增作業 ---
    case 'ops_new':
        $curr_dept = $_SESSION['user_id'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['status'];
            $db = get_db($curr_dept);
            $location_val = ($status === 'ON') ? ($_POST['tool_id'] ?? '') : ($_POST['location'] ?? '');
            $ipart_logged = isset($_POST['ipart_logged']) ? 1 : 0;
            if ($status === 'IN' && !empty($location_val)) { add_location_master($location_val); }
            $stmt = $db->prepare("INSERT INTO part_lifecycle (dept, status, part_no, part_name, vendor, sn, location, ipart_logged, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$curr_dept, $status, $_POST['part_no'], $_POST['part_name'], $_POST['vendor'], $_POST['sn'], $location_val, $ipart_logged, $_POST['remark']]);
            sync_part_master($_POST['part_no'], $_POST['part_name'], $_POST['vendor']);
            header('Location: index.php?route=ops');
            exit;
        }
        $status = $_GET['status'] ?? 'IN';
        $db = get_db($curr_dept);
        $tool_master = get_tool_master();
        $location_master = get_location_master();
        $inventory_list = []; $master_list = []; $mounted_list = []; 
        if ($status === 'ON') { $inventory_list = get_current_inventory($curr_dept); } 
        elseif ($status === 'OUT') { $mounted_list = get_mounted_parts($curr_dept); } 
        else { $master_list = get_part_master(); }
        $prefill = ['part_no' => $_GET['part_no']??'', 'part_name' => $_GET['part_name']??'', 'vendor' => $_GET['vendor']??'', 'sn' => $_GET['sn']??''];
        require 'views/ops_form.php';
        break;

    // --- 12. 管理員頁面 ---
    case 'admin':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master(); 
        $location_list = get_location_master();
        require 'views/admin.php';
        break;

    // --- 13. Admin: 匯出 Part Master ---
    case 'admin_export_master':
        if (ob_get_level()) ob_end_clean();
        $db_global = get_global_db();
        $stmt = $db_global->query("SELECT * FROM part_master ORDER BY part_no ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $filename = "Part_Master_List_" . date('Ymd') . ".csv";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['PartNo', 'Name', 'Vendor'], ",", "\"", "\\");
        foreach ($rows as $row) { fputcsv($output, [$row['part_no'], $row['name'], $row['vendor']], ",", "\"", "\\"); }
        fclose($output);
        exit;
        break;

    // --- 14. Admin: Part Master 匯入 (全量同步) ---
    case 'admin_import':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $csv_map = []; 
                $row = 0;
                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $row++; if ($row == 1) continue;
                    $p = trim($data[0] ?? ''); $n = trim($data[1] ?? ''); $v = trim($data[2] ?? '');
                    if ($p) $csv_map[$p] = ['name' => $n, 'vendor' => $v];
                }
                fclose($handle);

                $db_list = get_part_master(); 
                $db_map = [];
                foreach ($db_list as $item) {
                    $db_map[$item['part_no']] = ['name' => $item['name'], 'vendor' => $item['vendor']];
                }

                $to_add = []; $to_remove = []; $conflicts = [];

                // 找新增與衝突
                foreach ($csv_map as $p_no => $csv_info) {
                    if (!isset($db_map[$p_no])) {
                        $to_add[] = ['part_no' => $p_no, 'name' => $csv_info['name'], 'vendor' => $csv_info['vendor']];
                    } else {
                        $db_info = $db_map[$p_no];
                        if ($db_info['name'] !== $csv_info['name'] || $db_info['vendor'] !== $csv_info['vendor']) {
                            $conflicts[] = ['part_no' => $p_no, 'db' => $db_info, 'csv' => $csv_info];
                        }
                    }
                }
                // 找刪除
                foreach ($db_map as $p_no => $db_info) {
                    if (!isset($csv_map[$p_no])) {
                        $to_remove[] = ['part_no' => $p_no, 'name' => $db_info['name'], 'vendor' => $db_info['vendor']];
                    }
                }

                $_SESSION['sync_type'] = 'part_master';
                $_SESSION['sync_add'] = $to_add;
                $_SESSION['sync_remove'] = $to_remove;
                $_SESSION['sync_conflict'] = $conflicts;
                $show_sync_ui = true;
            }
        }
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master(); 
        $location_list = get_location_master();
        require 'views/admin.php';
        break;

    // --- 15. Admin: Tool 匯入 (全量同步) ---
    case 'tool_import':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $csv_data = [];
                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $row_val = trim($data[0] ?? '');
                    if ($row_val) $csv_data[] = $row_val;
                }
                fclose($handle);
                if (!empty($csv_data) && (strtoupper($csv_data[0]) == 'TOOL ID' || strtoupper($csv_data[0]) == 'NAME')) {
                    array_shift($csv_data);
                }
                $csv_data = array_unique($csv_data);
                $db_data = get_tool_master(); 

                $to_add = array_diff($csv_data, $db_data);
                $to_remove = array_diff($db_data, $csv_data);

                $_SESSION['sync_type'] = 'tool';
                $_SESSION['sync_add'] = $to_add;
                $_SESSION['sync_remove'] = $to_remove;
                $_SESSION['sync_conflict'] = [];
                $show_sync_ui = true;
            }
        }
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master(); 
        $location_list = get_location_master();
        require 'views/admin.php';
        break;

    // --- 16. Admin: Location 匯入 (全量同步) ---
    case 'location_import':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $csv_data = [];
                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $row_val = trim($data[0] ?? '');
                    if ($row_val) $csv_data[] = $row_val;
                }
                fclose($handle);
                if (!empty($csv_data) && (strtoupper($csv_data[0]) == 'LOCATION' || strtoupper($csv_data[0]) == 'NAME')) {
                    array_shift($csv_data);
                }
                $csv_data = array_unique($csv_data);
                $db_data = get_location_master();

                $to_add = array_diff($csv_data, $db_data);
                $to_remove = array_diff($db_data, $csv_data);

                $_SESSION['sync_type'] = 'location';
                $_SESSION['sync_add'] = $to_add;
                $_SESSION['sync_remove'] = $to_remove;
                $_SESSION['sync_conflict'] = [];
                $show_sync_ui = true;
            }
        }
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master(); 
        $location_list = get_location_master();
        require 'views/admin.php';
        break;

    // --- 17. Admin: 執行同步 ---
    case 'admin_sync_execute':
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['sync_type'])) {
            $type = $_SESSION['sync_type'];
            $adds = $_SESSION['sync_add'] ?? [];
            $removes = $_SESSION['sync_remove'] ?? [];
            $conflicts = $_SESSION['sync_conflict'] ?? [];
            $decisions = $_POST['decision'] ?? []; 

            $db_global = get_global_db();
            $msg = "";

            if ($type == 'tool') {
                $addStmt = $db_global->prepare("INSERT OR IGNORE INTO tool_master (name) VALUES (?)");
                foreach ($adds as $name) $addStmt->execute([$name]);
                $delStmt = $db_global->prepare("DELETE FROM tool_master WHERE name = ?");
                foreach ($removes as $name) $delStmt->execute([$name]);
                $msg = "機台清單同步完成：新增 " . count($adds) . " 筆，刪除 " . count($removes) . " 筆。";

            } elseif ($type == 'location') {
                $addStmt = $db_global->prepare("INSERT OR IGNORE INTO location_master (name) VALUES (?)");
                foreach ($adds as $name) $addStmt->execute([$name]);
                $delStmt = $db_global->prepare("DELETE FROM location_master WHERE name = ?");
                foreach ($removes as $name) $delStmt->execute([$name]);
                $msg = "位置清單同步完成：新增 " . count($adds) . " 筆，刪除 " . count($removes) . " 筆。";

            } elseif ($type == 'part_master') {
                $delStmt = $db_global->prepare("DELETE FROM part_master WHERE part_no = ?");
                foreach ($removes as $item) $delStmt->execute([$item['part_no']]);

                $addStmt = $db_global->prepare("INSERT INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
                foreach ($adds as $item) $addStmt->execute([$item['part_no'], $item['name'], $item['vendor']]);

                $updStmt = $db_global->prepare("UPDATE part_master SET name = ?, vendor = ? WHERE part_no = ?");
                $conflict_update_count = 0;
                foreach ($conflicts as $c) {
                    $p_no = $c['part_no'];
                    if (isset($decisions[$p_no]) && $decisions[$p_no] === 'csv') {
                        $updStmt->execute([$c['csv']['name'], $c['csv']['vendor'], $p_no]);
                        $conflict_update_count++;
                    }
                }
                $msg = "Part Master 同步完成：新增 ".count($adds)." 筆，刪除 ".count($removes)." 筆，更新 $conflict_update_count 筆。";
            }
            unset($_SESSION['sync_type'], $_SESSION['sync_add'], $_SESSION['sync_remove'], $_SESSION['sync_conflict']);
        }
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master(); 
        $location_list = get_location_master();
        require 'views/admin.php';
        break;

    // --- 18. Tool / Location 匯出與下載 ---
    case 'tool_export':
        if (ob_get_level()) ob_end_clean();
        $db_global = get_global_db();
        $stmt = $db_global->query("SELECT name FROM tool_master ORDER BY name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Tool_List.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Tool ID'], ",", "\"", "\\");
        foreach ($rows as $row) { fputcsv($output, [$row], ",", "\"", "\\"); }
        fclose($output);
        exit; break;

    case 'download_tool_template':
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tool_template.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Tool ID'], ",", "\"", "\\");
        fputcsv($output, ['TOOL-A01'], ",", "\"", "\\");
        fclose($output);
        exit; break;

    case 'location_export':
        if (ob_get_level()) ob_end_clean();
        $db_global = get_global_db();
        $stmt = $db_global->query("SELECT name FROM location_master ORDER BY name ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Location_List.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Location'], ",", "\"", "\\");
        foreach ($rows as $row) { fputcsv($output, [$row], ",", "\"", "\\"); }
        fclose($output);
        exit; break;

    case 'download_location_template':
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="location_template.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Location'], ",", "\"", "\\");
        fputcsv($output, ['SHELF-01-A'], ",", "\"", "\\");
        fclose($output);
        exit; break;

    // --- 19. 其他路由 ---
    case 'download_template':
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="part_master_template.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['PartNo', 'Name', 'Vendor'], ",", "\"", "\\");
        fputcsv($output, ['PN-EXAMPLE-01', 'Example Part', 'ASML'], ",", "\"", "\\");
        fclose($output);
        exit; break;

    case 'ipart_pending':
        $pending = [];
        foreach (DEPARTMENTS as $dept) {
            $db = get_db($dept);
            $stmt = $db->query("SELECT *, '$dept' as dept_source FROM part_lifecycle WHERE status='ON' AND ipart_logged=0");
            $pending = array_merge($pending, $stmt->fetchAll());
        }
        require 'views/pending_list.php';
        break;

    case 'api_complete':
        $dept = $_GET['dept'];
        $id = $_GET['id'];
        if ($dept && $id) {
            $db = get_db($dept);
            $stmt = $db->prepare("UPDATE part_lifecycle SET ipart_logged=1 WHERE id=?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?route=ipart_pending');
        exit;
        break;

    default:
        header('Location: index.php?route=dashboard');
        exit;
}
?>