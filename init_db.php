<?php
// init_db.php

$departments = [
    'LT3_EQ1', 'LT3_EQ2', 'LT3_EQ3', 'LT3_EQ4', 'LT3_EQ5', 
    'LT4_EQ1', 'LT4_EQ2', 'LT4_EQ3'
];

try {
    $globalDb = new PDO('sqlite:global.db');
    $globalDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 原有的 Part Master
    $globalDb->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");
    
    // ★ 新增：機台主檔 (Tool Master)
    $globalDb->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
    
    // ★ 新增：儲存位置主檔 (Location Master)
    $globalDb->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");

    echo "[Global DB] 初始化完成 (含 Tool/Location Master)<br>";
} catch (PDOException $e) {
    echo "Global DB Error: " . $e->getMessage();
}

// ... (下方各部門 DB 初始化程式碼維持不變) ...
foreach ($departments as $dept) {
    $dbFile = "{$dept}.db";
    try {
        $db = new PDO("sqlite:{$dbFile}");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = "
            CREATE TABLE IF NOT EXISTS part_lifecycle (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                dept TEXT,
                status TEXT,
                part_no TEXT,
                part_name TEXT,
                vendor TEXT,
                sn TEXT,
                location TEXT,
                ipart_logged INTEGER DEFAULT 0,
                remark TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $db->exec($sql);
        echo "[{$dept}] DB 初始化完成<br>";
    } catch (PDOException $e) {
        echo "[{$dept}] Error: " . $e->getMessage() . "<br>";
    }
}
?>