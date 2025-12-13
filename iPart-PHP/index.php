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
    // --- 登入 ---
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

    // --- 登出 ---
    case 'logout':
        session_destroy();
        header('Location: index.php?route=login');
        break;

    // --- Dashboard ---
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

    // --- 作業中心 ---
    case 'ops':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $logs = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 20")->fetchAll();
        $inventory_items = get_current_inventory($curr_dept);
        $inv_count = count($inventory_items);
        require 'views/ops_center.php';
        break;

    // --- 資料庫編輯 ---
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
        
        $h_locs = $db->query("SELECT DISTINCT location FROM part_lifecycle WHERE location IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
        require 'views/ops_edit.php';
        break;

    // --- 庫存明細 ---
    case 'inventory':
        $curr_dept = $_SESSION['user_id'];
        $items = get_current_inventory($curr_dept);
        require 'views/inventory.php';
        break;

    // --- 批次退料 ---
    case 'ops_batch_out':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['out_ids'])) {
            $ids = $_POST['out_ids']; 
            $remark = $_POST['batch_remark'] ?? 'Batch Return';
            
            $findStmt = $db->prepare("SELECT * FROM part_lifecycle WHERE id = ?");
            $insertStmt = $db->prepare("
                INSERT INTO part_lifecycle (dept, status, part_no, part_name, vendor, sn, location, ipart_logged, remark)
                VALUES (?, 'OUT', ?, ?, ?, ?, ?, 0, ?)
            ");

            foreach ($ids as $id) {
                $findStmt->execute([$id]);
                $origin = $findStmt->fetch();
                if ($origin) {
                    $insertStmt->execute([
                        $curr_dept,
                        $origin['part_no'],
                        $origin['part_name'],
                        $origin['vendor'],
                        $origin['sn'],
                        $origin['location'], 
                        $remark
                    ]);
                }
            }
        }
        header('Location: index.php?route=ops');
        exit;
        break;
    // index.php (請插入在 ops_batch_out 之後，ops_new 之前)

    // --- ★ 新增：退料歷史查詢 ---
    case 'return_history':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        // 撈取最近 100 筆退料紀錄
        $stmt = $db->query("SELECT * FROM part_lifecycle WHERE status='OUT' ORDER BY created_at DESC LIMIT 100");
        $returns = $stmt->fetchAll();
        require 'views/return_history.php';
        break;
            
    // --- 新增作業 (進料/上機/退料) ---
    case 'ops_new':
        $curr_dept = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['status'];
            $db = get_db($curr_dept);
            $location_val = ($status === 'ON') ? ($_POST['tool_id'] ?? '') : ($_POST['location'] ?? '');
            $ipart_logged = isset($_POST['ipart_logged']) ? 1 : 0;

            $stmt = $db->prepare("
                INSERT INTO part_lifecycle (dept, status, part_no, part_name, vendor, sn, location, ipart_logged, remark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $curr_dept, $status, 
                $_POST['part_no'], $_POST['part_name'], $_POST['vendor'], $_POST['sn'], 
                $location_val, $ipart_logged, $_POST['remark']
            ]);

            sync_part_master($_POST['part_no'], $_POST['part_name'], $_POST['vendor']);
            header('Location: index.php?route=ops');
            exit;
        }

        $status = $_GET['status'] ?? 'IN';
        $db = get_db($curr_dept);
        $h_locs = $db->query("SELECT DISTINCT location FROM part_lifecycle WHERE location IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

        $inventory_list = [];
        $master_list = [];
        $mounted_list = []; 

        if ($status === 'ON') {
            $inventory_list = get_current_inventory($curr_dept);
        } elseif ($status === 'OUT') {
            $mounted_list = get_mounted_parts($curr_dept);
        } else {
            $master_list = get_part_master();
        }

        $prefill = [
            'part_no' => $_GET['part_no'] ?? '',
            'part_name' => $_GET['part_name'] ?? '',
            'vendor' => $_GET['vendor'] ?? '',
            'sn' => $_GET['sn'] ?? ''
        ];

        // ★ 修改點：移除原本依據部門設定 ASML/TEL 的邏輯
        $default_vendor = ''; 

        require 'views/ops_form.php';
        break;

    // --- 管理員頁面 ---
    case 'admin':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        require 'views/admin.php';
        break;

    // --- CSV 匯入 ---
    case 'admin_import':
        $db_global = get_global_db();
        if (isset($_POST['resolve_submit'])) {
            $inserts = $_SESSION['import_inserts'] ?? [];
            $conflicts = $_SESSION['import_conflicts'] ?? [];
            $decisions = $_POST['decision'] ?? [];
            $added = 0; $updated = 0; $skipped = $_SESSION['import_skips'] ?? 0;

            $insertStmt = $db_global->prepare("INSERT INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
            foreach ($inserts as $item) {
                $insertStmt->execute([$item['part_no'], $item['name'], $item['vendor']]);
                $added++;
            }
            $updateStmt = $db_global->prepare("UPDATE part_master SET name = ?, vendor = ? WHERE part_no = ?");
            foreach ($conflicts as $c) {
                $p_no = $c['part_no'];
                if (isset($decisions[$p_no]) && $decisions[$p_no] === 'csv') {
                    $updateStmt->execute([$c['csv']['name'], $c['csv']['vendor'], $p_no]);
                    $updated++;
                } else {
                    $skipped++;
                }
            }
            unset($_SESSION['import_inserts'], $_SESSION['import_conflicts'], $_SESSION['import_skips']);
            $msg = "處理完成：新增 $added 筆，更新 $updated 筆，維持/跳過 $skipped 筆。";
            
            $curr_dept = $_SESSION['user_id'];
            $db = get_db($curr_dept);
            $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
            $my_records = $stmt->fetchAll();
            require 'views/admin.php';
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $to_insert = []; $conflicts = []; $skips_count = 0; $row = 0;
                $checkStmt = $db_global->prepare("SELECT * FROM part_master WHERE part_no = ?");

                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $row++; if ($row == 1) continue; 
                    $part_no = trim($data[0] ?? ''); $name = trim($data[1] ?? ''); $vendor = trim($data[2] ?? '');
                    if (!$part_no) continue;

                    $checkStmt->execute([$part_no]);
                    $exist = $checkStmt->fetch();
                    if ($exist) {
                        if ($exist['name'] !== $name || $exist['vendor'] !== $vendor) {
                            $conflicts[] = ['part_no' => $part_no, 'db' => ['name' => $exist['name'], 'vendor' => $exist['vendor']], 'csv' => ['name' => $name, 'vendor' => $vendor]];
                        } else { $skips_count++; }
                    } else {
                        $to_insert[] = ['part_no' => $part_no, 'name' => $name, 'vendor' => $vendor];
                    }
                }
                fclose($handle);

                if (count($conflicts) > 0) {
                    $_SESSION['import_inserts'] = $to_insert;
                    $_SESSION['import_conflicts'] = $conflicts;
                    $_SESSION['import_skips'] = $skips_count;
                    $show_conflict_ui = true; 
                } else {
                    $insertStmt = $db_global->prepare("INSERT INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
                    $added = 0;
                    foreach ($to_insert as $item) {
                        $insertStmt->execute([$item['part_no'], $item['name'], $item['vendor']]);
                        $added++;
                    }
                    $msg = "匯入成功！新增 $added 筆，跳過(重複) $skips_count 筆。";
                }
            } else { $error = "無法讀取檔案"; }
        }
        
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        require 'views/admin.php';
        break;

    // --- 下載範本 ---
    case 'download_template':
        ob_end_clean();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="part_master_template.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['PartNo', 'Name', 'Vendor'], ",", "\"", "\\");
        fputcsv($output, ['PN-EXAMPLE-01', 'Example Part', 'ASML'], ",", "\"", "\\");
        fclose($output);
        exit;
        break;

    // --- 待補登清單 ---
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