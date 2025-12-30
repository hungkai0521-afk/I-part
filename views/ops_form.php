<?php ob_start(); ?>
<style>
    /* 卡片與表單樣式 */
    .ipart-card { background: #fff; border: 1px solid #e2e8f0; border-left: 5px solid #3b82f6; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .form-switch .form-check-input { width: 3.5em; height: 1.75em; cursor: pointer; }
    .form-switch .form-check-input:checked { background-color: #10b981; border-color: #10b981; }
    
    /* 退料列表樣式 */
    .mounted-table tr:hover { background-color: #f8fafc; }
    .confirm-list { max-height: 400px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; }
    .confirm-list li { margin-bottom: 8px; color: #334155; font-size: 1.1em; }
</style>

<div class="row justify-content-center">
    <div class="col-md-8">
        
        <?php if ($status == 'OUT'): ?>
        <form id="batchReturnForm" action="index.php?route=ops_batch_out" method="POST">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-secondary text-white fw-bold py-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="me-3"><i class="fas fa-list-alt me-2"></i>已上機清單</span>
                        <a href="index.php?route=return_history" class="btn btn-sm btn-light text-secondary fw-bold shadow-sm"><i class="fas fa-history me-1"></i> 退料歷史</a>
                    </div>
                    <span class="badge bg-white text-dark"><?= count($mounted_list) ?> 筆</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-hover align-middle mb-0 mounted-table" id="outTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 40px;" class="text-center"><input class="form-check-input" type="checkbox" id="selectAll"></th>
                                    <th>機台 (Tool)</th>
                                    <th>料號 (Part No)</th>
                                    <th>品名 (Name)</th>
                                    <th>分類</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($mounted_list)): ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted">目前無上機零件，無需退料。</td></tr>
                                <?php else: ?>
                                    <?php foreach ($mounted_list as $item): ?>
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
                <?php if (!empty($mounted_list)): ?>
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
                            <div>
                                <strong>退料原因：</strong>
                                <span id="modalReasonDisplay" class="fw-bold text-dark"></span>
                            </div>
                        </div>

                        <div class="confirm-list mb-3"><ul id="modalList"></ul></div>
                        <div class="alert alert-secondary mb-0 border-0"><i class="fas fa-info-circle me-1"></i> 請確認上述項目無誤，送出後將寫入歷史紀錄。</div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-lg btn-outline-secondary px-4" data-bs-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-lg btn-danger fw-bold px-5" onclick="submitBatchForm()">確認送出 <i class="fas fa-paper-plane ms-2"></i></button>
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
                    <a href="http://【請修改這裡_填入_待建料DB_網址】" target="_blank" class="btn btn-sm btn-outline-secondary shadow-sm" title="查詢待建料資料庫">
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
                        <label class="form-label fw-bold">料號</label>
                        <?php if ($status == 'ON'): ?>
                            <input type="text" name="part_no" id="partNoInput" list="inventory_datalist" class="form-control form-control-lg" placeholder="從庫存選擇..." required autocomplete="off" value="<?= $prefill['part_no'] ?>">
                            <datalist id="inventory_datalist"><?php foreach ($inventory_list as $item): ?><option value="<?= $item['part_no'] ?>"><?= $item['part_name'] ?> (<?= $item['vendor'] ?>) - <?= $item['location'] ?></option><?php endforeach; ?></datalist>
                        <?php else: ?>
                            <input type="text" name="part_no" id="partNoInput" list="master_datalist" class="form-control form-control-lg" placeholder="輸入或搜尋..." required autocomplete="off" value="<?= $prefill['part_no'] ?>">
                            <datalist id="master_datalist"><?php foreach ($master_list as $m): ?><option value="<?= $m['part_no'] ?>"><?= $m['name'] ?> (<?= $m['vendor'] ?>)</option><?php endforeach; ?></datalist>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">品名</label><input type="text" name="part_name" id="partNameInput" class="form-control" value="<?= $prefill['part_name'] ?>"></div>
                        <div class="col-md-6"><label class="form-label">廠商</label><input type="text" name="vendor" id="vendorInput" class="form-control" value="<?= $prefill['vendor'] ?? '' ?>"></div>
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
                        <div>
                            <h5 class="fw-bold mb-1 text-dark">iPart 系統登錄確認</h5>
                            <small class="text-muted">請確認已在 iPart 完成上機作業。</small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ipart_logged" id="ipartSwitch">
                        </div>
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
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-map-marker-alt me-2"></i>發現新儲存位置</h5>
            </div>
            <div class="modal-body">
                <p>您輸入的位置 <strong id="newLocName" class="text-primary fs-5"></strong> 不在系統清單中。</p>
                <p class="mb-0">是否確認使用並將其<strong>加入清單</strong>？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">修改</button>
                <button type="button" class="btn btn-primary fw-bold">確認並新增</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="consumableModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-dark">
                <h5 class="modal-title fw-bold"><i class="fas fa-question-circle me-2"></i>零件屬性確認</h5>
            </div>
            <div class="modal-body">
                <p class="lead mb-4">請問此零件是否為 <strong>Consumables Part (耗材)</strong>？</p>
                
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
    
    // ==========================================
    // 退料 (OUT) 相關邏輯
    // ==========================================
    <?php if ($status == 'OUT'): ?>
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.item-check');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }

        // 監聽選單變更
        function onReasonChange() {
            const sel = document.getElementById('reasonSelect');
            const otherDiv = document.getElementById('otherInputDiv');
            const otherInput = document.getElementById('otherInput');
            const finalInput = document.getElementById('finalBatchRemark');
            
            if (sel.value === '其他') {
                otherDiv.classList.remove('d-none');
                finalInput.value = otherInput.value ? ("其他: " + otherInput.value) : "";
            } else {
                otherDiv.classList.add('d-none');
                finalInput.value = sel.value;
            }
        }

        // 監聽手動輸入
        function syncOtherReason() {
            const otherInput = document.getElementById('otherInput');
            const finalInput = document.getElementById('finalBatchRemark');
            finalInput.value = "其他: " + otherInput.value;
        }

        // 驗證並顯示確認視窗
        function validateAndShowConfirm() {
            const checkboxes = document.querySelectorAll('.item-check:checked');
            if (checkboxes.length === 0) { 
                alert('請至少選擇一筆上機零件！'); 
                return; 
            }

            const sel = document.getElementById('reasonSelect');
            if (!sel.value) {
                alert('請選擇「退料備註(原因)」！');
                sel.focus();
                return;
            }

            if (sel.value === '其他') {
                const otherInput = document.getElementById('otherInput');
                if (!otherInput.value.trim()) {
                    alert('選擇「其他」時，請手動輸入具體原因！');
                    otherInput.focus();
                    return;
                }
            }

            showConfirmModal();
        }

        function showConfirmModal() {
            const checkboxes = document.querySelectorAll('.item-check:checked');
            const listContainer = document.getElementById('modalList');
            const finalReason = document.getElementById('finalBatchRemark').value;
            
            document.getElementById('modalCount').textContent = checkboxes.length;
            document.getElementById('modalReasonDisplay').textContent = finalReason;
            
            listContainer.innerHTML = '';
            checkboxes.forEach(cb => {
                const row = cb.closest('tr');
                const li = document.createElement('li');
                li.innerHTML = `<span class="badge bg-success me-2">${row.cells[1].innerText}</span> <strong>${row.cells[2].innerText}</strong> <span class="text-muted ms-2">${row.cells[3].innerText}</span>`;
                listContainer.appendChild(li);
            });
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }
        
        function submitBatchForm() { document.getElementById('batchReturnForm').submit(); }
    <?php endif; ?>

    // ==========================================
    // 進料/上機 (IN/ON) 相關邏輯
    // ==========================================
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
                    const pName = item.part_name || item.name || '';
                    document.getElementById('partNameInput').value = pName;
                    document.getElementById('vendorInput').value = item.vendor || '';
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
        if (!form.checkValidity()) { form.reportValidity(); return; }

        // 機台 ID 檢查
        const toolInput = document.getElementById('toolInput');
        if (toolInput) {
            if (!toolList.includes(toolInput.value.trim())) {
                document.getElementById('toolError').classList.remove('d-none');
                return;
            }
        }

        // 新位置檢查 (IN)
        const locInput = document.getElementById('locInput');
        if (locInput) {
            const val = locInput.value.trim();
            if (val && !locList.includes(val)) {
                document.getElementById('newLocName').textContent = val;
                
                const newLocModalEl = document.getElementById('newLocModal');
                const newLocModal = new bootstrap.Modal(newLocModalEl);
                const btn = newLocModalEl.querySelector('.btn-primary');
                
                btn.onclick = function() { 
                    newLocModal.hide(); 
                    setTimeout(checkConsumableLogic, 300); 
                };
                
                newLocModal.show(); 
                return;
            }
        }

        checkConsumableLogic();
    }

    // 耗材詢問邏輯
    function checkConsumableLogic() {
        if (currentStatus === 'IN') {
            const modal = new bootstrap.Modal(document.getElementById('consumableModal'));
            modal.show();
        } else {
            submitOpsForm();
        }
    }

    // Modal 內部的互動邏輯
    const switchEl = document.getElementById('isConsumableSwitch');
    const subDiv = document.getElementById('subCategoryDiv');
    const subSelect = document.getElementById('subCategorySelect');
    const btnConfirm = document.getElementById('btnConfirmConsumable');

    if(switchEl) {
        switchEl.addEventListener('change', function() {
            if(this.checked) {
                if (specificDepts.includes(currentDept)) {
                    subDiv.classList.remove('d-none');
                    btnConfirm.disabled = (subSelect.value === ''); 
                } else {
                    subDiv.classList.add('d-none');
                    btnConfirm.disabled = false;
                }
            } else {
                subDiv.classList.add('d-none');
                subSelect.value = ''; 
                btnConfirm.disabled = true; 
            }
        });
    }
    
    if(subSelect) {
        subSelect.addEventListener('change', function() {
            btnConfirm.disabled = (this.value === '');
        });
    }

    function finalizeSubmit(type) {
        const hiddenInput = document.getElementById('hiddenCategory');
        
        if (type === 'YES') {
            if (specificDepts.includes(currentDept) && subSelect.value) {
                hiddenInput.value = subSelect.value;
            } else {
                hiddenInput.value = 'Consumables Part';
            }
        } else {
            hiddenInput.value = ''; 
        }
        submitOpsForm();
    }

    function submitOpsForm() { document.getElementById('opsForm').submit(); }
</script>
<?php $content = ob_get_clean(); require 'layout.php'; ?>