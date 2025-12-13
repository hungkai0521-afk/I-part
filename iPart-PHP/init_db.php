<?php
// init_db.php

// 部門清單 (已新增 LT3_EQ4, LT3_EQ5, LT4_EQ3)
$departments = [
    'LT3_EQ1', 'LT3_EQ2', 'LT3_EQ3', 'LT3_EQ4', 'LT3_EQ5', 
    'LT4_EQ1', 'LT4_EQ2', 'LT4_EQ3'
];

// 1. 建立 Global DB
try {
    $globalDb = new PDO('sqlite:global.db');
    $globalDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $globalDb->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");
    echo "[Global DB] 初始化完成<br>";
} catch (PDOException $e) {
    echo "Global DB Error: " . $e->getMessage();
}

// 2. 建立各部門 DB
foreach ($departments as $dept) {
    $dbFile = "{$dept}.db";
    // if (file_exists($dbFile)) unlink($dbFile); // 開發時若需重置可打開此行

    try {
        $db = new PDO("sqlite:{$dbFile}");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "
            CREATE TABLE IF NOT EXISTS part_lifecycle (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                dept TEXT,
                status TEXT, -- IN, ON, OUT
                part_no TEXT,
                part_name TEXT,
                vendor TEXT,
                sn TEXT,
                location TEXT,
                ipart_logged INTEGER DEFAULT 0, -- 0:No, 1:Yes
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