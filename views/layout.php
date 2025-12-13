<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>i-Parts Hub (Offline)</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; overflow-x: hidden; background-color: #f1f5f9; }
        .sidebar { min-height: 100vh; background-color: #0f172a; width: 260px; position: fixed; }
        .sidebar .nav-link { color: #94a3b8; padding: 15px 20px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #fff; background: #1e293b; border-left: 4px solid #3b82f6; }
        .main-content { margin-left: 260px; padding: 30px; min-height: 100vh; }
        .card { border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }

        /* --- ★ 新增：低飽和度表單修飾樣式 ★ --- */
        
        /* 1. 輸入框與下拉選單基礎樣式 */
        .form-control, .form-select, .input-group-text {
            border: 1px solid #94a3b8; /* 使用較深的低飽和藍灰色邊框 */
            background-color: #f8fafc; /* 極淺的冷灰色背景，增加實體感 */
            color: #334155; /* 稍微柔和的深色文字 */
            transition: all 0.2s ease-in-out;
        }

        /* 2. 輸入框焦點狀態 (滑鼠點擊時) */
        .form-control:focus, .form-select:focus {
            background-color: #fff; /* 聚焦時變回純白背景，提升輸入清晰度 */
            border-color: #64748b; /* 邊框變得更深一點 */
            /* 使用低飽和度的柔和光暈，取代原本刺眼的亮藍色 */
            box-shadow: 0 0 0 0.25rem rgba(100, 116, 139, 0.2);
        }

        /* 3. Checkbox 與 Radio 勾選框基礎樣式 */
        .form-check-input {
            border: 2px solid #94a3b8; /* 加粗邊框，讓它更明顯 */
            background-color: #f1f5f9;
        }

        /* 4. 勾選框焦點狀態 */
        .form-check-input:focus {
            border-color: #64748b;
            box-shadow: 0 0 0 0.25rem rgba(100, 116, 139, 0.2);
        }

        /* 5. 勾選框「選中」狀態 */
        .form-check-input:checked {
            background-color: #64748b; /* 使用沉穩的低飽和藍灰色填充 */
            border-color: #64748b;
        }

        /* 6. 特別處理大開關 (Switch) 的選中顏色 */
        .form-switch .form-check-input:checked {
            background-color: #64748b; /* 統一使用低飽和色系 */
            border-color: #64748b;
        }

        /* 7. 調整 Placeholder (提示文字) 的顏色，讓它清晰但不搶眼 */
        ::placeholder {
            color: #94a3b8 !important;
            opacity: 1;
        }
        /* --- ★ 修飾結束 ★ --- */

    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar d-flex flex-column p-3">
            <h4 class="text-white mb-4 px-3"><i class="fas fa-microchip me-2"></i>i-Parts Hub</h4>
            <ul class="nav nav-pills flex-column mb-auto">
                <?php $curr_route = $_GET['route'] ?? 'dashboard'; ?>
                <li class="nav-item">
                    <a href="index.php?route=dashboard" class="nav-link <?= $curr_route == 'dashboard' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line me-2"></i>上機率看板
                    </a>
                </li>
                <li>
                    <a href="index.php?route=ops" class="nav-link <?= in_array($curr_route, ['ops', 'ops_new', 'inventory', 'ops_edit']) ? 'active' : '' ?>">
                        <i class="fas fa-tools me-2"></i>作業中心
                    </a>
                </li>
                <li>
                    <a href="index.php?route=ipart_pending" class="nav-link <?= $curr_route == 'ipart_pending' ? 'active' : '' ?>">
                        <i class="fas fa-clipboard-check me-2"></i>待補登
                    </a>
                </li>
                <li>
                    <a href="index.php?route=admin" class="nav-link <?= $curr_route == 'admin' ? 'active' : '' ?>">
                        <i class="fas fa-user-cog me-2"></i>管理員頁面
                    </a>
                </li>
                <li>
                    <a href="index.php?route=logout" class="nav-link text-danger mt-3">
                        <i class="fas fa-sign-out-alt me-2"></i>登出
                    </a>
                </li>
            </ul>
            <div class="text-secondary small px-3 mt-4">Offline Ver 1.4 (UI Update)</div>
        </div>
        <div class="main-content w-100">
            <?= $content ?>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>