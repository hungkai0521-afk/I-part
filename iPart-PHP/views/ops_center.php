<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-tools me-2 text-primary"></i>作業中心 (<?= $curr_dept ?>)</h3>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <a href="index.php?route=inventory" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white;">
                <div class="card-body text-center py-4">
                    <div class="d-flex justify-content-between align-items-start px-2">
                        <i class="fas fa-boxes fa-2x opacity-50"></i>
                        <span class="badge bg-white text-primary rounded-pill fs-6"><?= $inv_count ?> 件</span>
                    </div>
                    <h3 class="mt-3 fw-bold">目前庫存</h3>
                    <p class="mb-0 opacity-75 small">查看在庫明細</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3"><a href="index.php?route=ops_new&status=IN" class="text-decoration-none"><div class="card h-100 bg-white border-0 shadow-sm text-center"><div class="card-body py-4"><i class="fas fa-truck-loading fa-3x mb-3 text-primary"></i><h5 class="fw-bold text-dark">進料 (IN)</h5></div></div></a></div>
    <div class="col-md-3"><a href="index.php?route=ops_new&status=ON" class="text-decoration-none"><div class="card h-100 bg-white border-0 shadow-sm text-center"><div class="card-body py-4"><i class="fas fa-wrench fa-3x mb-3 text-success"></i><h5 class="fw-bold text-dark">上機 (ON)</h5></div></div></a></div>
    <div class="col-md-3"><a href="index.php?route=ops_new&status=OUT" class="text-decoration-none"><div class="card h-100 bg-white border-0 shadow-sm text-center"><div class="card-body py-4"><i class="fas fa-box-open fa-3x mb-3 text-secondary"></i><h5 class="fw-bold text-dark">退料 (OUT)</h5></div></div></a></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3"><i class="fas fa-history me-2"></i>最近作業流水帳</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>時間</th><th>動作</th><th>Part No</th><th>品名</th><th>位置/機台</th><th>管理</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small text-muted"><?= $log['created_at'] ?></td>
                    <td><span class="badge bg-<?= ($log['status']=='IN'?'primary':($log['status']=='ON'?'success':'secondary')) ?>"><?= $log['status'] ?></span></td>
                    <td class="fw-bold"><?= $log['part_no'] ?></td>
                    <td><?= $log['part_name'] ?></td>
                    <td><?= $log['location'] ?></td>
                    <td>
                        <a href="index.php?route=ops_edit&id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout.php'; ?>