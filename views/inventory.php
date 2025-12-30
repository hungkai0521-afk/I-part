<?php ob_start(); ?>
<style>
    th.sortable { cursor: pointer; user-select: none; position: relative; }
    th.sortable:hover { background-color: #e2e8f0; }
    th.sortable i { margin-left: 5px; color: #94a3b8; font-size: 0.8em; }
    .nav-tabs .nav-link.active { font-weight: bold; color: #0d6efd; border-bottom: 3px solid #0d6efd; }
</style>

<?php
$list_consumable = [];
$list_other = [];
foreach ($items as $item) {
    if (!empty($item['category']) && strpos($item['category'], 'Consumables') !== false) {
        $list_consumable[] = $item;
    } else {
        $list_other[] = $item;
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php?route=ops" class="text-decoration-none text-secondary mb-2 d-inline-block">
            <i class="fas fa-arrow-left me-1"></i> 返回作業中心
        </a>
        <h3 class="fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>目前庫存清單 (<?= $curr_dept ?>)</h3>
    </div>
    <a href="index.php?route=ops_new&status=IN" id="guide_inv_add" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> 新增進料
    </a>
</div>

<ul class="nav nav-tabs mb-3" id="guide_inv_tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="other-tab" data-bs-toggle="tab" data-bs-target="#other" type="button" role="tab">
            <i class="fas fa-microchip me-2"></i>一般零件 (Other) <span class="badge bg-secondary ms-1"><?= count($list_other) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="consumable-tab" data-bs-toggle="tab" data-bs-target="#consumable" type="button" role="tab">
            <i class="fas fa-flask me-2"></i>耗材 (Consumables) <span class="badge bg-info text-dark ms-1"><?= count($list_consumable) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade show active" id="other" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tableOther">
                        <thead class="bg-light" id="guide_inv_list">
                            <tr>
                                <th class="sortable" onclick="sortTable('tableOther', 0)">登入日期 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableOther', 1)">料號 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableOther', 2)">品名 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableOther', 3)">廠商 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableOther', 4)">SN <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableOther', 5)">儲存位置 <i class="fas fa-sort"></i></th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php renderRows($list_other); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="consumable" role="tabpanel">
        <div class="card border-0 shadow-sm border-info border-top border-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tableConsumable">
                        <thead class="bg-info bg-opacity-10">
                            <tr>
                                <th class="sortable" onclick="sortTable('tableConsumable', 0)">登入日期 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableConsumable', 1)">分類 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableConsumable', 2)">料號 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableConsumable', 3)">品名 <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableConsumable', 4)">SN <i class="fas fa-sort"></i></th>
                                <th class="sortable" onclick="sortTable('tableConsumable', 5)">儲存位置 <i class="fas fa-sort"></i></th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($list_consumable)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted">無耗材庫存</td></tr>
                            <?php else: ?>
                                <?php foreach ($list_consumable as $item): ?>
                                <tr>
                                    <td class="text-muted small"><?= date('Y-m-d', strtotime($item['created_at'])) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= str_replace('Consumables Part - ', '', $item['category']) ?></span></td>
                                    <td class="fw-bold text-dark"><?= $item['part_no'] ?></td>
                                    <td><?= $item['part_name'] ?></td>
                                    <td><?= $item['sn'] ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $item['location'] ?></span></td>
                                    <td>
                                        <a href="index.php?route=ops_new&status=ON&part_no=<?= $item['part_no'] ?>&part_name=<?= $item['part_name'] ?>&vendor=<?= $item['vendor'] ?>&sn=<?= $item['sn'] ?>" class="btn btn-sm btn-success fw-bold px-3">
                                            <i class="fas fa-wrench me-1"></i> 上機
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
    </div>
</div>

<?php
function renderRows($data) {
    if (empty($data)) {
        echo '<tr><td colspan="7" class="text-center py-5 text-muted">無庫存資料</td></tr>';
        return;
    }
    foreach ($data as $item) {
        echo '<tr>';
        echo '<td class="text-muted small">' . date('Y-m-d', strtotime($item['created_at'])) . '</td>';
        echo '<td class="fw-bold text-primary">' . $item['part_no'] . '</td>';
        echo '<td>' . $item['part_name'] . '</td>';
        echo '<td>' . $item['vendor'] . '</td>';
        echo '<td>' . $item['sn'] . '</td>';
        echo '<td><span class="badge bg-light text-dark border">' . $item['location'] . '</span></td>';
        echo '<td>
                <a href="index.php?route=ops_new&status=ON&part_no='.$item['part_no'].'&part_name='.$item['part_name'].'&vendor='.$item['vendor'].'&sn='.$item['sn'].'" class="btn btn-sm btn-success fw-bold px-3">
                    <i class="fas fa-wrench me-1"></i> 上機
                </a>
              </td>';
        echo '</tr>';
    }
}
?>

<script>
    function sortTable(tableId, n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById(tableId);
        switching = true;
        dir = "asc"; 
        var headers = table.getElementsByTagName("th");
        for (var h = 0; h < headers.length; h++) {
            if (headers[h].classList.contains("sortable")) {
                headers[h].classList.remove("asc", "desc");
                headers[h].getElementsByTagName("i")[0].className = "fas fa-sort";
            }
        }
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                var xContent = x.innerHTML.toLowerCase();
                var yContent = y.innerHTML.toLowerCase();
                var xNum = parseFloat(xContent);
                var yNum = parseFloat(yContent);
                var isNum = !isNaN(xNum) && !isNaN(yNum);
                if (dir == "asc") {
                    if (isNum) { if (xNum > yNum) { shouldSwitch = true; break; } } 
                    else { if (xContent > yContent) { shouldSwitch = true; break; } }
                } else if (dir == "desc") {
                    if (isNum) { if (xNum < yNum) { shouldSwitch = true; break; } } 
                    else { if (xContent < yContent) { shouldSwitch = true; break; } }
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchcount++;      
            } else {
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }
        var targetHeader = headers[n];
        var icon = targetHeader.getElementsByTagName("i")[0];
        if (dir == "asc") {
            targetHeader.classList.add("asc");
            icon.className = "fas fa-sort-up";
        } else {
            targetHeader.classList.add("desc");
            icon.className = "fas fa-sort-down";
        }
    }
</script>
<?php $content = ob_get_clean(); require 'layout.php'; ?>