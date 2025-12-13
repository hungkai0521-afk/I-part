<?php ob_start(); ?>
<div class="mb-4">
    <h3><i class="fas fa-user-shield me-2 text-dark"></i>管理員頁面 (<?= $_SESSION['user_id'] ?>)</h3>
</div>

<?php if (isset($show_conflict_ui) && $show_conflict_ui): ?>
<div class="card border-warning shadow-sm mb-4">
    <div class="card-header bg-warning text-dark fw-bold py-3">
        <i class="fas fa-exclamation-triangle me-2"></i>發現資料衝突
    </div>
    <div class="card-body">
        <p class="lead">系統偵測到 <strong><?= count($conflicts) ?></strong> 筆資料與現有資料庫內容不符。</p>
        <p class="text-muted">請針對每一筆資料選擇您要保留的版本：</p>
        
        <form action="index.php?route=admin_import" method="POST">
            <input type="hidden" name="resolve_submit" value="1">
            
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Part No</th>
                            <th class="text-primary">現有資料 (DB)</th>
                            <th class="text-success">上傳資料 (CSV)</th>
                            <th>您的選擇</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($conflicts as $c): ?>
                        <tr>
                            <td class="fw-bold"><?= $c['part_no'] ?></td>
                            <td class="text-primary">
                                <div><?= $c['db']['name'] ?></div>
                                <div class="small"><?= $c['db']['vendor'] ?></div>
                            </td>
                            <td class="text-success">
                                <div><?= $c['csv']['name'] ?></div>
                                <div class="small"><?= $c['csv']['vendor'] ?></div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check" name="decision[<?= $c['part_no'] ?>]" id="db_<?= $c['part_no'] ?>" value="db" checked>
                                    <label class="btn btn-outline-primary" for="db_<?= $c['part_no'] ?>">保留舊的</label>

                                    <input type="radio" class="btn-check" name="decision[<?= $c['part_no'] ?>]" id="csv_<?= $c['part_no'] ?>" value="csv">
                                    <label class="btn btn-outline-success" for="csv_<?= $c['part_no'] ?>">使用新的</label>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="text-muted">另有 <?= count($_SESSION['import_inserts']) ?> 筆新資料將直接匯入。</span>
                <button type="submit" class="btn btn-primary fw-bold px-4">確認並執行更新</button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<div class="row">
    <div class="col-md-5">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-bold py-3">
                <i class="fas fa-file-csv me-2 text-success"></i>已知 PART 清單匯入
            </div>
            <div class="card-body">
                <?php if (isset($msg)): ?>
                    <div class="alert alert-success small"><i class="fas fa-check-circle me-1"></i> <?= $msg ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger small"><i class="fas fa-exclamation-circle me-1"></i> <?= $error ?></div>
                <?php endif; ?>

                <form action="index.php?route=admin_import" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">選擇 CSV 檔案</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-upload me-1"></i> 開始匯入</button>
                    <div class="text-center mt-2">
                         <a href="index.php?route=download_template" class="small text-muted text-decoration-none"><i class="fas fa-download me-1"></i> 下載範本</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body small text-muted">
                <strong><i class="fas fa-info-circle me-1"></i> 提示：</strong>
                若上傳的料號已存在且內容不同，系統將會跳出比對視窗供您確認。
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-database me-2 text-primary"></i>本單位資料庫編輯</span>
                <?php if(isset($my_records)): ?>
                    <span class="badge bg-secondary"><?= count($my_records) ?> 筆</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>操作</th>
                                <th>狀態</th>
                                <th>時間</th>
                                <th>料號</th>
                                <th>品名</th>
                                <th>機台/位置</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($my_records)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">目前無資料</td></tr>
                            <?php else: ?>
                                <?php foreach ($my_records as $rec): ?>
                                <tr>
                                    <td>
                                        <a href="index.php?route=ops_edit&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> 編輯
                                        </a>
                                    </td>
                                    <td><span class="badge bg-<?= ($rec['status']=='IN'?'primary':($rec['status']=='ON'?'success':'secondary')) ?>"><?= $rec['status'] ?></span></td>
                                    <td class="small"><?= date('m/d H:i', strtotime($rec['created_at'])) ?></td>
                                    <td class="fw-bold"><?= $rec['part_no'] ?></td>
                                    <td class="small text-truncate" style="max-width: 100px;"><?= $rec['part_name'] ?></td>
                                    <td class="small"><?= $rec['location'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); require 'layout.php'; ?>