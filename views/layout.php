<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iPart 零件管理系統 (<?= $_SESSION['user_id'] ?? 'Guest' ?>)</title>
    
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/driver.css"/>
    
    <style>
        body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
        .card { border-radius: 8px; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.03); }
        .fa, .fas { min-width: 1em; text-align: center; }

        /* ★ 優化：自訂導覽提示框樣式 */
        .driver-popover.driverjs-theme {
            background-color: #ffffff;
            color: #2d3748;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 15px;
        }
        .driver-popover.driverjs-theme .driver-popover-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0d6efd; /* Bootstrap Primary Blue */
            margin-bottom: 8px;
        }
        .driver-popover.driverjs-theme .driver-popover-description {
            font-size: 0.95rem;
            line-height: 1.5;
            color: #4a5568;
            margin-bottom: 15px;
        }
        .driver-popover.driverjs-theme button {
            background-color: #0d6efd;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.85rem;
            text-shadow: none;
            transition: all 0.2s;
        }
        .driver-popover.driverjs-theme button:hover {
            background-color: #0b5ed7;
        }
        .driver-popover.driverjs-theme button.driver-popover-prev-btn {
            background-color: #e2e8f0;
            color: #4a5568;
        }
        .driver-popover.driverjs-theme button.driver-popover-prev-btn:hover {
            background-color: #cbd5e0;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php?route=dashboard">
                <i class="fas fa-microchip me-2"></i>iPart System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='dashboard')?'active':'' ?>" href="index.php?route=dashboard"><i class="fas fa-chart-line me-1"></i> 儀表板</a>
                    </li>
                    <?php if ($_SESSION['user_id'] !== 'Guest'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='ops'||$route=='ops_new'||$route=='ops_edit')?'active':'' ?>" href="index.php?route=ops"><i class="fas fa-clipboard-list me-1"></i> 作業中心</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='inventory')?'active':'' ?>" href="index.php?route=inventory"><i class="fas fa-boxes me-1"></i> 庫存明細</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='ipart_pending')?'active':'' ?>" href="index.php?route=ipart_pending"><i class="fas fa-exclamation-circle me-1"></i> 待補登</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav align-items-center">
                    
                    <li class="nav-item me-3 border-end pe-3 d-none d-lg-block">
                        <span class="text-light">
                            <small class="text-muted d-block" style="line-height: 10px; font-size: 0.7rem;">Current User</small>
                            <i class="fas fa-user-circle me-1 text-warning"></i> 
                            <span class="fw-bold"><?= $_SESSION['user_id'] ?></span>
                        </span>
                    </li>

                    <li class="nav-item me-2">
                        <button class="btn btn-sm btn-outline-warning text-warning border-0" onclick="startTour()">
                            <i class="fas fa-lightbulb me-1"></i> 指引
                        </button>
                    </li>
                    
                    <?php if ($_SESSION['user_id'] !== 'Guest'): ?>
                    <li class="nav-item me-2">
                        <a class="nav-link btn btn-outline-secondary text-light <?= ($route=='admin')?'active':'' ?>" href="index.php?route=admin">
                            <i class="fas fa-user-cog me-1"></i> 管理
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="index.php?route=logout"><i class="fas fa-sign-out-alt me-1"></i> 登出</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <div class="container flex-grow-1">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </div>

    <footer class="bg-light text-center text-muted py-3 mt-4 border-top">
        <small>&copy; <?= date('Y') ?> iPart Management System. All rights reserved.</small>
    </footer>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/driver.js.iife.js"></script>
    
    <script>
        if (typeof bootstrap === 'undefined') { console.error("Bootstrap Assets Missing!"); }

        function startTour() {
            if (typeof window.driver === 'undefined') {
                alert("導覽功能無法使用 (assets 遺失)");
                return;
            }

            const driver = window.driver.js.driver;
            const currentRoute = "<?= $route ?? '' ?>";

            const driverObj = driver({
                showProgress: true,
                animate: true,
                opacity: 0.75, // 背景遮罩透明度
                
                // ★ 優化：按鈕文字中文化與加上箭頭
                nextBtnText: '下一步 ➡',
                prevBtnText: '⬅ 上一步',
                doneBtnText: '完成導覽 🎉',
                
                // 指定使用我們自訂的 CSS class
                popoverClass: 'driverjs-theme',

                steps: getStepsForRoute(currentRoute)
            });

            driverObj.drive();
        }

        function getStepsForRoute(route) {
            // 1. 作業中心
            if (route === 'ops' || route === 'ops_new' || route === 'ops_edit') {
                return [
                    { element: 'h3', popover: { title: '作業中心 (Ops Center)', description: '這裡是您日常工作的起點，負責所有零件的進出庫存管理。' } },
                    { element: '#guide_in', popover: { title: '1. 進料 (IN)', description: '當廠商送貨或您從庫房領出備品時，請點此建立庫存。<br><b>提示：</b>系統會自動記錄進料時間。' } },
                    { element: '#guide_on', popover: { title: '2. 上機 (ON)', description: '將零件安裝到機台時點此。系統會扣除庫存並轉移至機台，並提醒您是否有登錄 iPart。' } },
                    { element: '#guide_out', popover: { title: '3. 退料 (OUT)', description: '零件故障或定期更換時使用。可選擇退回庫存或報廢，並支援批次操作。' } },
                    { element: '#guide_links', popover: { title: '外部系統捷徑', description: '快速前往 iPart 官方系統或 Notes 待建料資料庫。' } },
                    { element: '#guide_filter', popover: { title: '搜尋與匯出', description: '想找上個月的紀錄？在此設定日期區間並按查詢，也可匯出 CSV 報表。' } }
                ];
            }
            
            // 2. 儀表板
            if (route === 'dashboard') {
                return [
                    { element: '.input-group', popover: { title: 'KPI 篩選器', description: '切換不同的部門或零件分類，即時查看專屬的績效指標。' } },
                    { element: '#chartDaily', popover: { title: '每日趨勢圖', description: '監控每日的「上機數量」與「iPart 登錄率」。藍線越高越好！' } },
                    { element: '#chartWeekly', popover: { title: '週/月統計', description: '長期的績效趨勢，幫助主管分析改善方向。' } },
                    { element: '.col-md-4:first-child', popover: { title: '部門達成率', description: '各部門的即時達成狀況排行榜。' } }
                ];
            }

            // 3. 庫存明細
            if (route === 'inventory') {
                return [
                    { element: '#guide_inv_tabs', popover: { title: '分類切換', description: '庫存分為三大類：「一般零件」、「耗材」與「純工具(Tool)」。點擊標籤切換檢視。' } },
                    { element: '#guide_inv_add', popover: { title: '快速進料', description: '發現庫存不足？點這裡直接進行入庫作業。' } },
                    { element: '#guide_inv_list', popover: { title: '庫存列表', description: '點擊標題可排序。最右側有「上機」按鈕，可直接對該零件進行作業。' } }
                ];
            }

            // 4. 待補登
            if (route === 'ipart_pending') {
                return [
                    { element: '#guide_pending_links', popover: { title: '快速補登入口', description: '點擊這裡開啟 iPart 系統進行補資料。' } },
                    { element: '#guide_pending_list', popover: { title: '待辦清單 (To-Do)', description: '這裡是系統自動抓出的「已上機但未勾選登錄」項目。<br>補完資料後，請務必點擊綠色的<b>「已補登」</b>按鈕消除紀錄。' } }
                ];
            }

            // 5. 管理員
            if (route === 'admin') {
                return [
                    { element: '.btn-warning', popover: { title: '系統初始化 (Seed Data)', description: '首次使用或需要匯入大量歷史數據時，請點此進入專用介面。' } },
                    { element: '#guide_admin_part', popover: { title: 'PART/Tool 主檔管理', description: '統一匯入料號與機台清單。系統會自動比對新舊資料並提示衝突。' } },
                    { element: '#guide_admin_tool', popover: { title: '機台列表維護', description: '檢視目前的機台清單，可手動新增或刪除。' } },
                    { element: '#guide_admin_loc', popover: { title: '位置管理', description: '管理儲存位置 (Location) 清單。' } }
                ];
            }

            // 6. 系統初始化 (Seed Data)
            if (route === 'seed_data') {
                return [
                    { element: '.col-md-6:first-child', popover: { title: '模式 A：詳細匯入', description: '如果您有完整的 Excel 流水帳 (含料號、序號、日期)，請使用此模式。這是最精確的方式。' } },
                    { element: '.col-md-6:last-child', popover: { title: '模式 B：快速 KPI', description: '如果您只想讓儀表板有數據，不想整理舊資料，請用此模式。系統會自動產生虛擬數據。' } },
                    { element: '.btn-outline-secondary', popover: { title: '返回', description: '作業完成後，點此返回管理員頁面。' } }
                ];
            }

            // 預設導覽
            return [
                { element: '.navbar-brand', popover: { title: '歡迎使用 iPart 管理系統', description: '這是一套專為晶圓廠設備工程師設計的零件管理工具。' } },
                { element: '.navbar-nav.me-auto', popover: { title: '功能選單', description: '在此切換儀表板、作業中心、庫存與待補登清單。' } }
            ];
        }
    </script>
</body>
</html>