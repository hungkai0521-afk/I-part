<?php
// functions.php

define('DEPARTMENTS', [
    'LT3_EQ1', 'LT3_EQ2', 'LT3_EQ3', 'LT3_EQ4', 'LT3_EQ5', 
    'LT4_EQ1', 'LT4_EQ2', 'LT4_EQ3'
]);

function get_db(string $db_name): PDO {
    $pdo = new PDO("sqlite:{$db_name}.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function get_global_db(): PDO {
    $pdo = new PDO("sqlite:global.db");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

// --- Part Master (料號主檔) ---
function get_part_master(): array {
    try {
        $db = get_global_db();
        return $db->query("SELECT * FROM part_master ORDER BY part_no")->fetchAll();
    } catch (Exception $e) { return []; }
}

function sync_part_master($part_no, $name, $vendor) {
    if (empty($part_no)) return;
    try {
        $db = get_global_db();
        $stmt = $db->prepare("INSERT OR IGNORE INTO part_master (part_no, name, vendor) VALUES (?, ?, ?)");
        $stmt->execute([$part_no, $name, $vendor]);
    } catch (Exception $e) {}
}

// --- ★ 新增：Tool Master (機台主檔) ---
function get_tool_master(): array {
    try {
        $db = get_global_db();
        // 確保 table 存在 (防呆)
        $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
        return $db->query("SELECT name FROM tool_master ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { return []; }
}

function add_tool_master($name) {
    if (empty($name)) return;
    try {
        $db = get_global_db();
        $stmt = $db->prepare("INSERT OR IGNORE INTO tool_master (name) VALUES (?)");
        $stmt->execute([trim($name)]);
    } catch (Exception $e) {}
}

// --- ★ 新增：Location Master (儲存位置主檔) ---
function get_location_master(): array {
    try {
        $db = get_global_db();
        // 確保 table 存在 (防呆)
        $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");
        return $db->query("SELECT name FROM location_master ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) { return []; }
}

function add_location_master($name) {
    if (empty($name)) return;
    try {
        $db = get_global_db();
        $stmt = $db->prepare("INSERT OR IGNORE INTO location_master (name) VALUES (?)");
        $stmt->execute([trim($name)]);
    } catch (Exception $e) {}
}

// --- ★ 新增：通用刪除 (管理員用) ---
function delete_master_item($table, $name) {
    try {
        $db = get_global_db();
        if (!in_array($table, ['tool_master', 'location_master'])) return;
        $stmt = $db->prepare("DELETE FROM $table WHERE name = ?");
        $stmt->execute([$name]);
    } catch (Exception $e) {}
}

// --- 庫存與上機清單 ---
function get_current_inventory(string $dept): array {
    $db = get_db($dept);
    $sql = "
        SELECT p1.part_no, p1.part_name, p1.vendor, p1.sn, p1.location, p1.created_at
        FROM part_lifecycle p1
        INNER JOIN (
            SELECT part_no, sn, MAX(id) as max_id
            FROM part_lifecycle
            GROUP BY part_no, sn
        ) p2 ON p1.id = p2.max_id
        WHERE p1.status = 'IN'
        ORDER BY p1.part_no
    ";
    return $db->query($sql)->fetchAll();
}

function get_mounted_parts(string $dept): array {
    $db = get_db($dept);
    $sql = "
        SELECT p1.id, p1.part_no, p1.part_name, p1.vendor, p1.sn, p1.location, p1.created_at
        FROM part_lifecycle p1
        INNER JOIN (
            SELECT part_no, sn, MAX(id) as max_id
            FROM part_lifecycle
            GROUP BY part_no, sn
        ) p2 ON p1.id = p2.max_id
        WHERE p1.status = 'ON'
        ORDER BY p1.location, p1.part_no
    ";
    return $db->query($sql)->fetchAll();
}

// --- ★ 新增：依日期範圍取得流水帳 (用於作業中心篩選) ---
function get_logs_by_date(string $dept, string $start_date, string $end_date): array {
    $db = get_db($dept);
    $sql = "
        SELECT * FROM part_lifecycle 
        WHERE date(created_at, 'localtime') BETWEEN ? AND ? 
        ORDER BY id DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    return $stmt->fetchAll();
}

// --- ★ 新增：CSV 匯出專用邏輯 (整合 IN/ON/OUT) ---
// functions.php

// --- ★ 修改：CSV 匯出專用邏輯 (新增 S/N 與 Tool ID) ---
function get_csv_lifecycle_data(string $dept, string $start_date, string $end_date): array {
    $db = get_db($dept);
    
    // 1. 找出區間內有活動的零件
    $sqlFindTargets = "
        SELECT DISTINCT part_no, sn, part_name
        FROM part_lifecycle 
        WHERE date(created_at, 'localtime') BETWEEN ? AND ?
    ";
    $stmt = $db->prepare($sqlFindTargets);
    $stmt->execute([$start_date, $end_date]);
    $targets = $stmt->fetchAll();

    $result = [];
    // ★ 修改點：多撈取 location 欄位，因為要抓機台編號
    $sqlLifecycle = "SELECT status, location, created_at FROM part_lifecycle WHERE part_no = ? AND sn = ? ORDER BY id ASC";
    $stmtCycle = $db->prepare($sqlLifecycle);

    foreach ($targets as $t) {
        $stmtCycle->execute([$t['part_no'], $t['sn']]);
        $history = $stmtCycle->fetchAll();

        $row = [
            'part_no' => $t['part_no'],
            'name'    => $t['part_name'],
            'sn'      => $t['sn'],        // ★ 新增：S/N
            'tool_id' => '',              // ★ 新增：機台 ID (預設空)
            'in_date' => '',
            'on_date' => '',
            'out_date'=> ''
        ];

        foreach ($history as $h) {
            if ($h['status'] === 'IN')  $row['in_date']  = $h['created_at'];
            if ($h['status'] === 'ON') {
                $row['on_date'] = $h['created_at'];
                // ★ 關鍵：當狀態是 ON 時，location 欄位存的就是 Tool ID
                $row['tool_id'] = $h['location']; 
            }
            if ($h['status'] === 'OUT') $row['out_date'] = $h['created_at'];
        }
        $result[] = $row;
    }
    return $result;
}

// --- 趨勢圖計算 ---
function get_trend_data(string $target_dept, string $period_type): array {
    $scan_list = ($target_dept === 'ALL') ? DEPARTMENTS : [$target_dept];
    $labels = [];
    $rates = [];
    $raw = []; 
    $today = new DateTime();

    if ($period_type === 'daily') {
        for ($i = 6; $i >= 0; $i--) {
            $dt = (clone $today)->modify("-{$i} days");
            $dateStr = $dt->format('Y-m-d');
            $labels[] = $dt->format('m/d');

            $total_on = 0; $total_logged = 0; $missing = [];
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged FROM part_lifecycle WHERE status='ON' AND date(created_at, 'localtime') = ?");
                $stmt->execute([$dateStr]);
                $res = $stmt->fetch();
                $d_on = $res['total']; $d_logged = $res['logged'] ?? 0;
                if ($d_on > $d_logged) {
                    $diff = $d_on - $d_logged;
                    $missing[] = "$dept: -$diff";
                }
                $total_on += $d_on; $total_logged += $d_logged;
            }
            $rates[] = ($total_on > 0) ? round(($total_logged / $total_on * 100), 1) : 0;
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'missing' => $missing];
        }
    } elseif ($period_type === 'weekly') {
        for ($i = 3; $i >= 0; $i--) {
            $dt = new DateTime();
            $dt->modify("-{$i} week");
            $labels[] = "W" . $dt->format('W'); 
            $startStr = $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'), 1)->format('Y-m-d');
            $endStr = $dt->setISODate((int)$dt->format('o'), (int)$dt->format('W'), 7)->format('Y-m-d');
            
            $total_on = 0; $total_logged = 0; $missing = [];
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged FROM part_lifecycle WHERE status='ON' AND date(created_at, 'localtime') BETWEEN ? AND ?");
                $stmt->execute([$startStr, $endStr]);
                $res = $stmt->fetch();
                $d_on = $res['total']; $d_logged = $res['logged'] ?? 0;
                if ($d_on > $d_logged) {
                    $diff = $d_on - $d_logged;
                    $missing[] = "$dept: -$diff";
                }
                $total_on += $d_on; $total_logged += $d_logged;
            }
            $rates[] = ($total_on > 0) ? round(($total_logged / $total_on * 100), 1) : 0;
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'missing' => $missing];
        }
    } elseif ($period_type === 'monthly') {
        for ($i = 2; $i >= 0; $i--) {
            $dt = (clone $today)->modify("-{$i} months");
            $monthStr = $dt->format('Y-m');
            $labels[] = $monthStr;
            
            $total_on = 0; $total_logged = 0; $missing = [];
            foreach ($scan_list as $dept) {
                $db = get_db($dept);
                $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN ipart_logged=1 THEN 1 ELSE 0 END) as logged FROM part_lifecycle WHERE status='ON' AND strftime('%Y-%m', created_at, 'localtime') = ?");
                $stmt->execute([$monthStr]);
                $res = $stmt->fetch();
                $d_on = $res['total']; $d_logged = $res['logged'] ?? 0;
                if ($d_on > $d_logged) {
                    $diff = $d_on - $d_logged;
                    $missing[] = "$dept: -$diff";
                }
                $total_on += $d_on; $total_logged += $d_logged;
            }
            $rates[] = ($total_on > 0) ? round(($total_logged / $total_on * 100), 1) : 0;
            $raw[] = ['logged' => $total_logged, 'on' => $total_on, 'missing' => $missing];
        }
    }

    return ['labels' => $labels, 'rates' => $rates, 'raw' => $raw];
}
?>