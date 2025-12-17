<?php
// seed_data.php - 獨立運行的系統初始化工具 (CSV 標題中文化版)
session_start();
date_default_timezone_set('Asia/Taipei'); 
require_once 'functions.php';

// 權限檢查：未登入或 Guest 禁止進入
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] === 'Guest') {
    header('Location: index.php');
    exit;
}

$curr_dept = $_SESSION['user_id'];
$msg = '';
$error = '';

// ====================================================
// 邏輯處理區 (Handle Logic)
// ====================================================

// 1. 下載範本 (★ 修改重點：標題中文化與說明)
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    
    if ($type === 'history') {
        $filename = "History_Import_Template.csv";
        // ★ 修改：加入中文與格式說明
        $header = [
            '狀態 (Status: IN/ON/OUT)*', 
            '發生時間 (Date: YYYY-MM-DD HH:MM)*', 
            '料號 (Part No)*', 
            '序號 (S/N)', 
            '位置/機台 (Location/Tool)', 
            '品名 (Name)', 
            '廠商 (Vendor)', 
            '分類 (Category)*', 
            'iPart登錄 (1=是 0=否)*', 
            '備註 (Remark)'
        ];
        $rows = [
            ['IN',  '2025-10-01 09:00', 'PN-SCREW-01', '', 'A-01-01', 'Screw Set', 'VendorA', 'Consumables Part', '0', '期初耗材'],
            ['ON',  '2025-10-05 14:00', 'PN-LENS-99', 'SN-A001', 'L3-EQ1',  'Main Lens', 'ASML', 'Tool Part', '1', '正常更換']
        ];
    } else {
        $filename = "KPI_Stats_Template.csv";
        // ★ 修改：加入中文與說明
        $header = [
            '日期 (Date: YYYY-MM-DD)', 
            '分類 (Category)', 
            '上機總數 (Total ON Count)', 
            '已登錄數 (iPart Logged Count)'
        ];
        $rows = [
            ['2025-11-01', 'Tool Part', '10', '8'],
            ['2025-11-02', 'Consumables Part', '5', '5']
        ];
    }
    
    // 清除緩衝區並輸出 CSV
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $fp = fopen('php://output', 'w');
    fwrite($fp, "\xEF\xBB\xBF"); // BOM 防止亂碼
    fputcsv($fp, $header, ",", "\"", "\\");
    foreach ($rows as $r) { fputcsv($fp, $r, ",", "\"", "\\"); }
    fclose($fp);
    exit;
}

// 2. 處理匯入
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $mode = $_POST['mode'] ?? '';
    $file = $_FILES['csv_file']['tmp_name'];
    
    if (($handle = fopen($file, "r")) !== FALSE) {
        $db = get_db($curr_dept);
        $count = 0;
        
        $sql = "INSERT INTO part_lifecycle 
                (dept, status, created_at, part_no, sn, location, part_name, vendor, category, ipart_logged, remark) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $row_idx = 0;
        while (($data = fgetcsv($handle, 1000, ",", "\"", "\\")) !== FALSE) {
            $row_idx++;
            if ($row_idx == 1) continue; // Skip header

            try {
                if ($mode === 'history') {
                    // Mode A: 詳細匯入
                    $status = strtoupper(clean_csv_value($data[0]??''));
                    $dateStr = clean_csv_value($data[1]??'');
                    $part_no = clean_csv_value($data[2]??'');
                    $sn = clean_csv_value($data[3]??'') ?: '-';
                    $loc = clean_csv_value($data[4]??'');
                    $name = clean_csv_value($data[5]??'');
                    $vendor = clean_csv_value($data[6]??'');
                    $cat = clean_csv_value($data[7]??'');
                    $ipart = (clean_csv_value($data[8]??'') == '1') ? 1 : 0;
                    $remark = clean_csv_value($data[9]??'');

                    if (!$status || !$dateStr || !$part_no || !$cat) continue;

                    $ts = strtotime($dateStr);
                    $created_at = ($ts === false) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', $ts);

                    $stmt->execute([$curr_dept, $status, $created_at, $part_no, $sn, $loc, $name, $vendor, $cat, $ipart, $remark]);
                    $count++;
                    
                    // 同步主檔
                    if ($name || $vendor) sync_part_master($curr_dept, $part_no, $name, $vendor);
                    if ($loc) {
                        if ($status == 'ON') add_tool_master($curr_dept, $loc);
                        if ($status == 'IN') add_location_master($curr_dept, $loc);
                    }

                } elseif ($mode === 'stats') {
                    // Mode B: KPI 統計
                    $dateStr = clean_csv_value($data[0]??'');
                    $cat = clean_csv_value($data[1]??'');
                    $total = (int)clean_csv_value($data[2]??0);
                    $logged = (int)clean_csv_value($data[3]??0);

                    if (!$dateStr || $total <= 0) continue;

                    $ts = strtotime($dateStr);
                    $created_at = ($ts === false) ? date('Y-m-d 12:00:00') : date('Y-m-d 12:00:00', $ts);

                    for ($i = 1; $i <= $total; $i++) {
                        $is_logged = ($i <= $logged) ? 1 : 0;
                        $dummy_pn = "KPI-" . strtoupper(substr($cat, 0, 3));
                        $dummy_sn = "AUTO-" . date('ymd') . "-" . $row_idx . "-" . $i;
                        
                        $stmt->execute([
                            $curr_dept, 'ON', $created_at, $dummy_pn, $dummy_sn, 
                            'VIRTUAL', 'KPI Data', 'System', $cat, $is_logged, 'KPI快速匯入'
                        ]);
                        $count++;
                    }
                }
            } catch (Exception $e) { }
        }
        fclose($handle);
        $msg = "匯入成功：共新增 {$count} 筆資料。";
    } else {
        $error = "檔案讀取失敗";
    }
}

