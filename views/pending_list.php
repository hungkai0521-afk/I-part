<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-clipboard-list me-2 text-danger"></i>iPart 待補登清單</h3>
    
    <div id="guide_pending_links">
        <a href="http://p58mesweb03.umc.com:8084/PMMWebSite/eParts/Login/login.cshtml?returnUrl=%2fPMMWebSite%2feParts%2fDefault" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="前往 iPart 系統">
            <i class="fas fa-external-link-alt me-1"></i> iPart 系統
        </a>
        <a href="Notes://F12AD16/48257DB0002B1BBC/EF9C1CE35692F71348256C5C0034F18C/6D22BDA971349EBD48258D63003116BF" class="btn btn-sm btn-outline-secondary" title="開啟 Notes 待建料資料庫">
            <i class="fas fa-database me-1"></i> 待建料 DB
        </a>
    </div>
</div>

<div class="card border-danger border-top border-3 shadow-sm" id="guide_pending_list">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>來源部門</th>
                        <th>上機時間</th>
                        <th>Part No</th>
                        <th>Tool ID</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pending)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">目前無待補登項目，太棒了！</td></tr>
                    <?php else: ?>
                        <?php foreach ($pending as $item): ?>
                        <tr>
                            <td><span class="badge bg-secondary"><?= $item['dept_source'] ?></span></td>
                            <td class="text-muted small"><?= date('Y-m-d H:i', strtotime($item['created_at'])) ?></td>
                            <td class="fw-bold text-danger"><?= $item['part_no'] ?></td>
                            <td><?= $item['location'] ?></td>
                            <td>
                                <a href="index.php?route=api_complete&dept=<?= $item['dept_source'] ?>&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('確認已在 iPart 完成補登？')">
                                    <i class="fas fa-check me-1"></i> 已補登
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require 'layout.php'; ?>