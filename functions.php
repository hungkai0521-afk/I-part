<?php
// functions.php

// 定義所有部門清單
define('DEPARTMENTS', [
    'LT3_EQ1', 'LT3_EQ2', 'LT3_EQ3', 'LT3_EQ4', 'LT3_EQ5', 
    'LT4_EQ1', 'LT4_EQ2', 'LT4_EQ3'
]);

// 取得部門專屬 DB 連線
function get_db(string $db_name): PDO {
    $pdo = new PDO("sqlite:{$db_name}.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

// CSV 編碼處理函式
function clean_csv_value($val) {
    if (!$val) {
        return '';
    }
    $encoding = mb_detect_encoding($val, ['UTF-8', 'BIG5', 'GBK'], true);
    if ($encoding != 'UTF-8') {
        $val = mb_convert_encoding($val, 'UTF-8', $encoding);
    }
    return trim($val);
}

// ==========================================
// 部門獨立主檔操作
// ==========================================

function get_part_master($dept): array {
    try {
        $db = get_db($dept);
        $db->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");
        return $db->query("SELECT * FROM part_master ORDER BY part_no")->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function sync_part_master($dept, $part_no, $name, $vendor) {
    if (empty($part_no)) {
        return;
    }
    try {
        $db = get_db($dept);
        $stmt = $db->prepare("INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
        $stmt->execute([$part_no, $name, $vendor]);
    } catch (Exception $e) {
        // 忽略錯誤
    }
}

function get_tool_master($dept): array {
    try {
        $db = get_db($dept);
        $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
        return $db->query("SELECT name FROM tool_master ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

function add_tool_master($dept, $name) {
    if (empty($name)) {
        return;
    }
    try {
        $db = get_db($dept);
        $stmt = $db->prepare("INSERT OR IGNORE INTO tool_master (name) VALUES (?)");
        $stmt->execute([trim($name)]);
    } catch (Exception $e) {
        // 忽略錯誤
    }
}

function get_location_master($dept): array {
    try {
        $db = get_db($dept);
        $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");
        return $db->query("SELECT name FROM location_master ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

function add_location_master($dept, $name) {
    if (empty($name)) {
        return;
    }
    try {
        $db = get_db($dept);
        $stmt = $db->prepare("INSERT OR IGNORE INTO location_master (name) VALUES (?)");
        $stmt->execute([trim($name)]);
    } catch (Exception $e) {
        // 忽略錯誤
    }
}

function delete_master_item($dept, $table, $name) {
    try {
        $db = get_db($dept);
        if (in_array($table, ['tool_master', 'location_master'])) {
            $stmt = $db->prepare("DELETE FROM $table WHERE name = ?");
            $stmt->execute([$name]);
        }
    } catch (Exception $e) {
        // 忽略錯誤
    }
}

// ==========================================
// 查詢與報表功能
// ==========================================

function get_current_inventory($dept): array {
    $db = get_db($dept);
    $sql = "SELECT p1.* FROM part_lifecycle p1 INNER JOIN (SELECT part_no, sn, MAX(id) as max_id FROM part_lifecycle GROUP BY part_no, sn) p2 ON p1.id = p2.max_id WHERE p1.status = 'IN' ORDER BY p1.part_no";
    return $db->query($sql)->fetchAll();
}

function get_mounted_parts($dept): array {
    $db = get_db($dept);
    $sql = "SELECT p1.* FROM part_lifecycle p1 INNER JOIN (SELECT part_no, sn, MAX(id) as max_id FROM part_lifecycle GROUP BY part_no, sn) p2 ON p1.id = p2.max_id WHERE p1.status = 'ON' ORDER BY p1.location, p1.part_no";
    return $db->query($sql)->fetchAll();
}

function get_logs_by_date($dept, $start, $end): array {
    $db = get_db($dept);
    $stmt = $db->prepare("SELECT * FROM part_lifecycle WHERE date(created_at, 'localtime') BETWEEN ? AND ? ORDER BY id DESC");
    $stmt->execute([$start, $end]);
    return $stmt->fetchAll();
}

function get_csv_lifecycle_data($dept, $start, $end): array {
    $db = get_db($dept);
    $stmt = $db->prepare("SELECT DISTINCT part_no, sn, part_name FROM part_lifecycle WHERE date(created_at, 'localtime') BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $targets = $stmt->fetchAll();
    
    $result = [];
    $stmtCycle = $db->prepare("SELECT status, created_at, category, location FROM part_lifecycle WHERE part_no=? AND sn=? ORDER BY id ASC");
    
    foreach ($targets as $t) {
        $stmtCycle->execute([$t['part_no'], $t['sn']]);
        $history = $stmtCycle->fetchAll();
        
        $row = [
            'part_no'  => $t['part_no'],
            'name'     => $t['part_name'],
            'sn'       => $t['sn'],
            'category' => '',
            'machine'  => '',
            'in_date'  => '',
            'on_date'  => '',
            'out_date' => ''
        ];

        foreach ($history as $h) {
            if ($h['status'] === 'IN') {
                $row['in_date'] = $h['created_at'];
            }
            if ($h['status'] === 'ON') {
                $row['on_date'] = $h['created_at'];
                $row['machine'] = $h['location'];
                if (!empty($h['category'])) {
                    $row['category'] = $h['category'];
                }
            }
            if ($h['status'] === 'OUT') {
                $row['out_date'] = $h['created_at'];
            }
            if (empty($row['category']) && !empty($h['category'])) {
                $row['category'] = $h['category'];
            }
        }
        $result[] = $row;
    }
    return $result;
}

// ★ 修改：完整記錄每個時間點的「部門詳細數據 (details)」
function get_trend_data($target_dept, $period_type, $category = 'ALL'): array {
    $scan_list = ($target_dept === 'ALL') ? DEPARTMENTS : [$target_dept];
    $labels = []; 
    $rates = []; 
    $raw = []; 
    $today = new DateTime();
    
    // 建立分類篩選 SQL
    $catSql = "";
    if ($category !== 'ALL') {
        $catSql = " AND category = '$category'";
    }

    // 1. 日趨勢 (Daily)
    if ($period_type === 'daily') {
        for ($i = 6; $i >= 0; $i--) {
            $dt = (clone $today)->modify("-{$i} days");
            $dateStr = $dt->format('Y-m-d');
            $labels[] = $dt->format('m/d');
            
            $total_on = 0; 
            $total_logged = 0; 
            $details = []; // 儲存該日每個部門的數據
            
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged 
                        FROM part_lifecycle 
                        WHERE status='ON' AND date(created_at, 'localtime') = '$dateStr' $catSql";
                $res = $db->query($sql)->fetch();
                
                $d_on = $res['total'];
                $d_log = $res['logged'] ?? 0;
                
                $total_on += $d_on; 
                $total_logged += $d_log;
                
                // ★ 關鍵：將此部門數據存入 details
                $details[] = ['dept' => $dept, 'on' => $d_on, 'logged' => $d_log];
            }
            
            if ($total_on > 0) {
                $rates[] = round(($total_logged / $total_on * 100), 1);
            } else {
                $rates[] = 0;
            }
            
            // ★ 將 details 存入 raw
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'details' => $details];
        }
    } 
    // 2. 週趨勢 (Weekly)
    elseif ($period_type === 'weekly') {
        for ($i = 3; $i >= 0; $i--) {
            $dt = new DateTime(); 
            $dt->modify("-{$i} week");
            $labels[] = "W" . $dt->format('W');
            
            $s = $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'), 1)->format('Y-m-d');
            $e = $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'), 7)->format('Y-m-d');
            
            $total_on = 0; 
            $total_logged = 0; 
            $details = [];
            
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged 
                        FROM part_lifecycle 
                        WHERE status='ON' AND date(created_at, 'localtime') BETWEEN '$s' AND '$e' $catSql";
                $res = $db->query($sql)->fetch();
                
                $d_on = $res['total'];
                $d_log = $res['logged'] ?? 0;
                
                $total_on += $d_on; 
                $total_logged += $d_log;
                
                $details[] = ['dept' => $dept, 'on' => $d_on, 'logged' => $d_log];
            }
            
            if ($total_on > 0) {
                $rates[] = round(($total_logged / $total_on * 100), 1);
            } else {
                $rates[] = 0;
            }
            
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'details' => $details];
        }
    } 
    // 3. 月趨勢 (Monthly)
    elseif ($period_type === 'monthly') {
        for ($i = 2; $i >= 0; $i--) {
            $dt = (clone $today)->modify("-{$i} months");
            $monthStr = $dt->format('Y-m');
            $labels[] = $monthStr;
            
            $total_on = 0; 
            $total_logged = 0; 
            $details = [];
            
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged 
                        FROM part_lifecycle 
                        WHERE status='ON' AND strftime('%Y-%m', created_at, 'localtime') = '$monthStr' $catSql";
                $res = $db->query($sql)->fetch();
                
                $d_on = $res['total'];
                $d_log = $res['logged'] ?? 0;
                
                $total_on += $d_on; 
                $total_logged += $d_log;
                
                $details[] = ['dept' => $dept, 'on' => $d_on, 'logged' => $d_log];
            }
            
            if ($total_on > 0) {
                $rates[] = round(($total_logged / $total_on * 100), 1);
            } else {
                $rates[] = 0;
            }
            
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'details' => $details];
        }
    }
    
    return [
        'labels' => $labels, 
        'rates' => $rates, 
        'raw' => $raw
    ];
}
?>