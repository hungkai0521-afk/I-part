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
    if (empty($part_no)) { return; }
    try {
        $db = get_db($dept);
        $stmt = $db->prepare("INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
        $stmt->execute([$part_no, $name, $vendor]);
    } catch (Exception $e) { }
}

function get_tool_master($dept): array {
    try {
        $db = get_db($dept);
        $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
        return $db->query("SELECT name FROM tool_master ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { return []; }
}

function add_tool_master($dept, $name) {
    if (empty($name)) { return; }
    try {
        $db = get_db($dept);
        $stmt = $db->prepare("INSERT OR IGNORE INTO tool_master (name) VALUES (?)");
        $stmt->execute([trim($name)]);
    } catch (Exception $e) { }
}

function get_location_master($dept): array {
    try {
        $db = get_db($dept);
        $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");
        return $db->query("SELECT name FROM location_master ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { return []; }
}

function add_location_master($dept, $name) {
    if (empty($name)) { return; }
    try {
        $db = get_db($dept);
        $stmt = $db->prepare("INSERT OR IGNORE INTO location_master (name) VALUES (?)");
        $stmt->execute([trim($name)]);
    } catch (Exception $e) { }
}

function delete_master_item($dept, $table, $name) {
    try {
        $db = get_db($dept);
        if (in_array($table, ['tool_master', 'location_master'])) {
            $stmt = $db->prepare("DELETE FROM $table WHERE name = ?");
            $stmt->execute([$name]);
        } elseif ($table === 'part_master') {
            $stmt = $db->prepare("DELETE FROM part_master WHERE part_no = ?");
            $stmt->execute([$name]);
        }
    } catch (Exception $e) { }
}

// ==========================================
// 查詢與報表功能
// ==========================================

function get_current_inventory($dept): array {
    $db = get_db($dept);
    // 只抓目前狀態為 IN 的
    $sql = "SELECT p1.* FROM part_lifecycle p1 INNER JOIN (SELECT part_no, sn, MAX(id) as max_id FROM part_lifecycle GROUP BY part_no, sn) p2 ON p1.id = p2.max_id WHERE p1.status = 'IN' ORDER BY p1.part_no";
    return $db->query($sql)->fetchAll();
}

function get_mounted_parts($dept): array {
    $db = get_db($dept);
    // 只抓目前狀態為 ON 的
    $sql = "SELECT p1.* FROM part_lifecycle p1 INNER JOIN (SELECT part_no, sn, MAX(id) as max_id FROM part_lifecycle GROUP BY part_no, sn) p2 ON p1.id = p2.max_id WHERE p1.status = 'ON' ORDER BY p1.location, p1.part_no";
    return $db->query($sql)->fetchAll();
}

// ★ 新增：取得所有可退料項目 (IN + ON)
function get_returnable_items($dept): array {
    $db = get_db($dept);
    $sql = "SELECT p1.* FROM part_lifecycle p1 
            INNER JOIN (SELECT part_no, sn, MAX(id) as max_id FROM part_lifecycle GROUP BY part_no, sn) p2 ON p1.id = p2.max_id 
            WHERE (p1.status = 'IN' OR p1.status = 'ON') 
            ORDER BY p1.status, p1.part_no";
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
    $stmt = $db->prepare("SELECT DISTINCT part_no, sn, part_name, vendor FROM part_lifecycle WHERE date(created_at, 'localtime') BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $targets = $stmt->fetchAll();
    
    $result = [];
    $stmtCycle = $db->prepare("SELECT * FROM part_lifecycle WHERE part_no=? AND sn=? ORDER BY id ASC");
    
    foreach ($targets as $t) {
        $stmtCycle->execute([$t['part_no'], $t['sn']]);
        $history = $stmtCycle->fetchAll();
        
        $row = [
            'part_no'  => $t['part_no'],
            'name'     => $t['part_name'],
            'vendor'   => $t['vendor'], 
            'sn'       => $t['sn'],
            'category' => '',
            'machine'  => '',
            'status'   => 'Unknown',    
            'in_date'  => '',
            'on_date'  => '',
            'out_date' => '',
            'days'     => '',           
            'remark'   => ''            
        ];

        foreach ($history as $h) {
            if (!empty($h['remark'])) { $row['remark'] = $h['remark']; }
            if (empty($row['category']) && !empty($h['category'])) { $row['category'] = $h['category']; }

            if ($h['status'] === 'IN') {
                $row['in_date'] = substr($h['created_at'], 0, 16);
                $row['status'] = '庫存 (IN)';
            }
            if ($h['status'] === 'ON') {
                $row['on_date'] = substr($h['created_at'], 0, 16);
                $row['machine'] = $h['location'];
                $row['status'] = '上機 (ON)';
                if (!empty($h['category'])) { $row['category'] = $h['category']; }
            }
            if ($h['status'] === 'OUT') {
                $row['out_date'] = substr($h['created_at'], 0, 16);
                $row['status'] = '退料 (OUT)';
            }
        }

        if (!empty($row['on_date'])) {
            $start_dt = new DateTime($row['on_date']);
            if (!empty($row['out_date'])) {
                $end_dt = new DateTime($row['out_date']);
                $diff = $start_dt->diff($end_dt);
                $row['days'] = $diff->days;
            } else {
                $now = new DateTime();
                $diff = $start_dt->diff($now);
                $row['days'] = $diff->days . " (Running)";
            }
        }
        $result[] = $row;
    }
    return $result;
}

function get_trend_data($target_dept, $period_type, $category = 'ALL'): array {
    $scan_list = ($target_dept === 'ALL') ? DEPARTMENTS : [$target_dept];
    $labels = []; $rates = []; $raw = []; 
    $today = new DateTime();
    
    $catSql = "";
    if ($category !== 'ALL') { $catSql = " AND category = '$category'"; }

    if ($period_type === 'daily') {
        for ($i = 6; $i >= 0; $i--) {
            $dt = (clone $today)->modify("-{$i} days");
            $dateStr = $dt->format('Y-m-d');
            $labels[] = $dt->format('m/d');
            
            $total_on = 0; $total_logged = 0; $details = [];
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged 
                        FROM part_lifecycle WHERE status='ON' AND date(created_at, 'localtime') = '$dateStr' $catSql";
                $res = $db->query($sql)->fetch();
                $d_on = $res['total']; $d_log = $res['logged'] ?? 0;
                $total_on += $d_on; $total_logged += $d_log;
                $details[] = ['dept' => $dept, 'on' => $d_on, 'logged' => $d_log];
            }
            $rates[] = ($total_on > 0) ? round(($total_logged / $total_on * 100), 1) : 0;
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'details' => $details];
        }
    } elseif ($period_type === 'weekly') {
        for ($i = 3; $i >= 0; $i--) {
            $dt = new DateTime(); $dt->modify("-{$i} week");
            $labels[] = "W" . $dt->format('W');
            $s = $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'), 1)->format('Y-m-d');
            $e = $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'), 7)->format('Y-m-d');
            
            $total_on = 0; $total_logged = 0; $details = [];
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged 
                        FROM part_lifecycle WHERE status='ON' AND date(created_at, 'localtime') BETWEEN '$s' AND '$e' $catSql";
                $res = $db->query($sql)->fetch();
                $d_on = $res['total']; $d_log = $res['logged'] ?? 0;
                $total_on += $d_on; $total_logged += $d_log;
                $details[] = ['dept' => $dept, 'on' => $d_on, 'logged' => $d_log];
            }
            $rates[] = ($total_on > 0) ? round(($total_logged / $total_on * 100), 1) : 0;
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'details' => $details];
        }
    } elseif ($period_type === 'monthly') {
        for ($i = 2; $i >= 0; $i--) {
            $dt = (clone $today)->modify("-{$i} months");
            $monthStr = $dt->format('Y-m');
            $labels[] = $monthStr;
            $total_on = 0; $total_logged = 0; $details = [];
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged 
                        FROM part_lifecycle WHERE status='ON' AND strftime('%Y-%m', created_at, 'localtime') = '$monthStr' $catSql";
                $res = $db->query($sql)->fetch();
                $d_on = $res['total']; $d_log = $res['logged'] ?? 0;
                $total_on += $d_on; $total_logged += $d_log;
                $details[] = ['dept' => $dept, 'on' => $d_on, 'logged' => $d_log];
            }
            $rates[] = ($total_on > 0) ? round(($total_logged / $total_on * 100), 1) : 0;
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'details' => $details];
        }
    }
    return ['labels' => $labels, 'rates' => $rates, 'raw' => $raw];
}
?>