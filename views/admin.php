<?php ob_start(); ?>
<div class="mb-4">
    <h3><i class="fas fa-user-shield me-2 text-dark"></i>管理員頁面 (<?= $_SESSION['user_id'] ?>)</h3>
</div>

<?php if (isset($show_conflict_ui) && $show_conflict_ui): ?>
    <?php $type = $_SESSION['import_type'] ?? 'part'; ?>
    <div class="card border-warning shadow-sm mb-4">
        </div>
<?php else: ?>

    <div class="row mb-4">
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" id="guide_admin_tool">
                <div class="card-header bg-white fw-bold py-3 text-primary d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-microchip me-2"></i>機台管理</span>
                    <a href="index.php?route=admin_export&type=tool" class="btn btn-sm btn-outline-primary" title="匯出/範本"><i class="fas fa-download"></i></a>
                </div>
                <div class="card-body">
                    <form action="index.php?route=admin_manage_master" method="POST" class="d-flex gap-2 mb-3" id="guide_admin_tool_form">
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
            <div class="card border-0 shadow-sm h-100" id="guide_admin_loc">
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
            <div class="card border-0 shadow-sm h-100" id="guide_admin_part">
                <div class="card-header bg-white fw-bold py-3 text-dark d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-file-csv me-2"></i>PART 主檔</span>
                    <a href="index.php?route=admin_export&type=part" class="btn btn-sm btn-outline-dark" title="匯出/範本"><i class="fas fa-file-export"></i></a>
                </div>
                <div class="card-body">
                    <?php if (isset($msg)): ?><div class="alert alert-success small py-1 mb-2"><i class="fas fa-check-circle me-1"></i> <?= $msg ?></div><?php endif; ?>
                    <?php if (isset($error)): ?><div class="alert alert-danger small py-1 mb-2"><i class="fas fa-exclamation-circle me-1"></i> <?= $error ?></div><?php endif; ?>

                    <p class="small text-muted mb-2">更新已知料號清單 (支援衝突比對)</p>
                    
                    <form action="index.php?route=admin_import&type=part" method="POST" enctype="multipart/form-data" id="guide_admin_part_form">
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
            <span><i class="fas fa-history me-2 text-primary"></i>歷史紀錄</span>
            <?php if(isset($my_records)): ?><span class="badge bg-secondary"><?= count($my_records) ?> 筆</span><?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light sticky-top" id="guide_admin_history_head">
                        <tr>
                            <th style="width: 120px;">操作</th>
                            <th>狀態 (Status)</th>
                            <th>時間 (Time)</th>
                            <th>料號 (Part No)</th>
                            <th>品名 (Name)</th>
                            <th>廠商 (Vendor)</th>
                            <th>序號 (S/N)</th>
                            <th>分類 (Category)</th>
                            <th>機台/位置 (Location)</th>
                            <th>備註 (Remark)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_records)): ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted">目前無資料</td></tr>
                        <?php else: ?>
                            <?php foreach ($my_records as $rec): ?>
                            <tr>
                                <td>
                                    <a href="index.php?route=ops_edit&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="編輯"><i class="fas fa-edit"></i></a>
                                    <a href="index.php?route=ops_delete&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-danger" title="刪除" onclick="return confirm('確定要刪除此筆紀錄嗎？此動作無法復原！')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = match($rec['status']) {
                                            'IN' => 'bg-primary',
                                            'ON' => 'bg-success',
                                            'OUT' => 'bg-secondary',
                                            default => 'bg-light text-dark'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $rec['status'] ?></span>
                                </td>
                                <td class="small"><?= date('Y-m-d H:i', strtotime($rec['created_at'])) ?></td>
                                <td class="fw-bold"><?= $rec['part_no'] ?></td>
                                <td class="small text-truncate" style="max-width: 200px;" title="<?= $rec['part_name'] ?>"><?= $rec['part_name'] ?></td>
                                <td class="small"><?= $rec['vendor'] ?></td>
                                <td class="small font-monospace"><?= $rec['sn'] ?></td>
                                <td class="small"><?= $rec['category'] ?></td>
                                <td class="small"><span class="badge bg-light text-dark border"><?= $rec['location'] ?></span></td>
                                <td class="small text-muted"><?= $rec['remark'] ?></td>
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