<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <div class="d-flex align-items-center gap-3 mb-2">
            <h3 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>作業中心</h3>
            
            <div id="guide_links">
                <a href="http://p58mesweb03.umc.com:8084/PMMWebSite/eParts/Login/login.cshtml?returnUrl=%2fPMMWebSite%2feParts%2fDefault" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm" title="前往 iPart 系統">
                    <i class="fas fa-external-link-alt me-1"></i> iPart 系統
                </a>
                <a href="Notes://F12AD16/48257DB0002B1BBC/EF9C1CE35692F71348256C5C0034F18C/6D22BDA971349EBD48258D63003116BF" class="btn btn-sm btn-outline-secondary shadow-sm" title="開啟 Notes 待建料資料庫">
                    <i class="fas fa-database me-1"></i> 待建料 DB
                </a>
            </div>
        </div>
        <p class="text-muted mb-0">目前部門: <span class="badge bg-dark"><?= $curr_dept ?></span></p>
    </div>
    
    <form class="d-flex align-items-end gap-2" method="GET" action="index.php" id="guide_filter">
        <input type="hidden" name="route" value="ops">
        <div>
            <label class="form-label small text-muted mb-0">起始日期</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
        </div>
        <div>
            <label class="form-label small text-muted mb-0">結束日期</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
        </div>
        <button type="submit" class="btn btn-sm btn-secondary align-self-end">
            <i class="fas fa-search"></i> 查詢
        </button>
        <a href="index.php?route=ops_export_csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-sm btn-success align-self-end" title="匯出 CSV">
            <i class="fas fa-file-csv me-1"></i> 匯出
        </a>
    </form>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <a href="index.php?route=ops_new&status=IN" id="guide_in" class="card text-decoration-none h-100 shadow-sm border-0 bg-primary bg-opacity-10 hover-shadow transition-link">
            <div class="card-body d-flex align-items-center justify-content-between text-primary">
                <div><h5 class="fw-bold mb-0">進料 (IN)</h5><small>零件入庫、建立庫存</small></div>
                <i class="fas fa-truck-loading fa-2x opacity-50"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?route=ops_new&status=ON" id="guide_on" class="card text-decoration-none h-100 shadow-sm border-0 bg-success bg-opacity-10 hover-shadow transition-link">
            <div class="card-body d-flex align-items-center justify-content-between text-success">
                <div><h5 class="fw-bold mb-0">上機 (ON)</h5><small>安裝零件、扣除庫存</small></div>
                <i class="fas fa-tools fa-2x opacity-50"></i>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="index.php?route=ops_new&status=OUT" id="guide_out" class="card text-decoration-none h-100 shadow-sm border-0 bg-secondary bg-opacity-10 hover-shadow transition-link">
            <div class="card-body d-flex align-items-center justify-content-between text-secondary">
                <div><h5 class="fw-bold mb-0">退料 (OUT)</h5><small>故障退回、批次處理</small></div>
                <i class="fas fa-box-open fa-2x opacity-50"></i>
            </div>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm" id="guide_table">
    <div class="card-header bg-white fw-bold py-3">
        <i class="fas fa-history me-2"></i>近期異動紀錄 (<?= count($logs) ?> 筆)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light">
                    <tr>
                        <th>狀態</th>
                        <th>日期時間</th>
                        <th>料號</th>
                        <th>品名</th>
                        <th>廠商</th>
                        <th>S/N</th>
                        <th>位置/機台</th>
                        <th>分類</th>
                        <th>iPart</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">查無此區間資料</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <?php 
                                    $statusColors = [
                                        'IN' => 'bg-primary',
                                        'ON' => 'bg-success',
                                        'OUT' => 'bg-secondary'
                                    ];
                                    $badgeClass = isset($statusColors[$log['status']]) ? $statusColors[$log['status']] : 'bg-light text-dark';
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $log['status'] ?></span>
                            </td>
                            <td class="small text-muted"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                            <td class="fw-bold text-dark"><?= $log['part_no'] ?></td>
                            <td class="small text-truncate" style="max-width: 150px;" title="<?= $log['part_name'] ?>"><?= $log['part_name'] ?></td>
                            <td class="small"><?= $log['vendor'] ?></td>
                            <td class="small font-monospace"><?= $log['sn'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $log['location'] ?></span></td>
                            <td class="small"><?= $log['category'] ?></td>
                            <td>
                                <?php if($log['status'] == 'ON'): ?>
                                    <?php if($log['ipart_logged']): ?>
                                        <i class="fas fa-check-circle text-success" title="已登錄"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle text-danger" title="未登錄"></i>
                                    <?php endif; ?>
                                <?php endif; ?>
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
    .hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; transition: all .2s; }
</style>

<?php $content = ob_get_clean(); require 'layout.php'; ?>