<?php ob_start(); ?>
<div class="mb-4">
    <h3><i class="fas fa-user-shield me-2 text-dark"></i>管理員頁面 (<?= $_SESSION['user_id'] ?>)</h3>
</div>

<?php if (isset($import_success) && $import_success): ?>
    <div class="alert alert-success border-success border-2 shadow-sm mb-4">
        <h5 class="fw-bold"><i class="fas fa-check-circle me-2"></i>匯入作業完成</h5>
        
        <?php if (!empty($added_items)): ?>
            <p class="mb-2 fw-bold text-success">本次新增 (<?= count($added_items) ?> 筆)：</p>
            <div class="bg-white p-2 border rounded mb-3" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Type</th><th>Key (PartNo/ToolID)</th><th>Name/Vendor</th></tr></thead>
                    <tbody>
                        <?php foreach ($added_items as $a): ?>
                            <tr>
                                <td><span class="badge <?= ($a['type']=='PART'?'bg-dark':'bg-primary') ?>"><?= $a['type'] ?? 'ITEM' ?></span></td>
                                <td><?= $a['part_no'] ?? $a['name'] ?></td>
                                <td><?= isset($a['vendor']) ? $a['name'].' / '.$a['vendor'] : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (!empty($updated_items)): ?>
            <p class="mb-2 fw-bold text-primary">本次更新 (<?= count($updated_items) ?> 筆)：</p>
            <div class="bg-white p-2 border rounded" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Part No</th><th>New Name</th><th>New Vendor</th></tr></thead>
                    <tbody>
                        <?php foreach ($updated_items as $u): ?>
                            <tr>
                                <td><?= $u['part_no'] ?></td>
                                <td><?= $u['name'] ?></td>
                                <td><?= $u['vendor'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="index.php?route=admin" class="btn btn-sm btn-outline-success">關閉訊息</a>
        </div>
    </div>
<?php endif; ?>


<?php if (!empty($conflicts) || !empty($category_errors)): ?>
    <div class="card border-danger shadow-sm mb-4">
        <div class="card-header bg-danger text-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-exclamation-circle me-2"></i>匯入資料異常處理</span>
            <span class="badge bg-white text-danger">共 <?= count($conflicts ?? []) + count($category_errors ?? []) ?> 筆待決</span>
        </div>
        <div class="card-body p-0">
            <form action="index.php?route=admin_resolve_conflict" method="POST">
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light sticky-top" style="z-index: 5;">
                            <tr>
                                <th style="width: 120px;">異常類型</th>
                                <th style="width: 150px;">關鍵值 (Key)</th>
                                <th>內容比對 / 詳情</th>
                                <th style="width: 320px;">解決方案 (Action)</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            <?php if (!empty($category_errors)): ?>
                                <?php foreach ($category_errors as $i => $err): ?>
                                <tr class="table-danger bg-opacity-10">
                                    <td class="text-center">
                                        <span class="badge bg-danger mb-1">分類不明</span><br>
                                        <small class="text-muted">Raw: <?= $err['raw_cat'] ?: '(空)' ?></small>
                                    </td>
                                    <td class="fw-bold text-dark"><?= $err['key'] ?></td>
                                    <td>
                                        <div class="small text-muted mb-1">CSV 內容：</div>
                                        <strong><?= $err['csv']['name'] ?></strong>
                                        <?php if($err['csv']['vendor']): ?>
                                            <div class="small text-secondary"><i class="fas fa-industry me-1"></i><?= $err['csv']['vendor'] ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="cat_fix[<?= $i ?>]" id="fix_p_<?= $i ?>" value="PART">
                                            <label class="btn btn-outline-dark btn-sm" for="fix_p_<?= $i ?>">轉 PART</label>

                                            <input type="radio" class="btn-check" name="cat_fix[<?= $i ?>]" id="fix_t_<?= $i ?>" value="TOOL">
                                            <label class="btn btn-outline-primary btn-sm" for="fix_t_<?= $i ?>">轉 TOOL</label>

                                            <input type="radio" class="btn-check" name="cat_fix[<?= $i ?>]" id="fix_s_<?= $i ?>" value="skip" checked>
                                            <label class="btn btn-outline-secondary btn-sm" for="fix_s_<?= $i ?>">不匯入</label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if (!empty($conflicts)): ?>
                                <?php foreach ($conflicts as $i => $c): ?>
                                <tr class="table-warning bg-opacity-10">
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">資料衝突</span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= $c['key'] ?></td>
                                    <td>
                                        <div class="row g-0">
                                            <div class="col-6 border-end pe-2">
                                                <div class="small text-primary fw-bold mb-1">DB (現有)</div>
                                                <div class="text-truncate"><?= $c['db']['name'] ?></div>
                                                <div class="small text-muted"><?= $c['db']['vendor'] ?></div>
                                            </div>
                                            <div class="col-6 ps-2">
                                                <div class="small text-success fw-bold mb-1">CSV (新版)</div>
                                                <div class="text-truncate"><?= $c['csv']['name'] ?></div>
                                                <div class="small text-muted"><?= $c['csv']['vendor'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="decision[<?= $i ?>]" id="db_<?= $i ?>" value="db" checked>
                                            <label class="btn btn-outline-primary btn-sm" for="db_<?= $i ?>">保留舊版</label>

                                            <input type="radio" class="btn-check" name="decision[<?= $i ?>]" id="csv_<?= $i ?>" value="csv">
                                            <label class="btn btn-outline-success btn-sm" for="csv_<?= $i ?>">覆蓋新版</label>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>
                
                <div class="card-footer bg-white p-3 text-end">
                    <button type="submit" class="btn btn-danger fw-bold px-4">
                        <i class="fas fa-save me-2"></i> 確認並處理所有異常
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>

    <div class="row mb-4">
        
        <div class="col-md-4" id="guide_admin_part">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-dark d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-cubes me-2"></i>PART / Tool 主檔</span>
                    <a href="index.php?route=download_template" class="btn btn-sm btn-outline-dark" title="匯出完整主檔清單"><i class="fas fa-file-export"></i></a>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">批次匯入料號與機台 (自動分類與比對)</p>
                    <form action="index.php?route=admin_import&type=part" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-sm fw-bold"><i class="fas fa-upload me-1"></i> 匯入主檔 (CSV)</button>
                        </div>
                    </form>
                    
                    <div class="d-grid mt-3">
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#partListCollapse">
                            <i class="fas fa-list me-1"></i> 檢視/刪除料號
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4" id="guide_admin_tool">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-primary d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-microchip me-2"></i>機台列表 (Tool List)</span>
                    <a href="index.php?route=admin_export&type=tool" class="btn btn-sm btn-outline-primary" title="匯出清單"><i class="fas fa-download"></i></a>
                </div>
                <div class="card-body">
                    <form action="index.php?route=admin_manage_master" method="POST" class="d-flex gap-2 mb-3">
                        <input type="hidden" name="type" value="tool"><input type="hidden" name="action" value="add">
                        <input type="text" name="name" class="form-control" placeholder="新增機台..." required>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i></button>
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

        <div class="col-md-4" id="guide_admin_loc">
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

    </div>

    <div class="collapse mb-4" id="partListCollapse">
        <div class="card border-secondary shadow-sm">
            <div class="card-header bg-secondary text-white fw-bold">
                <i class="fas fa-database me-2"></i>現有 Part 主檔清單 (<?= count($part_list_all ?? []) ?> 筆)
            </div>
            <div class="card-body p-0">
                <div class="p-2 border-bottom">
                    <input type="text" id="partSearch" class="form-control form-control-sm" placeholder="輸入料號快速搜尋..." onkeyup="filterParts()">
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover table-striped align-middle mb-0" id="partTable">
                        <thead class="sticky-top bg-light">
                            <tr><th>料號 (Part No)</th><th>品名 (Name)</th><th>廠商 (Vendor)</th><th>操作</th></tr>
                        </thead>
                        <tbody>
                            <?php if(empty($part_list_all)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">無資料</td></tr>
                            <?php else: ?>
                                <?php foreach($part_list_all as $p): ?>
                                <tr class="part-row">
                                    <td class="fw-bold"><?= $p['part_no'] ?></td>
                                    <td><?= $p['name'] ?></td>
                                    <td><?= $p['vendor'] ?></td>
                                    <td>
                                        <form action="index.php?route=admin_manage_master" method="POST" onsubmit="return confirm('確定要刪除料號 [<?= $p['part_no'] ?>] 嗎？');">
                                            <input type="hidden" name="type" value="part">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="name" value="<?= $p['part_no'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash-alt"></i></button>
                                        </form>
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
    
    <script>
        function filterParts() {
            var input = document.getElementById("partSearch");
            var filter = input.value.toUpperCase();
            var table = document.getElementById("partTable");
            var tr = table.getElementsByClassName("part-row");
            for (var i = 0; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td")[0];
                if (td) {
                    var txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>

    <div class="card border-0 shadow-sm" id="guide_admin_history">
        <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
            <span><i class="fas fa-history me-2 text-primary"></i>歷史紀錄 (Logbook)</span>
            <?php if(isset($my_records)): ?><span class="badge bg-secondary"><?= count($my_records) ?> 筆</span><?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light sticky-top">
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
                                        $statusColors = [
                                            'IN' => 'bg-primary',
                                            'ON' => 'bg-success',
                                            'OUT' => 'bg-secondary'
                                        ];
                                        $badgeClass = isset($statusColors[$rec['status']]) ? $statusColors[$rec['status']] : 'bg-light text-dark';
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