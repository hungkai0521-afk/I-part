<?php ob_start(); ?>
<div class="mb-4">
    <h3><i class="fas fa-user-shield me-2 text-dark"></i>管理員頁面 (<?= $_SESSION['user_id'] ?>)</h3>
</div>

<?php if (isset($show_conflict_ui) && $show_conflict_ui): ?>
    <?php $type = $_SESSION['import_type'] ?? 'part'; ?>
    <div class="card border-warning shadow-sm mb-4">
        <div class="card-header bg-warning text-dark fw-bold py-3"><i class="fas fa-exclamation-triangle me-2"></i>發現資料衝突 (<?= ucfirst($type) ?>)</div>
        <div class="card-body">
            <p class="lead">系統偵測到 <strong><?= count($conflicts) ?></strong> 筆資料與現有資料庫內容不符。</p>
            <p class="text-muted">請選擇您要保留的版本：</p>
            
            <form action="index.php?route=admin_resolve_conflict" method="POST">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>關鍵值 (Key)</th>
                                <th class="text-primary">現有資料 (DB)</th>
                                <th class="text-success">上傳資料 (CSV)</th>
                                <th>選擇</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conflicts as $i => $c): ?>
                            <tr>
                                <td class="fw-bold"><?= $c['key'] ?></td>
                                <td class="text-primary">
                                    <?php if($type=='part'): ?>
                                        <div><?= $c['db']['name'] ?></div><div class="small"><?= $c['db']['vendor'] ?></div>
                                    <?php else: ?>
                                        <div><?= $c['db']['name'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-success">
                                    <?php if($type=='part'): ?>
                                        <div><?= $c['csv']['name'] ?></div><div class="small"><?= $c['csv']['vendor'] ?></div>
                                    <?php else: ?>
                                        <div><?= $c['csv']['name'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check" name="decision[<?= $i ?>]" id="db_<?= $i ?>" value="db" checked>
                                        <label class="btn btn-outline-primary" for="db_<?= $i ?>">保留舊的</label>

                                        <input type="radio" class="btn-check" name="decision[<?= $i ?>]" id="csv_<?= $i ?>" value="csv">
                                        <label class="btn btn-outline-success" for="csv_<?= $i ?>">使用新的</label>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="text-muted">另有 <?= count($_SESSION['import_inserts']) ?> 筆無衝突資料將直接匯入。</span>
                    <button type="submit" class="btn btn-primary fw-bold px-4">確認並更新</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>

    <div class="row mb-4">
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-primary d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-microchip me-2"></i>機台管理</span>
                    <a href="index.php?route=admin_export&type=tool" class="btn btn-sm btn-outline-primary" title="匯出/範本"><i class="fas fa-download"></i></a>
                </div>
                <div class="card-body">
                    <form action="index.php?route=admin_manage_master" method="POST" class="d-flex gap-2 mb-3">
                        <input type="hidden" name="type" value="tool"><input type="hidden" name="action" value="add">
                        <input type="text" name="name" class="form-control" placeholder="新增機台..." required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i></button>
                    </form>
                    <form action="index.php?route=admin_import&type=tool" method="POST" enctype="multipart/form-data" class="mb-3">
                        <div class="input-group input-group-sm">
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <button type="submit" class="btn btn-outline-primary">匯入</button>
                        </div>
                    </form>
                    <div class="border rounded p-2 bg-light" style="max-height: 250px; overflow-y: auto;">
                        <?php if (empty($tool_list)): ?><div class="text-muted small text-center py-2">無資料</div><?php else: ?>
                            <ul class="list-group list-group-flush small">
                            <?php foreach ($tool_list as $tool): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-1 bg-transparent px-0">
                                    <?= $tool ?>
                                    <form action="index.php?route=admin_manage_master" method="POST" class="d-inline" onsubmit="return confirm('刪除此機台？');">
                                        <input type="hidden" name="type" value="tool"><input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= $tool ?>">
                                        <button type="submit" class="btn btn-link text-danger p-0"><i class="fas fa-times"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-success d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-warehouse me-2"></i>位置管理</span>
                    <a href="index.php?route=admin_export&type=location" class="btn btn-sm btn-outline-success" title="匯出/範本"><i class="fas fa-download"></i></a>
                </div>
                <div class="card-body">
                    <form action="index.php?route=admin_manage_master" method="POST" class="d-flex gap-2 mb-3">
                        <input type="hidden" name="type" value="location"><input type="hidden" name="action" value="add">
                        <input type="text" name="name" class="form-control" placeholder="新增位置..." required>
                        <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i></button>
                    </form>
                    <form action="index.php?route=admin_import&type=location" method="POST" enctype="multipart/form-data" class="mb-3">
                        <div class="input-group input-group-sm">
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <button type="submit" class="btn btn-outline-success">匯入</button>
                        </div>
                    </form>
                    <div class="border rounded p-2 bg-light" style="max-height: 250px; overflow-y: auto;">
                        <?php if (empty($location_list)): ?><div class="text-muted small text-center py-2">無資料</div><?php else: ?>
                            <ul class="list-group list-group-flush small">
                            <?php foreach ($location_list as $loc): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-1 bg-transparent px-0">
                                    <?= $loc ?>
                                    <form action="index.php?route=admin_manage_master" method="POST" class="d-inline" onsubmit="return confirm('刪除此位置？');">
                                        <input type="hidden" name="type" value="location"><input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= $loc ?>">
                                        <button type="submit" class="btn btn-link text-danger p-0"><i class="fas fa-times"></i></button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-dark d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-csv me-2"></i>PART 主檔</span>
                    <a href="index.php?route=admin_export&type=part" class="btn btn-sm btn-outline-dark" title="匯出/範本"><i class="fas fa-file-export"></i></a>
                </div>
                <div class="card-body">
                    <?php if (isset($msg)): ?><div class="alert alert-success small py-1 mb-2"><i class="fas fa-check-circle me-1"></i> <?= $msg ?></div><?php endif; ?>
                    <?php if (isset($error)): ?><div class="alert alert-danger small py-1 mb-2"><i class="fas fa-exclamation-circle me-1"></i> <?= $error ?></div><?php endif; ?>

                    <p class="small text-muted mb-2">更新已知料號清單 (支援衝突比對)</p>
                    
                    <form action="index.php?route=admin_import&type=part" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fas fa-upload me-1"></i> 開始匯入 PART 清單</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-3 pt-3 border-top">
                        <a href="index.php?route=download_template" class="small text-muted text-decoration-none"><i class="fas fa-download me-1"></i> 下載 Part 範本</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-database me-2 text-primary"></i>本單位資料庫編輯 (流水帳)</span>
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
                                    <a href="index.php?route=ops_edit&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="編輯"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?route=ops_delete&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-danger" title="刪除" onclick="return confirm('確定要刪除此筆紀錄嗎？此動作無法復原！')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                                <td><span class="badge bg-<?= ($rec['status']=='IN'?'primary':($rec['status']=='ON'?'success':'secondary')) ?>"><?= $rec['status'] ?></span></td>
                                <td class="small"><?= date('m/d H:i', strtotime($rec['created_at'])) ?></td>
                                <td class="fw-bold"><?= $rec['part_no'] ?></td>
                                <td class="small text-truncate" style="max-width: 250px;"><?= $rec['part_name'] ?></td>
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