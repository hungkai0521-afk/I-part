<?php ob_start(); ?>
<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold text-dark"><i class="fas fa-clipboard-list me-2"></i>作業中心 (Operations)</h2>
        <p class="text-muted">請選擇作業項目，或查詢下方流水帳。</p>
    </div>
    
    <div class="col-md-4 text-end" id="guide_links">
        <div class="btn-group shadow-sm">
            <a href="http://p58mesweb03.umc.com:8084/PMMWebSite/eParts/Login/login.cshtml?returnUrl=%2fPMMWebSite%2feParts%2fDefault" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-external-link-alt me-1"></i> iPart
            </a>
            <a href="Notes://F12AD16/48257DB0002B1BBC/EF9C1CE35692F71348256C5C0034F18C/6D22BDA971349EBD48258D63003116BF" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-database me-1"></i> 待建料
            </a>
        </div>
    </div>
</div>

<div class="row mb-4 g-3">
    <div class="col-md-4">
        <a href="index.php?route=ops_new&status=IN" class="card text-decoration-none shadow-sm h-100 border-primary border-top border-4 hover-shadow" id="guide_in">
            <div class="card-body text-center py-4">
                <div class="display-4 text-primary mb-2"><i class="fas fa-dolly"></i></div>
                <h4 class="fw-bold text-dark">進料 (IN)</h4>
                <p class="text-muted small mb-0">廠商進貨 / 領出備品入庫</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?route=ops_new&status=ON" class="card text-decoration-none shadow-sm h-100 border-success border-top border-4 hover-shadow" id="guide_on">
            <div class="card-body text-center py-4">
                <div class="display-4 text-success mb-2"><i class="fas fa-wrench"></i></div>
                <h4 class="fw-bold text-dark">上機 (ON)</h4>
                <p class="text-muted small mb-0">領料上機 / 扣除庫存</p>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?route=ops_new&status=OUT" class="card text-decoration-none shadow-sm h-100 border-secondary border-top border-4 hover-shadow" id="guide_out">
            <div class="card-body text-center py-4">
                <div class="display-4 text-secondary mb-2"><i class="fas fa-sign-out-alt"></i></div>
                <h4 class="fw-bold text-dark">退料 (OUT)</h4>
                <p class="text-muted small mb-0">故障送修 / 報廢 / 退回</p>
            </div>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0 fw-bold"><i class="fas fa-history me-2"></i>近期流水帳 (Logbook)</h5>
        
        <form class="d-flex align-items-center gap-2" id="guide_filter">
            <input type="hidden" name="route" value="ops">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light">From</span>
                <input type="date" name="start_date" class="form-control" value="<?= $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days')) ?>">
            </div>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light">To</span>
                <input type="date" name="end_date" class="form-control" value="<?= $_GET['end_date'] ?? date('Y-m-d') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            <a href="index.php?route=ops_export_csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success btn-sm ms-1" title="匯出 CSV">
                <i class="fas fa-file-csv"></i>
            </a>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>狀態</th>
                        <th>時間</th>
                        <th>料號 / 品名</th>
                        <th>序號 (S/N)</th>
                        <th>位置 / 機台</th>
                        <th>操作者</th>
                        <th>備註</th>
                        <th class="text-end">管理</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">查無資料</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $row): ?>
                        <tr>
                            <td>
                                <?php 
                                    $badges = ['IN'=>'bg-primary', 'ON'=>'bg-success', 'OUT'=>'bg-secondary'];
                                    $bg = $badges[$row['status']] ?? 'bg-dark';
                                ?>
                                <span class="badge <?= $bg ?>"><?= $row['status'] ?></span>
                            </td>
                            <td class="small"><?= date('Y-m-d H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <div class="fw-bold"><?= $row['part_no'] ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 150px;"><?= $row['part_name'] ?></div>
                            </td>
                            <td class="small font-monospace"><?= $row['sn'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $row['location'] ?></span></td>
                            <td class="small"><i class="fas fa-user-circle text-secondary me-1"></i><?= $row['dept'] ?></td>
                            <td class="small text-muted text-truncate" style="max-width: 150px;" title="<?= $row['remark'] ?>"><?= $row['remark'] ?></td>
                            <td class="text-end">
                                <a href="index.php?route=ops_edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover { transform: translateY(-3px); transition: all 0.3s; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>

<?php $content = ob_get_clean(); require 'layout.php'; ?>