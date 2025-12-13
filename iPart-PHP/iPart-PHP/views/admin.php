<?php ob_start(); ?>
<div class="mb-4">
    <h3><i class="fas fa-user-shield me-2 text-dark"></i>管理員頁面 (<?= $_SESSION['user_id'] ?>)</h3>
    <?php if (isset($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
</div>

<?php 
// ★★★★ 統一的同步確認介面 ★★★★
if (isset($show_sync_ui) && $show_sync_ui): 
    $sync_type = $_SESSION['sync_type'];
    $adds = $_SESSION['sync_add'];
    $removes = $_SESSION['sync_remove'];
    $conflicts = $_SESSION['sync_conflict'];
    
    $type_name = "";
    if ($sync_type == 'tool') $type_name = "機台清單 (Tool)";
    if ($sync_type == 'location') $type_name = "位置清單 (Location)";
    if ($sync_type == 'part_master') $type_name = "Part Master";
?>
    <div class="card border-primary shadow mb-4">
        <div class="card-header bg-primary text-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-sync-alt me-2"></i>確認同步：<?= $type_name ?></span>
            <span class="badge bg-white text-primary">比對結果</span>
        </div>
        <div class="card-body">
            <div class="alert alert-info border-0">
                <i class="fas fa-info-circle me-1"></i> 系統將以 <strong>CSV 檔案</strong> 為基準進行同步。請確認以下差異：
            </div>

            <form action="index.php?route=admin_sync_execute" method="POST">
                
                <div class="row g-4">
                    <div class="col-md-<?= (!empty($conflicts)) ? '4' : '6' ?>">
                        <div class="card h-100 border-success">
                            <div class="card-header bg-success text-white fw-bold">
                                <i class="fas fa-plus-circle me-1"></i> 即將新增 (<?= count($adds) ?>)
                            </div>
                            <div class="card-body p-0" style="max-height:300px; overflow-y:auto;">
                                <?php if(empty($adds)): ?>
                                    <div class="p-3 text-center text-muted">無新增項目</div>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($adds as $item): ?>
                                            <li class="list-group-item text-success">
                                                <?php if(is_array($item)): ?>
                                                    <strong><?= $item['part_no'] ?></strong><br>
                                                    <small class="text-muted"><?= $item['name'] ?></small>
                                                <?php else: ?>
                                                    <strong><?= $item ?></strong>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($conflicts)): ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-warning">
                            <div class="card-header bg-warning text-dark fw-bold">
                                <i class="fas fa-exclamation-triangle me-1"></i> 資料衝突 (<?= count($conflicts) ?>)
                            </div>
                            <div class="card-body p-0" style="max-height:300px; overflow-y:auto;">
                                <table class="table table-bordered mb-0 small">
                                    <thead class="table-light"><tr><th>資料</th><th>選擇版本</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($conflicts as $c): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= $c['part_no'] ?></div>
                                                <div class="text-primary">DB: <?= $c['db']['name'] ?></div>
                                                <div class="text-success">CSV: <?= $c['csv']['name'] ?></div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="decision[<?= $c['part_no'] ?>]" value="db" checked> 舊
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="decision[<?= $c['part_no'] ?>]" value="csv"> 新
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-md-<?= (!empty($conflicts)) ? '4' : '6' ?>">
                        <div class="card h-100 border-danger">
                            <div class="card-header bg-danger text-white fw-bold">
                                <i class="fas fa-minus-circle me-1"></i> 即將刪除 (<?= count($removes) ?>)
                            </div>
                            <div class="card-body p-0" style="max-height:300px; overflow-y:auto;">
                                <?php if(empty($removes)): ?>
                                    <div class="p-3 text-center text-muted">無刪除項目</div>
                                <?php else: ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach($removes as $item): ?>
                                            <li class="list-group-item text-danger text-decoration-line-through">
                                                <?php if(is_array($item)): ?>
                                                    <strong><?= $item['part_no'] ?></strong><br>
                                                    <small class="text-muted"><?= $item['name'] ?></small>
                                                <?php else: ?>
                                                    <strong><?= $item ?></strong>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <a href="index.php?route=admin" class="btn btn-secondary me-2">取消</a>
                    <button type="submit" class="btn btn-primary fw-bold px-4" onclick="return confirm('確定要執行同步嗎？\n此操作將修改資料庫內容。')">
                        <i class="fas fa-check me-1"></i> 確認並執行同步
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>

<div class="row g-4 mb-5">
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 text-primary border-bottom-0">
                <i class="fas fa-microchip me-2"></i>機台清單 (Tool ID)
            </div>
            <div class="card-body bg-light bg-opacity-10">
                <form action="index.php?route=tool_import" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small text-muted">匯入 CSV (同步)</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3"><i class="fas fa-upload me-1"></i> 開始比對匯入</button>
                </form>
                <div class="d-flex gap-2">
                    <a href="index.php?route=download_tool_template" class="btn btn-sm btn-outline-secondary flex-grow-1"><i class="fas fa-download"></i> 範本</a>
                    <a href="index.php?route=tool_export" class="btn btn-sm btn-outline-primary flex-grow-1"><i class="fas fa-file-export"></i> 匯出</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 text-success border-bottom-0">
                <i class="fas fa-warehouse me-2"></i>儲存位置 (Location)
            </div>
            <div class="card-body bg-light bg-opacity-10">
                <form action="index.php?route=location_import" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small text-muted">匯入 CSV (同步)</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mb-3"><i class="fas fa-upload me-1"></i> 開始比對匯入</button>
                </form>
                <div class="d-flex gap-2">
                    <a href="index.php?route=download_location_template" class="btn btn-sm btn-outline-secondary flex-grow-1"><i class="fas fa-download"></i> 範本</a>
                    <a href="index.php?route=location_export" class="btn btn-sm btn-outline-success flex-grow-1"><i class="fas fa-file-export"></i> 匯出</a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold py-3 text-dark border-bottom-0">
                <i class="fas fa-cubes me-2"></i>已知 PART 清單
            </div>
            <div class="card-body bg-light bg-opacity-10">
                <form action="index.php?route=admin_import" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small text-muted">匯入 CSV (同步)</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 mb-3"><i class="fas fa-upload me-1"></i> 開始比對匯入</button>
                </form>
                <div class="d-flex gap-2">
                    <a href="index.php?route=download_template" class="btn btn-sm btn-outline-secondary flex-grow-1"><i class="fas fa-download"></i> 範本</a>
                    <a href="index.php?route=admin_export_master" class="btn btn-sm btn-outline-dark flex-grow-1"><i class="fas fa-file-export"></i> 匯出</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-database me-2 text-secondary"></i>本單位資料庫編輯 (最近 100 筆)</span>
        <?php if(isset($my_records)): ?><span class="badge bg-secondary"><?= count($my_records) ?> 筆</span><?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 600px;">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width: 140px;">操作</th>
                        <th>狀態</th>
                        <th>時間</th>
                        <th>料號</th>
                        <th>品名</th>
                        <th>機台/位置</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($my_records)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">目前無資料</td></tr>
                    <?php else: ?>
                        <?php foreach ($my_records as $rec): ?>
                        <tr>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="index.php?route=ops_edit&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-primary" title="編輯">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="index.php?route=ops_delete&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-danger" title="刪除" onclick="return confirm('確定要刪除這筆紀錄嗎？\n(注意：此動作無法復原)');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                            <td><span class="badge bg-<?= ($rec['status']=='IN'?'primary':($rec['status']=='ON'?'success':'secondary')) ?>"><?= $rec['status'] ?></span></td>
                            <td class="small"><?= date('m/d H:i', strtotime($rec['created_at'])) ?></td>
                            <td class="fw-bold"><?= $rec['part_no'] ?></td>
                            <td class="small text-truncate" style="max-width: 150px;"><?= $rec['part_name'] ?></td>
                            <td class="small"><?= $rec['location'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php endif; ?>
<?php $content = ob_get_clean(); require 'layout.php'; ?>