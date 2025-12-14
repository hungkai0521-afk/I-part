<?php ob_start(); ?>
<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h3 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>作業中心 (<?= $curr_dept ?>)</h3>
        <p class="text-muted small mb-0">管理您的進出料與上機紀錄。</p>
    </div>
    <div class="col-md-6 text-end">
        <div class="btn-group shadow-sm">
            <a href="index.php?route=ops_new&status=IN" class="btn btn-primary fw-bold"><i class="fas fa-arrow-down me-1"></i> 進料 (IN)</a>
            <a href="index.php?route=ops_new&status=ON" class="btn btn-success fw-bold"><i class="fas fa-power-off me-1"></i> 上機 (ON)</a>
            <a href="index.php?route=ops_new&status=OUT" class="btn btn-secondary fw-bold"><i class="fas fa-arrow-up me-1"></i> 退料 (OUT)</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body bg-light rounded">
        <form class="row g-2 align-items-center" method="GET" action="index.php">
            <input type="hidden" name="route" value="ops">
            <div class="col-auto"><label class="fw-bold text-secondary small">日期範圍：</label></div>
            <div class="col-auto"><input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>"></div>
            <div class="col-auto">~</div>
            <div class="col-auto"><input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>"></div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i> 篩選</button></div>
            <div class="col-auto ms-auto">
                <a href="index.php?route=ops_export_csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-file-csv me-1"></i> 匯出 CSV
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold py-3">
                <i class="fas fa-history me-2"></i>近期異動紀錄
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th>狀態</th>
                                <th>日期</th>
                                <th>料號 (Part No)</th>
                                <th>品名 / 廠商</th>
                                <th>分類</th> <th>S/N</th>
                                <th>機台 / 位置</th>
                                <th>備註</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">查無資料</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                <tr class="<?= ($log['status']=='OUT') ? 'table-secondary text-muted' : '' ?>">
                                    <td>
                                        <span class="badge rounded-pill bg-<?= ($log['status']=='IN'?'primary':($log['status']=='ON'?'success':'secondary')) ?>">
                                            <?= $log['status'] ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= date('Y/m/d H:i', strtotime($log['created_at'])) ?></td>
                                    <td class="fw-bold"><?= $log['part_no'] ?></td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 150px;"><?= $log['part_name'] ?></div>
                                        <div class="small text-muted"><?= $log['vendor'] ?></div>
                                    </td>
                                    <td class="small text-muted"><?= $log['category'] ?? '-' ?></td> <td class="small font-monospace"><?= $log['sn'] ?></td>
                                    <td>
                                        <?php if($log['status']=='ON'): ?>
                                            <span class="badge bg-info text-dark"><i class="fas fa-microchip me-1"></i><?= $log['location'] ?></span>
                                        <?php else: ?>
                                            <i class="fas fa-warehouse me-1 text-muted"></i><?= $log['location'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted text-truncate" style="max-width: 100px;"><?= $log['remark'] ?></td>
                                    <td>
                                        <a href="index.php?route=ops_edit&id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-primary" title="編輯"><i class="fas fa-edit"></i></a>
                                        <a href="index.php?route=ops_delete&id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-danger" title="刪除" onclick="return confirm('確定要刪除嗎？')"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 text-primary">
                <i class="fas fa-box-open me-2"></i>目前庫存 (<?= $inv_count ?>)
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($inventory_items)): ?>
                        <li class="list-group-item text-center text-muted py-4">目前無庫存</li>
                    <?php else: ?>
                        <?php foreach (array_slice($inventory_items, 0, 10) as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div class="text-truncate me-2" style="max-width: 70%;">
                                <div class="fw-bold small"><?= $item['part_no'] ?></div>
                                <div class="small text-muted" style="font-size: 0.8em;"><?= $item['part_name'] ?></div>
                            </div>
                            <span class="badge bg-light text-dark border"><?= $item['location'] ?></span>
                        </li>
                        <?php endforeach; ?>
                        <?php if (count($inventory_items) > 10): ?>
                            <li class="list-group-item text-center bg-light">
                                <a href="index.php?route=inventory" class="small text-decoration-none">查看全部...</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout.php'; ?>