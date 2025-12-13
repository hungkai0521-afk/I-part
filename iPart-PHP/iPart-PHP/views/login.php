<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>i-Parts Hub | 系統登入</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; background: white; padding: 2rem; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="login-card shadow-lg">
        <h3 class="text-center mb-4 fw-bold">i-Parts Hub</h3>
        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">部門帳號</label>
                <input type="text" name="username" class="form-control" placeholder="LT3_EQ1" required>
            </div>
            <div class="mb-4">
                <label class="form-label">密碼</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">登入系統</button>
            </div>
        </form>
    </div>
</body>
</html>