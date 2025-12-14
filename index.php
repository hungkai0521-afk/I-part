<?php
// index.php
session_start();
date_default_timezone_set('Asia/Taipei'); 

// 引入核心函式庫 (clean_csv_value 函式已在其中定義)
require_once 'functions.php';

$route = $_GET['route'] ?? 'dashboard';

// Auth Check: 未登入強制導向 Login
if (!isset($_SESSION['user_id']) && $route !== 'login') {
    header('Location: index.php?route=login');
    exit;
}

switch ($route) {
    // ------------------------------------------------
    // 1. 系統登入
    // ------------------------------------------------
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = $_POST['username'] ?? '';
            $pwd = $_POST['password'] ?? '';
            
            // 簡單驗證：帳號密碼相同且在部門清單內
            if (in_array($user, DEPARTMENTS) && $pwd === $user) {
                $_SESSION['user_id'] = $user;
                // 登入後導向至儀表板 (Dashboard)
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
        break;

    // ------------------------------------------------
    // 3. 儀表板 (Dashboard) - ★ 支援分類篩選 (邏輯展開)
    // ------------------------------------------------
    case 'dashboard':
        $target_dept = $_GET['dept'] ?? 'ALL';
        // ★ 接收分類參數，預設為 Contract Tool Part
        $target_cat = $_GET['cat'] ?? 'Contract Tool Part'; 
        
        // ★ 將分類傳入圖表計算函式
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

        // 準備分類 SQL 條件 (給下方表格使用) - 邏輯展開
        $catSql = "";
        if ($target_cat !== 'ALL') {
            $catSql = " AND category = '$target_cat'";
        }

        foreach ($scan_depts as $d) {
            $db = get_db($d);
            
            // ★ 表格數據也加入分類篩選 $catSql
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
        }
        require 'views/dashboard.php';
        break;

    // ------------------------------------------------
    // 4. 作業中心 (List View)
    // ------------------------------------------------
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

    // ------------------------------------------------
    // 5. 匯出流水帳 CSV
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
        fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
        
        fputcsv($output, ['PARTNO', '品名', '序號', '分類', '機台', 'in_date', 'on_date', 'out_date'], ",", "\"", "\\");
        
        foreach ($data as $row) {
            fputcsv($output, [
                $row['part_no'],
                $row['name'],
                $row['sn'],
                $row['category'],
                $row['machine'],
                $row['in_date'],
                $row['on_date'],
                $row['out_date']
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
        $mounted_list = []; 
        
        if ($status === 'ON') {
            $inventory_list = get_current_inventory($curr_dept);
        } elseif ($status === 'OUT') {
            $mounted_list = get_mounted_parts($curr_dept);
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
    // 12. 管理員：主檔管理 (單筆新增/刪除)
    // ------------------------------------------------
    case 'admin_manage_master':
        $curr_dept = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type = $_POST['type']; 
            $action = $_POST['action']; 
            $name = trim($_POST['name']);

            if ($type === 'tool') {
                if ($action === 'add') {
                    add_tool_master($curr_dept, $name);
                }
                if ($action === 'delete') {
                    delete_master_item($curr_dept, 'tool_master', $name);
                }
            } elseif ($type === 'location') {
                if ($action === 'add') {
                    add_location_master($curr_dept, $name);
                }
                if ($action === 'delete') {
                    delete_master_item($curr_dept, 'location_master', $name);
                }
            }
        }
        header('Location: index.php?route=admin');
        exit;
        break;

    // ------------------------------------------------
    // 13. 管理員：統一匯出
    // ------------------------------------------------
    case 'admin_export':
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $curr_dept = $_SESSION['user_id'];
        $type = $_GET['type'] ?? 'part';
        $db = get_db($curr_dept);
        
        $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
        $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");
        $db->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");

        if ($type === 'part') {
            $filename = "Part_Master_{$curr_dept}.csv";
            $sql = "SELECT part_no, name, vendor FROM part_master ORDER BY part_no";
            $headers = ['PartNo', 'Name', 'Vendor'];
        } elseif ($type === 'tool') {
            $filename = "Tool_List_{$curr_dept}.csv";
            $sql = "SELECT name FROM tool_master ORDER BY name";
            $headers = ['Tool ID'];
        } elseif ($type === 'location') {
            $filename = "Location_List_{$curr_dept}.csv";
            $sql = "SELECT name FROM location_master ORDER BY name";
            $headers = ['Location'];
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
    // 14. 管理員：統一匯入 - 階段1
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
                $skips_count = 0; 
                $row_idx = 0;

                if ($type === 'part') {
                    $stmtCheck = $db->prepare("SELECT * FROM part_master WHERE part_no = ?");
                } elseif ($type === 'tool') {
                    $existing = $db->query("SELECT name FROM tool_master")->fetchAll(PDO::FETCH_COLUMN);
                    $map = []; 
                    foreach($existing as $v) {
                        $map[strtolower($v)] = $v;
                    }
                } elseif ($type === 'location') {
                    $existing = $db->query("SELECT name FROM location_master")->fetchAll(PDO::FETCH_COLUMN);
                    $map = []; 
                    foreach($existing as $v) {
                        $map[strtolower($v)] = $v;
                    }
                }

                while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
                    $row_idx++; 
                    if ($row_idx == 1) {
                        continue;
                    }

                    if ($type === 'part') {
                        $p = clean_csv_value($data[0]??''); 
                        $n = clean_csv_value($data[1]??''); 
                        $v = clean_csv_value($data[2]??'');
                        
                        if (!$p) continue;

                        $stmtCheck->execute([$p]);
                        $exist = $stmtCheck->fetch();
                        
                        if ($exist) {
                            if ($exist['name'] !== $n || $exist['vendor'] !== $v) {
                                $conflicts[] = [
                                    'key' => $p, 
                                    'db' => $exist, 
                                    'csv' => ['part_no'=>$p, 'name'=>$n, 'vendor'=>$v]
                                ];
                            } else { 
                                $skips_count++; 
                            }
                        } else {
                            $to_insert[] = ['part_no'=>$p, 'name'=>$n, 'vendor'=>$v];
                        }

                    } else {
                        $val = clean_csv_value($data[0]??'');
                        if (!$val) continue;
                        $val_lower = strtolower($val);

                        if (isset($map[$val_lower])) {
                            if ($map[$val_lower] !== $val) { 
                                $conflicts[] = [
                                    'key' => $val, 
                                    'db' => ['name'=>$map[$val_lower]], 
                                    'csv' => ['name'=>$val]
                                ];
                            } else { 
                                $skips_count++; 
                            }
                        } else {
                            $to_insert[] = ['name'=>$val];
                        }
                    }
                }
                fclose($handle);

                if (count($conflicts) > 0) {
                    $_SESSION['import_type'] = $type;
                    $_SESSION['import_inserts'] = $to_insert;
                    $_SESSION['import_conflicts'] = $conflicts;
                    $_SESSION['import_skips'] = $skips_count;
                    $show_conflict_ui = true;
                } else {
                    if ($type === 'part') {
                        $sql = "INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)";
                    } elseif ($type === 'tool') {
                        $sql = "INSERT OR IGNORE INTO tool_master (name) VALUES (?)";
                    } elseif ($type === 'location') {
                        $sql = "INSERT OR IGNORE INTO location_master (name) VALUES (?)";
                    }
                    
                    $stmtIn = $db->prepare($sql);
                    foreach ($to_insert as $item) {
                        if ($type === 'part') {
                            $stmtIn->execute([$item['part_no'], $item['name'], $item['vendor']]);
                        } else {
                            $stmtIn->execute([$item['name']]);
                        }
                    }
                    $msg = "匯入成功！新增 ".count($to_insert)." 筆，略過(重複) $skips_count 筆。";
                }
            } else { 
                $error = "檔案讀取失敗"; 
            }
        }
        
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master($curr_dept); 
        $location_list = get_location_master($curr_dept);
        require 'views/admin.php';
        break;

    // ------------------------------------------------
    // 15. 管理員：統一衝突解決 - 階段2
    // ------------------------------------------------
    case 'admin_resolve_conflict':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        
        $type = $_SESSION['import_type'] ?? 'part';
        $inserts = $_SESSION['import_inserts'] ?? [];
        $conflicts = $_SESSION['import_conflicts'] ?? [];
        $decisions = $_POST['decision'] ?? [];

        $added = 0; 
        $updated = 0; 
        $skipped = $_SESSION['import_skips'] ?? 0;

        if ($type === 'part') {
            $sqlIn = "INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)";
        } elseif ($type === 'tool') {
            $sqlIn = "INSERT OR IGNORE INTO tool_master (name) VALUES (?)";
        } elseif ($type === 'location') {
            $sqlIn = "INSERT OR IGNORE INTO location_master (name) VALUES (?)";
        }

        $stmtIn = $db->prepare($sqlIn);
        foreach ($inserts as $item) {
            if ($type === 'part') {
                $stmtIn->execute([$item['part_no'], $item['name'], $item['vendor']]);
            } else {
                $stmtIn->execute([$item['name']]);
            }
            $added++;
        }

        if ($type === 'part') {
            $stmtUpd = $db->prepare("UPDATE part_master SET name = ?, vendor = ? WHERE part_no = ?");
            
            foreach ($conflicts as $idx => $c) {
                $key = $c['key']; 
                $decision = $decisions[$idx] ?? 'db'; 
                
                if ($decision === 'csv') {
                    $stmtUpd->execute([$c['csv']['name'], $c['csv']['vendor'], $key]);
                    $updated++;
                } else { 
                    $skipped++; 
                }
            }
        } else {
            $stmtDel = $db->prepare("DELETE FROM " . ($type=='tool'?'tool_master':'location_master') . " WHERE name = ?");
            $stmtAdd = $db->prepare($sqlIn); 

            foreach ($conflicts as $idx => $c) {
                $key = $c['key']; 
                $decision = $decisions[$idx] ?? 'db';
                
                if ($decision === 'csv') {
                    $stmtDel->execute([$c['db']['name']]); 
                    $stmtAdd->execute([$c['csv']['name']]); 
                    $updated++;
                } else { 
                    $skipped++; 
                }
            }
        }

        unset($_SESSION['import_type'], $_SESSION['import_inserts'], $_SESSION['import_conflicts'], $_SESSION['import_skips']);
        $msg = "處理完成：新增 $added 筆，更新 $updated 筆，略過 $skipped 筆。";

        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();
        $tool_list = get_tool_master($curr_dept); 
        $location_list = get_location_master($curr_dept);
        require 'views/admin.php';
        break;

    // ------------------------------------------------
    // 16. 下載範本
    // ------------------------------------------------
    case 'download_template':
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="part_master_template.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['PartNo', 'Name', 'Vendor'], ",", "\"", "\\");
        fputcsv($output, ['PN-EXAMPLE-01', 'Example Part', 'ASML'], ",", "\"", "\\");
        fclose($output);
        exit;
        break;

    // ------------------------------------------------
    // 17. 待補登清單
    // ------------------------------------------------
    case 'ipart_pending':
        $pending = [];
        foreach (DEPARTMENTS as $dept) {
            $db = get_db($dept);
            $stmt = $db->query("SELECT *, '$dept' as dept_source FROM part_lifecycle WHERE status='ON' AND ipart_logged=0");
            $pending = array_merge($pending, $stmt->fetchAll());
        }
        require 'views/pending_list.php';
        break;

    // ------------------------------------------------
    // 18. API 完成補登
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
    // 19. 管理員頁面
    // ------------------------------------------------
    case 'admin':
        $curr_dept = $_SESSION['user_id'];
        $db = get_db($curr_dept);
        $stmt = $db->query("SELECT * FROM part_lifecycle ORDER BY id DESC LIMIT 100");
        $my_records = $stmt->fetchAll();

        $tool_list = get_tool_master($curr_dept); 
        $location_list = get_location_master($curr_dept);

        require 'views/admin.php';
        break;

    default:
        header('Location: index.php?route=dashboard');
        exit;
}
?>