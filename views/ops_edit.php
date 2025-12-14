<?php ob_start(); ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold py-3 border-bottom">
                <i class="fas fa-edit me-2 text-primary"></i>編輯紀錄 #<?= $record['id'] ?>
            </div>
            <div class="card-body p-4 bg-light bg-opacity-25">
                <form method="POST">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">狀態</label>
                            <select name="status" class="form-select" readonly style="pointer-events: none; background-color: #e9ecef;">
                                <option value="IN" <?= $record['status']=='IN'?'selected':'' ?>>進料 (IN)</option>
                                <option value="ON" <?= $record['status']=='ON'?'selected':'' ?>>上機 (ON)</option>
                                <option value="OUT" <?= $record['status']=='OUT'?'selected':'' ?>>退料 (OUT)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">時間</label>
                            <input type="text" class="form-control" value="<?= $record['created_at'] ?>" readonly disabled>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">料號</label>
                        <input type="text" name="part_no" class="form-control" value="<?= $record['part_no'] ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">品名</label><input type="text" name="part_name" class="form-control" value="<?= $record['part_name'] ?>"></div>
                        <div class="col-md-6"><label class="form-label">廠商</label><input type="text" name="vendor" class="form-control" value="<?= $record['vendor'] ?>"></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6"><label class="form-label">序號 (S/N)</label><input type="text" name="sn" class="form-control" value="<?= $record['sn'] ?>"></div>
                        <div class="col-md-6">
                            <label class="form-label">位置 / 機台</label>
                            <input type="text" name="location" list="loc_datalist" class="form-control" value="<?= $record['location'] ?>">
                            <datalist id="loc_datalist"><?php foreach ($h_locs as $l): ?><option value="<?= $l ?>"><?php endforeach; ?></datalist>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">PART 分類</label>
                        <select name="category" class="form-select">
                            <option value="">-- 未分類 --</option>
                            <option value="Contract Tool Part" <?= ($record['category']??'') == 'Contract Tool Part' ? 'selected' : '' ?>>Contract Tool Part</option>
                            <option value="Warranty Tool Part" <?= ($record['category']??'') == 'Warranty Tool Part' ? 'selected' : '' ?>>Warranty Tool Part</option>
                            <option value="Consumables Part" <?= ($record['category']??'') == 'Consumables Part' ? 'selected' : '' ?>>Consumables Part</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">備註</label>
                        <textarea name="remark" class="form-control" rows="2"><?= $record['remark'] ?></textarea>
                    </div>

                    <?php if ($record['status'] == 'ON'): ?>
                    <div class="mb-4 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="ipart_logged" id="ipartSwitch" <?= $record['ipart_logged']?'checked':'' ?>>
                        <label class="form-check-label" for="ipartSwitch">iPart 系統已登錄</label>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary fw-bold">保存修改</button>
                        <a href="index.php?route=ops" class="btn btn-outline-secondary">取消</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout.php'; ?>