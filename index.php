<?php
// index.php - 核心路由控制器 (CSV 標題中文化版)
session_start();
date_default_timezone_set('Asia/Taipei'); 

// 引入核心函式庫
require_once 'functions.php';

// ====================================================
// 1. 路由與權限控制核心
// ====================================================

$route = $_GET['route'] ?? 'dashboard';

// Auth Check: 未登入強制導向 Login
if (!isset($_SESSION['user_id']) && $route !== 'login') {
    header('Location: index.php?route=login');
    exit;
}

// Guest Check: 訪客權限卡控
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] === 'Guest') {
    $allowed_routes = ['dashboard', 'logout'];
    
    if (!in_array($route, $allowed_routes)) {
        header('Location: index.php?route=dashboard');
        exit;
    }
}

switch ($route) {
    // ------------------------------------------------
    // 1. 系統登入
    // ------------------------------------------------
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $_POST['username'] ?? '';
            $pwd = $_POST['password'] ?? '';
            
            // 驗證
            if ((defined('DEPARTMENTS') && in_array($user, DEPARTMENTS) && $pwd === $user) || ($user === 'Guest' && $pwd === 'Guest')) {
                $_SESSION['user_id'] = $user;
                header('Location: index.php?route=dashboard');
                exit;
            } else {
                $error = "帳號或密碼錯誤";
            }
        }
        require 'views/login.php';
        break;

    // ------------------------------------------------
    // 2. 系統登出
    // ------------------------------------------------
    case 'logout':
        session_destroy();
        header('Location: index.php?route=login');
        exit;

    // ------------------------------------------------
    // 3. 儀表板 (Dashboard)
    // ------------------------------------------------
    case 'dashboard':
        $target_dept = $_GET['dept'] ?? 'ALL';
        $target_cat = $_GET['cat'] ?? 'Contract Tool Part'; 
        
        $trend_daily = get_trend_data($target_dept, 'daily', $target_cat);
        $trend_weekly = get_trend_data($target_dept, 'weekly', $target_cat);
        $trend_monthly = get_trend_data($target_dept, 'monthly', $target_cat);

        $dept_stats = [];
        $today_str = date('Y-m-d');
        
        if ($target_dept !== 'ALL') {
            $scan_depts = [$target_dept];
        } else {
            $scan_depts = DEPARTMENTS;
        }

        $catSql = "";
        if ($target_cat !== 'ALL') {
            $catSql = " AND category = '$target_cat'";
        }

        foreach ($scan_depts as $d) {
            try {
                $db = get_db($d);
                
                $sqlOn = "SELECT COUNT(*) FROM part_lifecycle WHERE status='ON' AND date(created_at, 'localtime')=? $catSql";
                $stmtOn = $db->prepare($sqlOn);
                $stmtOn->execute([$today_str]);
                $on = $stmtOn->fetchColumn();

                $sqlLog = "SELECT COUNT(*) FROM part_lifecycle WHERE status='ON' AND ipart_logged=1 AND date(created_at, 'localtime')=? $catSql";
                $stmtLog = $db->prepare($sqlLog);
                $stmtLog->execute([$today_str]);
                $logged = $stmtLog->fetchColumn();

                if ($on > 0) {
                    $rate = round(($logged / $on * 100), 1);
                } else {
                    $rate = -1;
                }
                
                $dept_stats[] = [
                    'name' => $d, 
                    'on' => $on, 
                    'logged' => $logged, 
                    'rate' => $rate
                ];
            } catch (Exception $e) {
                continue;
            }
        }
        require 'views/dashboard.php';
        break;

    // ------------------------------------------------
    // 4. 作業中心 (Ops Center)
    // ------------------------------------------------
    case 'ops':
        $curr_dept = $_SESSION['user_id'];
        
        $default_start = date('Y-m-d', strtotime('-7 days'));
        $default_end   = date('Y-m-d');
        
        $start_date = $_GET['start_date'] ?? $default_start;
        $end_date   = $_GET['end_date'] ?? $default_end;

        $logs = get_logs_by_date($curr_dept, $start_date, $end_date);
        require 'views/ops_center.php';
        break;

    // ------------------------------------------------
    // 5. 匯出流水帳 CSV (★ 修改：標題中文化)
    // ------------------------------------------------
    case 'ops_export_csv':
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $curr_dept = $_SESSION['user_id'];
        $start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
        $end_date   = $_GET['end_date'] ?? date('Y-m-d');
        
        $data = get_csv_lifecycle_data($curr_dept, $start_date, $end_date);
        
        $filename = "Logbook_{$curr_dept}_{$start_date}_to_{$end_date}.csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        
        // ★ 修改：使用更清楚的中文標題
        $headers = [
            '料號 (Part No)', 
            '品名 (Name)', 
            '廠商 (Vendor)', 
            '序號 (S/N)', 
            '分類 (Category)', 
            '位置/機台 (Location)', 
            '目前狀態 (Status)', 
            '進料時間 (IN Time)', 
            '上機時間 (ON Time)', 
            '退料時間 (OUT Time)', 
            '上機天數 (Run Days)', 
            '備註 (Remark)'
        ];
        
        fputcsv($output, $headers, ",", "\"", "\\");
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['part_no'],
                $row['name'],
                $row['vendor'],
                $row['sn'],
                $row['category'],
                $row['machine'],
                $row['status'],
                $row['in_date'],
                $row['on_date'],
                $row['out_date'],
                $row['days'],
                $row['remark']
            ], ",", "\"", "\\");
        }
        fclose($output);
        exit;
        break;

    // ------------------------------------------------
    // 6. 刪除單筆紀錄
    // ------------------------------------------------
    case 'ops_delete':
        $curr_dept = $_SESSION['user_id'];
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $db = get_db($curr_dept);
            $stmt = $db->prepare("DELETE FROM part_lifecycle WHERE id = ?");
            $stmt->execute([$id]);
        }
        header('Location: index.php?route=admin');
        exit;
        break;

    // ------------------------------------------------
    // 7. 編輯單筆紀錄
    // ------------------------------------------------
    case 'ops_edit':
        $curr_dept = $_SESSION['user_id'];
        $id = $_GET['id'] ?? null;
        
        if (!$id) { 
            header('Location: index.php?route=ops'); 
            exit; 
        }
        
        $db = get_db($curr_dept);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "UPDATE part_lifecycle SET 
                    status = ?, 
                    part_no = ?, 
                    part_name = ?, 
                    vendor = ?, 
                    sn = ?, 
                    location = ?, 
                    remark = ?, 
                    category = ?, 
                    ipart_logged = ? 
                    WHERE id = ? AND dept = ?";
            
            $stmt = $db->prepare($sql);
            $ipart_val = isset($_POST['ipart_logged']) ? 1 : 0;
            
            $stmt->execute([
                $_POST['status'], 
                $_POST['part_no'], 
                $_POST['part_name'], 
                $_POST['vendor'], 
                $_POST['sn'], 
                $_POST['location'], 
                $_POST['remark'], 
                $_POST['category'] ?? '', 
                $ipart_val, 
                $id, 
                $curr_dept
            ]);
            
            header('Location: index.php?route=ops');
            exit;
        }

        $stmt = $db->prepare("SELECT * FROM part_lifecycle WHERE id = ? AND dept = ?");
        $stmt->execute([$id, $curr_dept]);
        $record = $stmt->fetch();
        
        if (!$record) { 
            die("無權限或查無資料"); 
        }
        
        $h_locs = get_location_master($curr_dept); 
        require 'views/ops_edit.php';
        break;

    // ------------------------------------------------
    // 8. 庫存明細
    // ------------------------------------------------
    case 'inventory':
        $curr_dept = $_SESSION['user_id'];
        $items = get_current_inventory($curr_dept);
        require 'views/inventory.php';
        break;

    // ------------------------------------------------
    // 9. 批次退料處理
    // ------------------------------------------------
    case 'ops_batch_out':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['out_ids'])) {
            $ids = $_POST['out_ids']; 
            $remark = $_POST['batch_remark'] ?? 'Batch Return';
            
            $findStmt = $db->prepare("SELECT * FROM part_lifecycle WHERE id = ?");
            
            $insertStmt = $db->prepare("
                INSERT INTO part_lifecycle 
                (dept, status, part_no, part_name, vendor, sn, location, category, ipart_logged, remark)
                VALUES (?, 'OUT', ?, ?, ?, ?, ?, ?, 0, ?)
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
                        $origin['category'] ?? '', 
                        $remark
                    ]);
                }
            }
        }
        header('Location: index.php?route=ops');
        exit;
        break;

    // ------------------------------------------------
    // 10. 退料歷史查詢
    // ------------------------------------------------
    case 'return_history':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        
        $stmt = $db->query("SELECT * FROM part_lifecycle WHERE status='OUT' ORDER BY created_at DESC LIMIT 100");
        $returns = $stmt->fetchAll();
        
        require 'views/return_history.php';
        break;

    // ------------------------------------------------
    // 11. 新增作業 (IN/ON/OUT)
    // ------------------------------------------------
    case 'ops_new':
        $curr_dept = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['status'];
            $db = get_db($curr_dept);
            
            if ($status === 'ON') {
                $location_val = $_POST['tool_id'] ?? '';
            } else {
                $location_val = $_POST['location'] ?? '';
            }
            
            $ipart_logged = isset($_POST['ipart_logged']) ? 1 : 0;
            $category = $_POST['category'] ?? '';

            if ($status === 'IN' && !empty($location_val)) {
                add_location_master($curr_dept, $location_val);
            }

            $stmt = $db->prepare("
                INSERT INTO part_lifecycle 
                (dept, status, part_no, part_name, vendor, sn, location, category, ipart_logged, remark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $curr_dept, 
                $status, 
                $_POST['part_no'], 
                $_POST['part_name'], 
                $_POST['vendor'], 
                $_POST['sn'], 
                $location_val, 
                $category, 
                $ipart_logged, 
                $_POST['remark']
            ]);

            sync_part_master($curr_dept, $_POST['part_no'], $_POST['part_name'], $_POST['vendor']);
            
            header('Location: index.php?route=ops');
            exit;
        }

        $status = $_GET['status'] ?? 'IN';
        $db = get_db($curr_dept);
        
        $tool_master = get_tool_master($curr_dept);
        $location_master = get_location_master($curr_dept);

        $inventory_list = []; 
        $master_list = []; 
        $return_list = []; 
        
        if ($status === 'ON') {
            $inventory_list = get_current_inventory($curr_dept);
        } elseif ($status === 'OUT') {
            $return_list = get_returnable_items($curr_dept);
        } else {
            $master_list = get_part_master($curr_dept);
        }

        $prefill = [
            'part_no' => $_GET['part_no'] ?? '',
            'part_name' => $_GET['part_name'] ?? '',
            'vendor' => $_GET['vendor'] ?? '',
            'sn' => $_GET['sn'] ?? ''
        ];

        require 'views/ops_form.php';
        break;

    // ------------------------------------------------
    // 12. 管理員：主檔管理
    // ------------------------------------------------
    case 'admin_manage_master':
        $curr_dept = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type']; 
            $action = $_POST['action']; 
            $name = trim($_POST['name']);

            if ($action === 'delete') {
                if ($type === 'tool') {
                    delete_master_item($curr_dept, 'tool_master', $name);
                } elseif ($type === 'location') {
                    delete_master_item($curr_dept, 'location_master', $name);
                } elseif ($type === 'part') {
                    delete_master_item($curr_dept, 'part_master', $name);
                }
            } elseif ($action === 'add') {
                if ($type === 'tool') {
                    add_tool_master($curr_dept, $name);
                } elseif ($type === 'location') {
                    add_location_master($curr_dept, $name);
                }
            }
        }
        header('Location: index.php?route=admin');
        exit;
        break;

    // ------------------------------------------------
    // 13. 管理員：匯出與下載清單 (Master List)
    // ------------------------------------------------
    case 'admin_export':
    case 'download_template': 
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $curr_dept = $_SESSION['user_id'];
        $type = $_GET['type'] ?? 'part';
        $db = get_db($curr_dept);
        
        $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
        $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");
        $db->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");

        // 下載完整清單 (範本)
        if ($route === 'download_template') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="Master_Full_List.csv"');
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            
            // ★ 修改：標題中文化
            fputcsv($output, ['分類 (Category: PART/TOOL)', '料號 (PartNo)', '品名 (Name)', '廠商 (Vendor)'], ",", "\"", "\\");
            
            $sqlPart = "SELECT 'PART', part_no, name, vendor FROM part_master ORDER BY part_no";
            $stmt = $db->query($sqlPart);
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                fputcsv($output, $row, ",", "\"", "\\");
            }

            $sqlTool = "SELECT 'TOOL', name, '', '' FROM tool_master ORDER BY name";
            $stmt = $db->query($sqlTool);
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                fputcsv($output, $row, ",", "\"", "\\");
            }
            
            fclose($output);
            exit;
        }

        // 個別匯出 (清單)
        if ($type === 'part') {
            $filename = "Part_Master_{$curr_dept}.csv";
            $sql = "SELECT 'PART', part_no, name, vendor FROM part_master ORDER BY part_no";
            // ★ 修改：標題中文化
            $headers = ['分類 (Category)', '料號 (Part No)', '品名 (Name)', '廠商 (Vendor)'];
        } elseif ($type === 'tool') {
            $filename = "Tool_List_{$curr_dept}.csv";
            $sql = "SELECT 'TOOL', name, '', '' FROM tool_master ORDER BY name";
            // ★ 修改：標題中文化
            $headers = ['分類 (Category)', '機台編號 (Tool ID)', '名稱 (Name)', '備註 (Remark)'];
        } elseif ($type === 'location') {
            $filename = "Location_List_{$curr_dept}.csv";
            $sql = "SELECT 'LOC', name, '', '' FROM location_master ORDER BY name";
            // ★ 修改：標題中文化
            $headers = ['分類 (Category)', '儲存位置 (Location)', '名稱 (Name)', '備註 (Remark)'];
        } else {
            header('Location: index.php?route=admin'); 
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ",", "\"", "\\");
        $stmt = $db->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            fputcsv($output, $row, ",", "\"", "\\");
        }
        fclose($output);
        exit;
        break;

    // ------------------------------------------------
    // 14. 管理員：統一匯入 (Master Data)
    // ------------------------------------------------
    case 'admin_import':
        $curr_dept = $_SESSION['user_id'];
        $type = $_GET['type'] ?? 'part'; 
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
            $file = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                $db = get_db($curr_dept);
                $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
                $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");
                $db->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");

                $to_insert = []; 
                $conflicts = []; 
                $category_errors = []; 
                $row_idx = 0;

                $stmtCheckPart = $db->prepare("SELECT * FROM part_master WHERE part_no = ?");
                
                $existingTools = $db->query("SELECT name FROM tool_master")->fetchAll(PDO::FETCH_COLUMN);
                $toolMap = []; foreach($existingTools as $v) { $toolMap[strtolower($v)] = $v; }

                // PHP 7.4 Fix
                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $row_idx++; 
                    if ($row_idx == 1) continue; 

                    $cat = strtoupper(clean_csv_value($data[0]??''));
                    $key = clean_csv_value($data[1]??'');
                    $name = clean_csv_value($data[2]??'');
                    $vendor = clean_csv_value($data[3]??'');

                    if (!$key) continue;

                    if ($cat !== 'PART' && $cat !== 'TOOL') {
                        $category_errors[] = [
                            'raw_cat' => $cat,
                            'key' => $key,
                            'csv' => ['cat'=>$cat, 'key'=>$key, 'name'=>$name, 'vendor'=>$vendor]
                        ];
                        continue;
                    }

                    if ($cat === 'PART') {
                        $stmtCheckPart->execute([$key]);
                        $exist = $stmtCheckPart->fetch();
                        
                        if ($exist) {
                            if ($exist['name'] !== $name || $exist['vendor'] !== $vendor) {
                                $conflicts[] = [
                                    'type' => 'PART',
                                    'key' => $key, 
                                    'db' => $exist, 
                                    'csv' => ['part_no'=>$key, 'name'=>$name, 'vendor'=>$vendor]
                                ];
                            }
                        } else {
                            $to_insert[] = ['type'=>'PART', 'part_no'=>$key, 'name'=>$name, 'vendor'=>$vendor];
                        }
                    }

                    if ($cat === 'TOOL') {
                        if (!isset($toolMap[strtolower($key)])) {
                            $to_insert[] = ['type'=>'TOOL', 'name'=>$key];
                        }
                    }
                }
                fclose($handle);

                if (count($conflicts) > 0 || count($category_errors) > 0) {
                    $_SESSION['import_inserts'] = $to_insert;
                    $_SESSION['import_conflicts'] = $conflicts;
                    $_SESSION['import_cat_errors'] = $category_errors;
                    $show_conflict_ui = true;
                } else {
                    $added_items = [];
                    $stmtInPart = $db->prepare("INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
                    $stmtInTool = $db->prepare("INSERT OR IGNORE INTO tool_master (name) VALUES (?)");

                    foreach ($to_insert as $item) {
                        if ($item['type'] === 'PART') {
                            $stmtInPart->execute([$item['part_no'], $item['name'], $item['vendor']]);
                        } else {
                            $stmtInTool->execute([$item['name']]);
                        }
                        $added_items[] = $item;
                    }
                    $import_success = true;
                }
            } else { 
                $error = "檔案讀取失敗"; 
            }
        }
        
        $part_list_all = get_part_master($curr_dept);
        $tool_list = get_tool_master($curr_dept); 
        $location_list = get_location_master($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 50");
        $my_records = $stmt->fetchAll();
        require 'views/admin.php';
        break;

    // ------------------------------------------------
    // 15. 管理員：衝突解決
    // ------------------------------------------------
    case 'admin_resolve_conflict':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        
        $inserts = $_SESSION['import_inserts'] ?? [];
        $conflicts = $_SESSION['import_conflicts'] ?? [];
        $cat_errors = $_SESSION['import_cat_errors'] ?? [];
        $decisions = $_POST['decision'] ?? [];
        $cat_fix = $_POST['cat_fix'] ?? [];

        $added_items = [];
        $updated_items = [];

        $stmtInPart = $db->prepare("INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
        $stmtInTool = $db->prepare("INSERT OR IGNORE INTO tool_master (name) VALUES (?)");

        foreach ($inserts as $item) {
            if ($item['type'] === 'PART') {
                $stmtInPart->execute([$item['part_no'], $item['name'], $item['vendor']]);
            } else {
                $stmtInTool->execute([$item['name']]);
            }
            $added_items[] = $item;
        }

        $stmtUpdPart = $db->prepare("UPDATE part_master SET name = ?, vendor = ? WHERE part_no = ?");
        foreach ($conflicts as $idx => $c) {
            $decision = $decisions[$idx] ?? 'db';
            if ($decision === 'csv') {
                $stmtUpdPart->execute([$c['csv']['name'], $c['csv']['vendor'], $c['key']]);
                $updated_items[] = $c['csv'];
            }
        }

        foreach ($cat_errors as $idx => $err) {
            $choice = $cat_fix[$idx] ?? 'skip';
            $csv = $err['csv'];
            if ($choice === 'PART') {
                $stmtInPart->execute([$csv['key'], $csv['name'], $csv['vendor']]);
                $added_items[] = ['type'=>'PART', 'part_no'=>$csv['key'], 'name'=>$csv['name'], 'vendor'=>$csv['vendor']];
            } elseif ($choice === 'TOOL') {
                $stmtInTool->execute([$csv['key']]);
                $added_items[] = ['type'=>'TOOL', 'name'=>$csv['key']];
            }
        }

        unset($_SESSION['import_inserts'], $_SESSION['import_conflicts'], $_SESSION['import_cat_errors']);
        $import_success = true;

        $part_list_all = get_part_master($curr_dept);
        $tool_list = get_tool_master($curr_dept); 
        $location_list = get_location_master($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 50");
        $my_records = $stmt->fetchAll();
        require 'views/admin.php';
        break;

    // ------------------------------------------------
    // 20. 待補登清單
    // ------------------------------------------------
    case 'ipart_pending':
        $pending = [];
        $dept = $_SESSION['user_id'];
        $db = get_db($dept);
        
        $stmt = $db->query("SELECT *, '$dept' as dept_source FROM part_lifecycle WHERE status='ON' AND ipart_logged=0");
        $pending = $stmt->fetchAll();
        
        require 'views/pending_list.php';
        break;

    // ------------------------------------------------
    // 21. API 完成補登
    // ------------------------------------------------
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

    // ------------------------------------------------
    // 22. 管理員頁面
    // ------------------------------------------------
    case 'admin':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 50");
        $my_records = $stmt->fetchAll();

        $tool_list = get_tool_master($curr_dept); 
        $location_list = get_location_master($curr_dept);
        $part_list_all = get_part_master($curr_dept);

        require 'views/admin.php';
        break;

    default:
        header('Location: index.php?route=dashboard');
        exit;
}
?>