<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iPart 零件管理系統 (<?= $_SESSION['user_id'] ?? 'Guest' ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    
    <style>
        body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
        .card { border-radius: 8px; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.03); }
        /* 優化 Popover 樣式 */
        .driver-popover.driverjs-theme { background-color: #ffffff; color: #333; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .driver-popover-title { font-weight: bold; font-size: 1.1rem; color: #0d6efd; margin-bottom: 8px; }
        .driver-popover-description { font-size: 0.95rem; line-height: 1.5; margin-bottom: 15px; }
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
                    <li class="nav-item me-2">
                        <button class="btn btn-sm btn-outline-warning text-warning border-0" onclick="startTour()">
                            <i class="fas fa-question-circle me-1"></i> 操作指引
                        </button>
                    </li>
                    <?php if ($_SESSION['user_id'] !== 'Guest'): ?>
                    <li class="nav-item me-2">
                        <a class="nav-link btn btn-outline-secondary text-light <?= ($route=='admin')?'active':'' ?>" href="index.php?route=admin">
                            <i class="fas fa-user-cog me-1"></i> 管理者頁面
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    
    <script>
        function startTour() {
            const driver = window.driver.js.driver;
            const currentRoute = "<?= $route ?? '' ?>";

            const driverObj = driver({
                showProgress: true,
                nextBtnText: '下一步',
                prevBtnText: '上一步',
                doneBtnText: '完成教學',
                steps: getStepsForRoute(currentRoute)
            });

            driverObj.drive();
        }

        function getStepsForRoute(route) {
            // 1. 作業中心
            if (route === 'ops' || route === 'ops_new' || route === 'ops_edit') {
                return [
                    { element: 'h3', popover: { title: '作業中心', description: '日常管理核心，負責零件的進出與流水帳。' } },
                    { element: '#guide_in', popover: { title: '1. 進料 (IN)', description: '廠商送貨或領出備品時，點此建立庫存。' } },
                    { element: '#guide_on', popover: { title: '2. 上機 (ON)', description: '安裝到機台時點此。系統會扣庫存並提醒登錄 iPart。' } },
                    { element: '#guide_out', popover: { title: '3. 退料 (OUT)', description: '故障或報廢時點此。需填寫退料原因。' } },
                    { element: '#guide_links', popover: { title: '外部連結', description: '快速前往 iPart 系統或待建料 DB。' } },
                    { element: '#guide_filter', popover: { title: '搜尋與匯出', description: '篩選特定日期紀錄，並匯出 CSV 報表。' } },
                    { element: '#guide_table', popover: { title: '流水帳列表', description: '顯示所有的異動紀錄。' } }
                ];
            }
            
            // 2. 儀表板
            if (route === 'dashboard') {
                return [
                    { element: '.input-group', popover: { title: '篩選條件', description: '切換不同部門或零件分類，即時查看 KPI 變化。' } },
                    { element: '#chartDaily', popover: { title: '日趨勢圖', description: '顯示每日的「上機數」與「iPart 登錄確實率」。點擊長條可查看詳細清單。' } },
                    { element: '#chartWeekly', popover: { title: '週/月統計', description: '從更長的時間維度，觀察部門的作業績效趨勢。' } }
                ];
            }

            // 3. 庫存明細 (優化版)
            if (route === 'inventory') {
                return [
                    { element: '#guide_inv_tabs', popover: { title: '分類切換', description: '庫存分為「一般」與「耗材」，點擊切換。' } },
                    { element: '#guide_inv_add', popover: { title: '新增進料', description: '庫存不足時可直接在此入庫。' } },
                    // 這裡改為選取 thead，而非整個 table container
                    { element: '#guide_inv_list', popover: { title: '庫存列表', description: '顯示在庫的零件。點擊表頭可排序；點擊右側綠色按鈕可快速上機。' } }
                ];
            }

            // 4. 待補登 (優化版)
            if (route === 'ipart_pending') {
                return [
                    { element: '#guide_pending_links', popover: { title: '快速補登', description: '點擊連結前往 iPart 系統補資料。' } },
                    { element: '#guide_pending_table', popover: { title: '待辦清單', description: '這張表列出「已上機但未勾選登錄」的項目。補完資料後，請務必點擊「已補登」消除紀錄。' } }
                ];
            }

            // 5. 管理者頁面 (優化版)
            if (route === 'admin') {
                return [
                    { element: '#guide_admin_tool', popover: { title: '機台管理', description: '管理下拉選單中的機台 ID 清單。' } },
                    { element: '#guide_admin_tool_form', popover: { title: '新增機台', description: '在此輸入機台名稱並按 + 新增。' } },
                    { element: '#guide_admin_loc', popover: { title: '位置管理', description: '管理儲存位置清單。' } },
                    { element: '#guide_admin_part', popover: { title: 'Part 主檔', description: '支援 CSV 匯入，自動比對衝突。' } },
                    { element: '#guide_admin_part_form', popover: { title: '匯入主檔', description: '選擇 CSV 檔案後，點擊按鈕即可批次更新料號資訊。' } },
                    { element: '#guide_admin_history_head', popover: { title: '完整歷史紀錄', description: '這是資料庫的底層紀錄。擁有最高權限，可修改或刪除任何一筆過往紀錄。' } }
                ];
            }

            return [
                { element: '.navbar', popover: { title: '導覽列', description: '切換 Dashboard、作業中心與庫存清單。' } }
            ];
        }
    </script>
</body>
</html>