<?php ob_start(); ?>
<style>
    .ipart-card { background: #fff; border: 1px solid #e2e8f0; border-left: 5px solid #3b82f6; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .form-switch .form-check-input { width: 3.5em; height: 1.75em; cursor: pointer; }
    .form-switch .form-check-input:checked { background-color: #10b981; border-color: #10b981; }
    .mounted-table tr:hover { background-color: #f8fafc; }
    
    /* Modal 列表樣式 - 加大高度限制 */
    .confirm-list { max-height: 400px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
    .confirm-list ul { padding-left: 20px; margin-bottom: 0; }
    .confirm-list li { margin-bottom: 8px; color: #334155; font-size: 1.1em; } /* 字體稍微加大 */
</style>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <?php if ($status == 'OUT'): ?>
        <form id="batchReturnForm" action="index.php?route=ops_batch_out" method="POST">
            <div class="card shadow-sm border-0 mb-4">
                
                <div class="card-header bg-secondary text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="me-3"><i class="fas fa-list-alt me-2"></i>已上機清單</span>
                        <a href="index.php?route=return_history" class="btn btn-sm btn-light text-secondary fw-bold shadow-sm">
                            <i class="fas fa-history me-1"></i> 退料歷史
                        </a>
                    </div>
                    <span class="badge bg-white text-dark"><?= count($mounted_list) ?> 筆</span>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-hover align-middle mb-0 mounted-table" id="outTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </th>
                                    <th>機台 (Tool)</th>
                                    <th>料號 (Part No)</th>
                                    <th>品名 (Name)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mounted_list)): ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">目前無上機零件，無需退料。</td></tr>
                                <?php else: ?>
                                    <?php foreach ($mounted_list as $item): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input class="form-check-input item-check" type="checkbox" name="out_ids[]" value="<?= $item['id'] ?>">
                                        </td>
                                        <td><span class="badge bg-success"><?= $item['location'] ?></span></td>
                                        <td class="fw-bold text-dark"><?= $item['part_no'] ?></td>
                                        <td class="small text-muted"><?= $item['part_name'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($mounted_list)): ?>
                <div class="card-footer bg-white p-3">
                    <div class="mb-3">
                        <label class="form-label small text-muted">退料備註 (選填)</label>
                        <input type="text" name="batch_remark" class="form-control" placeholder="例如: 故障更換、報廢...">
                    </div>
                    <button type="button" class="btn btn-danger w-100 fw-bold py-2" onclick="showConfirmModal()">
                        <i class="fas fa-sign-out-alt me-2"></i> 確認退料 (Batch Return)
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </form>

        <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-danger text-white">
                        <h4 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>確認退料作業</h4>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="lead mb-3">您即將退回共 <strong id="modalCount" class="text-danger fs-2">0</strong> 筆項目：</p>
                        
                        <div class="confirm-list mb-3">
                            <ul id="modalList">
                                </ul>
                        </div>
                        
                        <div class="alert alert-secondary mb-0 border-0">
                            <i class="fas fa-info-circle me-1"></i> 請確認上述項目無誤，送出後將寫入歷史紀錄。
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-lg btn-outline-secondary px-4" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-lg btn-danger fw-bold px-5" onclick="submitBatchForm()">
                            確認送出 <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <div class="card shadow-sm border-0" id="formCard">
            <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                <div><i class="fas fa-edit me-2 text-primary"></i>新增作業單</div>
                <div>
                    <a href="http://【請修改這裡_填入_IPART_網址】" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="前往 iPart 系統"><i class="fas fa-external-link-alt me-1"></i> iPart 系統</a>
                    <a href="http://【請修改這裡_填入_待建料DB_網址】" target="_blank" class="btn btn-sm btn-outline-secondary" title="查詢待建料清單"><i class="fas fa-database me-1"></i> 待建料 DB</a>
                </div>
            </div>

            <div class="card-body p-4 bg-light bg-opacity-25">
                <form method="POST">
                    <div class="mb-3"><label class="badge bg-primary fs-6"><?= $status ?> 作業</label><input type="hidden" name="status" value="<?= $status ?>"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">料號</label>
                        <?php if ($status == 'ON'): ?>
                            <?php if (empty($prefill['part_no'])): ?><div class="text-muted small mb-1"><i class="fas fa-magic me-1"></i> 請選擇清單帶入庫存</div><?php endif; ?>
                            <input type="text" name="part_no" id="partNoInput" list="inventory_datalist" class="form-control form-control-lg" placeholder="從庫存選擇..." required autocomplete="off" value="<?= $prefill['part_no'] ?>">
                            <datalist id="inventory_datalist">
                                <?php foreach ($inventory_list as $item): ?>
                                    <option value="<?= $item['part_no'] ?>"><?= $item['part_name'] ?> (<?= $item['vendor'] ?>) - <?= $item['location'] ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        <?php else: ?>
                            <div class="text-muted small mb-1"><i class="fas fa-search me-1"></i> 可搜尋 Part Master 主檔</div>
                            <input type="text" name="part_no" id="partNoInput" list="master_datalist" class="form-control form-control-lg" placeholder="輸入或搜尋..." required autocomplete="off" value="<?= $prefill['part_no'] ?>">
                            <datalist id="master_datalist">
                                <?php foreach ($master_list as $m): ?>
                                    <option value="<?= $m['part_no'] ?>"><?= $m['name'] ?> (<?= $m['vendor'] ?>)</option>
                                <?php endforeach; ?>
                            </datalist>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">品名</label><input type="text" name="part_name" id="partNameInput" class="form-control" value="<?= $prefill['part_name'] ?>"></div>
                        <div class="col-md-6">
                            <label class="form-label">廠商</label>
                            <input type="text" name="vendor" id="vendorInput" class="form-control" value="<?= $prefill['vendor'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">序號 (S/N)</label><input type="text" name="sn" id="snInput" class="form-control" value="<?= $prefill['sn'] ?>"></div>
                        <div class="col-md-6">
                            <?php if ($status == 'ON'): ?>
                                <label class="form-label fw-bold text-success">機台 ID (Tool ID)</label><input type="text" name="tool_id" class="form-control" required>
                            <?php else: ?>
                                <label class="form-label">儲存位置</label><input type="text" name="location" id="locInput" list="loc_list" class="form-control" required>
                                <datalist id="loc_list"><?php foreach ($h_locs as $l): ?><option value="<?= $l ?>"><?php endforeach; ?></datalist>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($status == 'ON'): ?>
                    <div class="mb-4 p-4 ipart-card d-flex align-items-center justify-content-between">
                        <div><h5 class="fw-bold mb-1 text-dark">iPart 系統登錄確認</h5><small class="text-muted">請確認已在 iPart 完成上機。</small></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="ipart_logged" id="ipartSwitch"></div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4"><label class="form-label">備註</label><textarea name="remark" class="form-control" rows="2"></textarea></div>
                    <div class="d-grid gap-2"><button type="submit" class="btn btn-primary btn-lg py-2 fw-bold">提交</button><a href="index.php?route=ops" class="btn btn-outline-secondary">取消</a></div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.item-check');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    }

    function showConfirmModal() {
        const checkboxes = document.querySelectorAll('.item-check:checked');
        if (checkboxes.length === 0) {
            alert('請至少選擇一筆項目進行退料！');
            return;
        }

        const listContainer = document.getElementById('modalList');
        const countSpan = document.getElementById('modalCount');
        listContainer.innerHTML = ''; 
        countSpan.textContent = checkboxes.length;

        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            // 0:checkbox, 1:Tool, 2:PartNo, 3:Name
            const tool = row.cells[1].innerText.trim();
            const partNo = row.cells[2].innerText.trim();
            const name = row.cells[3].innerText.trim();

            const li = document.createElement('li');
            li.innerHTML = `<span class="badge bg-success me-2">${tool}</span> <strong>${partNo}</strong> <span class="text-muted ms-2">${name}</span>`;
            listContainer.appendChild(li);
        });

        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        modal.show();
    }

    function submitBatchForm() {
        document.getElementById('batchReturnForm').submit();
    }

    <?php if ($status != 'OUT'): ?>
        <?php if ($status == 'ON'): ?>
            const dataList = <?= json_encode($inventory_list) ?>;
        <?php else: ?>
            const dataList = <?= json_encode($master_list) ?>; 
        <?php endif; ?>

        const partNoInput = document.getElementById('partNoInput');
        if (partNoInput) {
            partNoInput.addEventListener('input', function(e) {
                const foundItem = dataList.find(item => item.part_no === e.target.value);
                if (foundItem) {
                    if(document.getElementById('partNameInput')) document.getElementById('partNameInput').value = foundItem.part_name || foundItem.name || '';
                    if(document.getElementById('vendorInput')) document.getElementById('vendorInput').value = foundItem.vendor || '';

                    <?php if ($status == 'ON'): ?>
                        if(document.getElementById('snInput')) document.getElementById('snInput').value = foundItem.sn || '';
                        if(document.getElementById('locInput')) document.getElementById('locInput').value = foundItem.location || '';
                    <?php endif; ?>
                }
            });
        }
    <?php endif; ?>
</script>
<?php $content = ob_get_clean(); require 'layout.php'; ?>