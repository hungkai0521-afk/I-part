<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iPart 零件管理系統 (<?= $_SESSION['user_id'] ?? 'Guest' ?>)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: "Microsoft JhengHei", sans-serif; }
        .navbar-brand { font-weight: bold; letter-spacing: 1px; }
        .card { border-radius: 8px; }
        .table-hover tbody tr:hover { background-color: rgba(0,0,0,.03); }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php?route=dashboard">
                <i class="fas fa-tools me-2"></i>iPart 系統 <span class="badge bg-secondary text-white ms-1"><?= $_SESSION['user_id'] ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='dashboard')?'active':'' ?>" href="index.php?route=dashboard"><i class="fas fa-chart-line me-1"></i> 儀表板</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='ops'||$route=='ops_new'||$route=='ops_edit')?'active':'' ?>" href="index.php?route=ops"><i class="fas fa-clipboard-list me-1"></i> 作業中心</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='inventory')?'active':'' ?>" href="index.php?route=inventory"><i class="fas fa-boxes me-1"></i> 庫存明細</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($route=='ipart_pending')?'active':'' ?>" href="index.php?route=ipart_pending"><i class="fas fa-exclamation-circle me-1"></i> 待補登</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item me-2">
                        <a class="nav-link btn btn-outline-secondary text-light <?= ($route=='admin')?'active':'' ?>" href="index.php?route=admin">
                            <i class="fas fa-user-cog me-1"></i> 管理者頁面
                        </a>
                    </li>
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
</body>
</html>