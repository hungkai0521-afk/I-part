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

// ★ 修正：多選取 p1.id，供批次退料使用
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

// 趨勢計算函式 (維持不變)
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