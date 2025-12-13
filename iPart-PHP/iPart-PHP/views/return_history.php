<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php?route=ops_new&status=OUT" class="text-decoration-none text-secondary mb-2 d-inline-block">
            <i class="fas fa-arrow-left me-1"></i> 返回退料作業
        </a>
        <h3 class="fw-bold"><i class="fas fa-history me-2 text-secondary"></i>退料歷史紀錄 (<?= $curr_dept ?>)</h3>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3">
        <i class="fas fa-list me-2"></i>最近 100 筆退料明細
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>退料時間</th>
                        <th>料號 (Part No)</th>
                        <th>品名</th>
                        <th>廠商</th>
                        <th>S/N</th>
                        <th>來源機台</th>
                        <th>備註</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">目前沒有退料紀錄</td></tr>
                    <?php else: ?>
                        <?php foreach ($returns as $row): ?>
                        <tr>
                            <td class="text-muted small"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                            <td class="fw-bold text-dark"><?= $row['part_no'] ?></td>
                            <td><?= $row['part_name'] ?></td>
                            <td><?= $row['vendor'] ?></td>
                            <td><?= $row['sn'] ?></td>
                            <td><span class="badge bg-secondary"><?= $row['location'] ?></span></td>
                            <td class="text-muted small"><?= $row['remark'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout.php'; ?>