// ====================================================
// 介面渲染區 (Render View)
// ====================================================
ob_start(); 
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3><i class="fas fa-database me-2 text-warning"></i>系統初始化 (Seed Data)</h3>
        <p class="text-muted mb-0">歷史數據建立與 KPI 快速匯入中心 (獨立作業)</p>
    </div>
    <a href="index.php?route=admin" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> 返回管理員頁面
    </a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success border-success shadow-sm mb-4"><i class="fas fa-check-circle me-2"></i> <?= $msg ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger border-danger shadow-sm mb-4"><i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?></div>
<?php endif; ?>

<div class="card border-warning shadow-sm">
    <div class="card-header bg-warning text-dark fw-bold py-3">
        <i class="fas fa-history me-2"></i>選擇匯入模式
    </div>
    <div class="card-body">
        <div class="row">
            
            <div class="col-md-6 border-end">
                <h5 class="fw-bold text-primary mb-3"><i class="fas fa-list-ol me-2"></i>模式 A：完整歷史明細 (建議)</h5>
                <div class="alert alert-primary bg-opacity-10 border-0 small">
                    <strong>適用情境：</strong> 有過去 3~6 個月完整的 Excel 紀錄。<br>
                    <strong>優點：</strong> 資料精確，可追溯。
                </div>
                <div class="mb-3">
                    <a href="seed_data.php?download=history" class="btn btn-outline-primary w-100 btn-sm">
                        <i class="fas fa-download me-1"></i> 下載範本 A (History)
                    </a>
                </div>
                <form action="seed_data.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="history">
                    <div class="input-group mb-3">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <button type="submit" class="btn btn-primary fw-bold">匯入明細</button>
                    </div>
                </form>
                <hr>
                <h6 class="fw-bold small text-muted">欄位規範:</h6>
                <table class="table table-sm table-bordered small mb-0">
                    <thead class="table-light"><tr><th>必填 (Required)</th><th>選填 (Optional)</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="text-danger">Status, Date, PartNo, Category, iPart</td>
                            <td class="text-secondary">S/N, Location, Name, Vendor</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="col-md-6 ps-4">
                <h5 class="fw-bold text-success mb-3"><i class="fas fa-chart-pie me-2"></i>模式 B：快速 KPI 統計 (懶人包)</h5>
                <div class="alert alert-success bg-opacity-10 border-0 small">
                    <strong>適用情境：</strong> 舊資料難整理，只輸入「總數」來產生圖表。<br>
                    <strong>原理：</strong> 自動生成對應數量的「虛擬資料」。
                </div>
                <div class="mb-3">
                    <a href="seed_data.php?download=stats" class="btn btn-outline-success w-100 btn-sm">
                        <i class="fas fa-download me-1"></i> 下載範本 B (Stats)
                    </a>
                </div>
                <form action="seed_data.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="mode" value="stats">
                    <div class="input-group mb-3">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <button type="submit" class="btn btn-success fw-bold">匯入統計</button>
                    </div>
                </form>
                <hr>
                <div class="small">
                    <h6 class="fw-bold small text-muted">邏輯範例:</h6>
                    輸入：<code>Date=2025/11/01, Category=Tool, Total=10, Logged=8</code><br>
                    結果：產生 10 筆上機，其中 8 筆已登錄。<br>
                </div>
            </div>

        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
$route = 'seed_data'; 
require 'views/layout.php'; // ★ 修正路徑
?>