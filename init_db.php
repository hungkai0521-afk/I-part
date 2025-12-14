<?php
// init_db.php

$departments = [
    'LT3_EQ1', 'LT3_EQ2', 'LT3_EQ3', 'LT3_EQ4', 'LT3_EQ5', 
    'LT4_EQ1', 'LT4_EQ2', 'LT4_EQ3'
];

try {
    $globalDb = new PDO('sqlite:global.db');
} catch (PDOException $e) {
    echo "Global DB Error: " . $e->getMessage();
}

foreach ($departments as $dept) {
    $dbFile = "{$dept}.db";
    
    try {
        $db = new PDO("sqlite:{$dbFile}");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 1. 建立流水帳表
        $sqlLog = "
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
                category TEXT,  -- ★ 新增分類欄位
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $db->exec($sqlLog);

        // ★ 自動檢查並補上 category 欄位 (針對舊資料庫)
        $cols = $db->query("PRAGMA table_info(part_lifecycle)")->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('category', $cols)) {
            $db->exec("ALTER TABLE part_lifecycle ADD COLUMN category TEXT");
            echo "[{$dept}] 已自動更新資料庫結構 (新增 category 欄位)<br>";
        }

        // 2. 主檔表
        $db->exec("CREATE TABLE IF NOT EXISTS part_master (part_no TEXT PRIMARY KEY, name TEXT, vendor TEXT)");
        $db->exec("CREATE TABLE IF NOT EXISTS tool_master (name TEXT PRIMARY KEY)");
        $db->exec("CREATE TABLE IF NOT EXISTS location_master (name TEXT PRIMARY KEY)");

        echo "[{$dept}] DB 初始化/檢查完成<br>";

    } catch (PDOException $e) {
        echo "[{$dept}] Error: " . $e->getMessage() . "<br>";
    }
}
?>