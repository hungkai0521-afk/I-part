<?php ob_start(); ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark fw-bold py-3 border-bottom">
                <i class="fas fa-edit me-2"></i>編輯作業紀錄 (ID: <?= $record['id'] ?>)
            </div>
            <div class="card-body p-4 bg-light bg-opacity-25">
                <form method="POST">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">狀態 (Status)</label>
                            <select name="status" class="form-select">
                                <option value="IN" <?= $record['status']=='IN'?'selected':'' ?>>IN (進料)</option>
                                <option value="ON" <?= $record['status']=='ON'?'selected':'' ?>>ON (上機)</option>
                                <option value="OUT" <?= $record['status']=='OUT'?'selected':'' ?>>OUT (退料)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">iPart 登錄 (僅 ON 有效)</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="ipart_logged" id="ipartSwitch" <?= $record['ipart_logged']?'checked':'' ?>>
                                <label class="form-check-label" for="ipartSwitch">已登錄</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">料號 (Part No)</label>
                        <input type="text" name="part_no" class="form-control" value="<?= $record['part_no'] ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">品名</label>
                            <input type="text" name="part_name" class="form-control" value="<?= $record['part_name'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">廠商</label>
                            <input type="text" name="vendor" class="form-control" value="<?= $record['vendor'] ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">序號 (S/N)</label>
                            <input type="text" name="sn" class="form-control" value="<?= $record['sn'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">位置/機台 (Location/Tool)</label>
                            <input type="text" name="location" list="loc_list" class="form-control" value="<?= $record['location'] ?>">
                            <datalist id="loc_list">
                                <?php foreach ($h_locs as $l): ?><option value="<?= $l ?>"><?php endforeach; ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">備註</label>
                        <textarea name="remark" class="form-control" rows="2"><?= $record['remark'] ?></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning fw-bold">保存修改</button>
                        <a href="index.php?route=ops" class="btn btn-outline-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout.php'; ?>