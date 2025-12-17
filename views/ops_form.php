<?php ob_start(); ?>
<style>
    .ipart-card { background: #fff; border: 1px solid #e2e8f0; border-left: 5px solid #3b82f6; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .form-switch .form-check-input { width: 3.5em; height: 1.75em; cursor: pointer; }
    .form-switch .form-check-input:checked { background-color: #10b981; border-color: #10b981; }
    .mounted-table tr:hover { background-color: #f8fafc; }
    .confirm-list { max-height: 400px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
    .confirm-list li { margin-bottom: 8px; color: #334155; font-size: 1.1em; }
    .nav-tabs .nav-link.active { font-weight: bold; color: #dc3545; border-bottom: 3px solid #dc3545; }
</style>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <?php if ($status == 'OUT'): ?>
        
        <?php
            $list_in = [];
            $list_on = [];
            foreach ($return_list as $item) {
                if ($item['status'] === 'IN') {
                    $list_in[] = $item;
                } else {
                    $list_on[] = $item;
                }
            }
        ?>

        <form id="batchReturnForm" action="index.php?route=ops_batch_out" method="POST">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-secondary text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="me-3"><i class="fas fa-list-alt me-2"></i>退料選擇清單</span>
                        <a href="index.php?route=return_history" class="btn btn-sm btn-light text-secondary fw-bold shadow-sm"><i class="fas fa-history me-1"></i> 退料歷史</a>
                    </div>
                    <span class="badge bg-white text-dark"><?= count($return_list) ?> 筆可退</span>
                </div>
                
                <div class="card-body p-0">
                    <ul class="nav nav-tabs nav-fill bg-light px-2 pt-2" id="returnTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-on" data-bs-toggle="tab" data-bs-target="#content-on" type="button">
                                <i class="fas fa-tools me-2"></i>上機退料 (From Machine) <span class="badge bg-secondary"><?= count($list_on) ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-in" data-bs-toggle="tab" data-bs-target="#content-in" type="button">
                                <i class="fas fa-box me-2"></i>庫存退料 (From Stock) <span class="badge bg-secondary"><?= count($list_in) ?></span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="content-on">
                            <div class="table-responsive" style="max-height: 500px;">
                                <table class="table table-hover align-middle mb-0 mounted-table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 40px;" class="text-center">選</th>
                                            <th>位置/機台</th>
                                            <th>料號</th>
                                            <th>品名</th>
                                            <th>分類</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_on)): ?>
                                            <tr><td colspan="5" class="text-center py-5 text-muted">無上機零件</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_on as $item): ?>
                                            <tr>
                                                <td class="text-center"><input class="form-check-input item-check" type="checkbox" name="out_ids[]" value="<?= $item['id'] ?>"></td>
                                                <td><span class="badge bg-success"><?= $item['location'] ?></span></td>
                                                <td class="fw-bold text-dark"><?= $item['part_no'] ?></td>
                                                <td class="small text-muted"><?= $item['part_name'] ?></td>
                                                <td class="small"><span class="badge bg-light text-dark border"><?= $item['category'] ?? '-' ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="content-in">
                            <div class="table-responsive" style="max-height: 500px;">
                                <table class="table table-hover align-middle mb-0 mounted-table">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 40px;" class="text-center">選</th>
                                            <th>儲存位置</th>
                                            <th>料號</th>
                                            <th>品名</th>
                                            <th>分類</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($list_in)): ?>
                                            <tr><td colspan="5" class="text-center py-5 text-muted">無庫存零件</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($list_in as $item): ?>
                                            <tr>
                                                <td class="text-center"><input class="form-check-input item-check" type="checkbox" name="out_ids[]" value="<?= $item['id'] ?>"></td>
                                                <td><span class="badge bg-secondary"><?= $item['location'] ?></span></td>
                                                <td class="fw-bold text-primary"><?= $item['part_no'] ?></td>
                                                <td class="small text-muted"><?= $item['part_name'] ?></td>
                                                <td class="small"><span class="badge bg-light text-dark border"><?= $item['category'] ?? '-' ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($return_list)): ?>
                <div class="card-footer bg-white p-3">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">退料備註 (原因) <span class="badge bg-danger ms-1">必填</span></label>
                        <select class="form-select mb-2" id="reasonSelect" onchange="onReasonChange()">
                            <option value="">-- 請選擇退料原因 --</option>
                            <option value="報廢">報廢 (Scrap)</option>
                            <option value="維修">維修 (Repair)</option>
                            <option value="退回廠商">退回廠商 (Return to Vendor)</option>
                            <option value="其他">其他 (Other)</option>
                        </select>
                        <div id="otherInputDiv" class="d-none">
                            <input type="text" id="otherInput" class="form-control border-danger" placeholder="請手動輸入具體原因..." oninput="syncOtherReason()">
                        </div>
                        <input type="hidden" name="batch_remark" id="finalBatchRemark">
                    </div>

                    <button type="button" class="btn btn-danger w-100 fw-bold py-2" onclick="validateAndShowConfirm()">
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
                        <div class="alert alert-warning d-flex align-items-center mb-3">
                            <i class="fas fa-pen-nib me-2 fs-4"></i>
                            <div><strong>退料原因：</strong><span id="modalReasonDisplay" class="fw-bold text-dark"></span></div>
                        </div>
                        <div class="confirm-list mb-3"><ul id="modalList"></ul></div>
                        <div class="alert alert-secondary mb-0 border-0"><i class="fas fa-info-circle me-1"></i> 請確認上述項目無誤。</div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-lg btn-outline-secondary px-4" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-lg btn-danger fw-bold px-5" onclick="submitBatchForm()">確認送出</button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        
        <div class="card shadow-sm border-0" id="formCard">
            <div class="card-header bg-white fw-bold py-3 border-bottom d-flex justify-content-between align-items-center">
                <div><i class="fas fa-edit me-2 text-primary"></i>新增作業單 (<?= $status ?>)</div>
                <div>
                    <a href="http://p58mesweb03.umc.com:8084/PMMWebSite/eParts/Login/login.cshtml?returnUrl=%2fPMMWebSite%2feParts%2fDefault" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm me-1" title="前往 iPart 系統">
                        <i class="fas fa-external-link-alt me-1"></i> iPart 系統
                    </a>
                    <a href="Notes://F12AD16/48257DB0002B1BBC/EF9C1CE35692F71348256C5C0034F18C/6D22BDA971349EBD48258D63003116BF" class="btn btn-sm btn-outline-secondary shadow-sm" title="開啟 Notes 待建料資料庫">
                        <i class="fas fa-database me-1"></i> 待建料 DB
                    </a>
                </div>
            </div>

            <div class="card-body p-4 bg-light bg-opacity-25">
                <form id="opsForm" method="POST">
                    <div class="mb-3">
                        <input type="hidden" name="status" value="<?= $status ?>">
                        <input type="hidden" name="category" id="hiddenCategory" value="">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">料號 (Part No)</label>
                        <?php if ($status == 'ON'): ?>
                            <input type="text" name="part_no" id="partNoInput" list="inventory_datalist" class="form-control form-control-lg" placeholder="從庫存選擇..." required autocomplete="off" value="<?= $prefill['part_no'] ?>">
                            <datalist id="inventory_datalist"><?php foreach ($inventory_list as $item): ?><option value="<?= $item['part_no'] ?>"><?= $item['part_name'] ?> (<?= $item['vendor'] ?>) - <?= $item['location'] ?></option><?php endforeach; ?></datalist>
                        <?php else: ?>
                            <input type="text" name="part_no" id="partNoInput" list="master_datalist" class="form-control form-control-lg" placeholder="輸入或搜尋..." required autocomplete="off" value="<?= $prefill['part_no'] ?>">
                            <datalist id="master_datalist"><?php foreach ($master_list as $m): ?><option value="<?= $m['part_no'] ?>"><?= $m['name'] ?> (<?= $m['vendor'] ?>)</option><?php endforeach; ?></datalist>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="<?= ($status=='ON') ? 'col-md-4' : 'col-md-6' ?>"><label class="form-label">品名</label><input type="text" name="part_name" id="partNameInput" class="form-control" value="<?= $prefill['part_name'] ?>"></div>
                        <div class="<?= ($status=='ON') ? 'col-md-4' : 'col-md-6' ?>"><label class="form-label">廠商</label><input type="text" name="vendor" id="vendorInput" class="form-control" value="<?= $prefill['vendor'] ?? '' ?>"></div>
                        
                        <?php if ($status == 'ON'): ?>
                        <div class="col-md-4">
                            <label class="form-label fw-bold text-primary">分類 (Category)</label>
                            <select name="category" id="categorySelect" class="form-select" required>
                                <option value="">-- 請手動選擇 (Dashboard) --</option>
                                <option value="Contract Tool Part">Contract Tool Part</option>
                                <option value="warranty Part">warranty Part</option>
                                <option value="Consumables Part">Consumables Part</option>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">序號 (S/N)</label><input type="text" name="sn" id="snInput" class="form-control" value="<?= $prefill['sn'] ?>"></div>
                        <div class="col-md-6">
                            <?php if ($status == 'ON'): ?>
                                <label class="form-label fw-bold text-success">機台 ID (Tool ID)</label>
                                <input type="text" name="tool_id" id="toolInput" list="tool_datalist" class="form-control" required autocomplete="off">
                                <datalist id="tool_datalist"><?php foreach ($tool_master as $t): ?><option value="<?= $t ?>"><?php endforeach; ?></datalist>
                                <div id="toolError" class="text-danger small mt-1 d-none"><i class="fas fa-exclamation-circle"></i> 機台 ID 必須存在於清單中！</div>
                            <?php else: ?>
                                <label class="form-label fw-bold">儲存位置</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-secondary"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" name="location" id="locInput" list="loc_datalist" class="form-control" placeholder="選擇或輸入新位置..." required autocomplete="off">
                                </div>
                                <datalist id="loc_datalist"><?php foreach ($location_master as $l): ?><option value="<?= $l ?>"><?php endforeach; ?></datalist>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($status == 'ON'): ?>
                    <div class="mb-4 p-4 ipart-card d-flex align-items-center justify-content-between">
                        <div><h5 class="fw-bold mb-1 text-dark">iPart 系統登錄確認</h5><small class="text-muted">請確認已在 iPart 完成上機作業。</small></div>
                        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="ipart_logged" id="ipartSwitch"></div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-4"><label class="form-label">備註</label><textarea name="remark" class="form-control" rows="2"></textarea></div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg py-2 fw-bold" onclick="validateAndSubmit()">提交</button>
                        <a href="index.php?route=ops" class="btn btn-outline-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="newLocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title fw-bold"><i class="fas fa-map-marker-alt me-2"></i>發現新儲存位置</h5></div>
            <div class="modal-body"><p>您輸入的位置 <strong id="newLocName" class="text-primary fs-5"></strong> 不在系統清單中。</p><p class="mb-0">是否確認使用並將其<strong>加入清單</strong>？</p></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">修改</button><button type="button" class="btn btn-primary fw-bold">確認並新增</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="consumableModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark"><h5 class="modal-title fw-bold"><i class="fas fa-question-circle me-2"></i>零件屬性確認 (IN)</h5></div>
            <div class="modal-body">
                <p class="lead mb-4">請問此進料零件是否為 <strong>Consumables Part (耗材)</strong>？</p>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="isConsumableSwitch" style="width: 3em; height: 1.5em;">
                    <label class="form-check-label ms-2 fs-5" for="isConsumableSwitch">是，這是耗材 (YES)</label>
                </div>
                <div id="subCategoryDiv" class="d-none mt-3 p-3 bg-light rounded border">
                    <label class="form-label fw-bold">請選擇細部分類：</label>
                    <select class="form-select" id="subCategorySelect">
                        <option value="">-- 請選擇 --</option>
                        <option value="Consumables Part - Optics lens">Optics lens</option>
                        <option value="Consumables Part - Lamp">Lamp</option>
                        <option value="Consumables Part - Wafer Table">Wafer Table</option>
                        <option value="Consumables Part - Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="finalizeSubmit('NO')">非耗材 / 直接提交</button>
                <button type="button" class="btn btn-primary fw-bold" id="btnConfirmConsumable" onclick="finalizeSubmit('YES')" disabled>確認並提交</button>
            </div>
        </div>
    </div>
</div>

<script>
    const currentDept = "<?= $_SESSION['user_id'] ?>";
    const currentStatus = "<?= $status ?>";
    const specificDepts = ['LT3_EQ1', 'LT3_EQ2', 'LT4_EQ1'];
    
    // 退料 JS
    <?php if ($status == 'OUT'): ?>
        function onReasonChange() {
            const sel = document.getElementById('reasonSelect');
            const otherDiv = document.getElementById('otherInputDiv');
            const otherInput = document.getElementById('otherInput');
            const finalInput = document.getElementById('finalBatchRemark');
            if (sel.value === '其他') { otherDiv.classList.remove('d-none'); finalInput.value = otherInput.value ? ("其他: " + otherInput.value) : ""; } 
            else { otherDiv.classList.add('d-none'); finalInput.value = sel.value; }
        }
        function syncOtherReason() { document.getElementById('finalBatchRemark').value = "其他: " + document.getElementById('otherInput').value; }
        function validateAndShowConfirm() {
            const checkboxes = document.querySelectorAll('.item-check:checked');
            if (checkboxes.length === 0) { alert('請至少選擇一筆零件！'); return; }
            const sel = document.getElementById('reasonSelect');
            if (!sel.value) { alert('請選擇「退料備註(原因)」！'); sel.focus(); return; }
            if (sel.value === '其他' && !document.getElementById('otherInput').value.trim()) { alert('請輸入具體原因！'); document.getElementById('otherInput').focus(); return; }
            showConfirmModal();
        }
        function showConfirmModal() {
            const checkboxes = document.querySelectorAll('.item-check:checked');
            const listContainer = document.getElementById('modalList');
            document.getElementById('modalCount').textContent = checkboxes.length;
            document.getElementById('modalReasonDisplay').textContent = document.getElementById('finalBatchRemark').value;
            listContainer.innerHTML = '';
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                const badge = row.cells[1].innerHTML; 
                const li = document.createElement('li');
                li.innerHTML = `${badge} <strong>${row.cells[2].innerText}</strong> <span class="text-muted ms-2">${row.cells[3].innerText}</span>`;
                listContainer.appendChild(li);
            });
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }
        function submitBatchForm() { document.getElementById('batchReturnForm').submit(); }
    <?php endif; ?>

    // 作業 JS (IN/ON)
    const toolList = <?= json_encode($tool_master ?? []) ?>;
    const locList = <?= json_encode($location_master ?? []) ?>;
    <?php if ($status != 'OUT'): ?>
        <?php if ($status == 'ON'): ?>
            const dataList = <?= json_encode($inventory_list) ?>;
        <?php else: ?>
            const dataList = <?= json_encode($master_list) ?>;
        <?php endif; ?>
        const partNoInput = document.getElementById('partNoInput');
        if (partNoInput) {
            partNoInput.addEventListener('input', function(e) {
                const val = e.target.value.trim();
                const item = dataList.find(i => i.part_no === val);
                if (item) {
                    document.getElementById('partNameInput').value = item.part_name || item.name || '';
                    document.getElementById('vendorInput').value = item.vendor || '';
                    
                    // ON 時自動帶入分類
                    const catSelect = document.getElementById('categorySelect');
                    if(item.category && catSelect) {
                        for (let i = 0; i < catSelect.options.length; i++) {
                            if (catSelect.options[i].value === item.category) {
                                catSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    <?php if ($status == 'ON'): ?>
                        if(document.getElementById('snInput')) document.getElementById('snInput').value = item.sn || '';
                        if(document.getElementById('locInput')) document.getElementById('locInput').value = item.location || '';
                    <?php endif; ?>
                }
            });
        }
    <?php endif; ?>

    function validateAndSubmit() {
        const form = document.getElementById('opsForm');
        
        // 1. 基本必填檢查
        if (!form.checkValidity()) { form.reportValidity(); return; }
        
        // 2. 針對 ON 狀態的嚴格分類檢查
        if (currentStatus === 'ON') {
            const catSelect = document.getElementById('categorySelect');
            if (!catSelect.value) {
                alert('請選擇分類 (Category)！');
                catSelect.focus();
                return;
            }
        }

        // 3. Tool/Location 驗證
        const toolInput = document.getElementById('toolInput');
        if (toolInput && !toolList.includes(toolInput.value.trim())) { document.getElementById('toolError').classList.remove('d-none'); return; }
        const locInput = document.getElementById('locInput');
        if (locInput) {
            const val = locInput.value.trim();
            if (val && !locList.includes(val)) {
                document.getElementById('newLocName').textContent = val;
                const modal = new bootstrap.Modal(document.getElementById('newLocModal'));
                document.querySelector('#newLocModal .btn-primary').onclick = function() { modal.hide(); setTimeout(checkConsumableLogic, 300); };
                modal.show(); return;
            }
        }
        checkConsumableLogic();
    }

    function checkConsumableLogic() {
        // ★ 邏輯：只有 IN 才跳 Modal，ON 直接送出
        if (currentStatus === 'IN') { 
            new bootstrap.Modal(document.getElementById('consumableModal')).show(); 
        } else { 
            // ON 狀態下，分類來自下拉選單 (name="category")
            submitOpsForm(); 
        }
    }

    const switchEl = document.getElementById('isConsumableSwitch');
    const subDiv = document.getElementById('subCategoryDiv');
    const subSelect = document.getElementById('subCategorySelect');
    const btnConfirm = document.getElementById('btnConfirmConsumable');
    if(switchEl) {
        switchEl.addEventListener('change', function() {
            if(this.checked) {
                if (specificDepts.includes(currentDept)) { subDiv.classList.remove('d-none'); btnConfirm.disabled = (subSelect.value === ''); } 
                else { subDiv.classList.add('d-none'); btnConfirm.disabled = false; }
            } else { subDiv.classList.add('d-none'); subSelect.value = ''; btnConfirm.disabled = true; }
        });
    }
    if(subSelect) { subSelect.addEventListener('change', function() { btnConfirm.disabled = (this.value === ''); }); }

    function finalizeSubmit(type) {
        const hiddenInput = document.getElementById('hiddenCategory');
        if (type === 'YES') {
            if (specificDepts.includes(currentDept) && subSelect.value) { hiddenInput.value = subSelect.value; } 
            else { hiddenInput.value = 'Consumables Part'; }
        } else { 
            // IN 狀態若非耗材，給個預設分類
            hiddenInput.value = 'Contract Tool Part'; 
        }
        submitOpsForm();
    }
    function submitOpsForm() { document.getElementById('opsForm').submit(); }
</script>
<?php $content = ob_get_clean(); require 'layout.php'; ?>