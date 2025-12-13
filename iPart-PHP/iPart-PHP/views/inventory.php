<?php ob_start(); ?>
<style>
    /* 讓表頭看起來像可點擊的按鈕 */
    th.sortable {
        cursor: pointer;
        user-select: none; /* 防止連點時選取到文字 */
        position: relative;
    }
    th.sortable:hover {
        background-color: #e2e8f0; /* 滑鼠移過去變色 */
    }
    /* 排序圖示的位置 */
    th.sortable i {
        margin-left: 5px;
        color: #94a3b8;
        font-size: 0.8em;
    }
    th.sortable.asc i { color: #3b82f6; }  /* 升序時變藍色 */
    th.sortable.desc i { color: #3b82f6; } /* 降序時變藍色 */
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="index.php?route=ops" class="text-decoration-none text-secondary mb-2 d-inline-block">
            <i class="fas fa-arrow-left me-1"></i> 返回作業中心
        </a>
        <h3 class="fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>目前庫存清單 (<?= $curr_dept ?>)</h3>
    </div>
    <a href="index.php?route=ops_new&status=IN" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> 新增進料
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="inventoryTable">
                <thead class="bg-light">
                    <tr>
                        <th class="sortable" onclick="sortTable(0)">登入日期 <i class="fas fa-sort"></i></th>
                        <th class="sortable" onclick="sortTable(1)">料號 <i class="fas fa-sort"></i></th>
                        <th class="sortable" onclick="sortTable(2)">品名 <i class="fas fa-sort"></i></th>
                        <th class="sortable" onclick="sortTable(3)">廠商 <i class="fas fa-sort"></i></th>
                        <th class="sortable" onclick="sortTable(4)">SN <i class="fas fa-sort"></i></th>
                        <th class="sortable" onclick="sortTable(5)">儲存位置 <i class="fas fa-sort"></i></th>
                        <th>操作</th> </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">目前沒有庫存</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="text-muted small"><?= date('Y-m-d', strtotime($item['created_at'])) ?></td>
                            
                            <td class="fw-bold text-primary"><?= $item['part_no'] ?></td>
                            <td><?= $item['part_name'] ?></td>
                            <td><?= $item['vendor'] ?></td>
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

<script>
    /**
     * 表格排序功能
     * @param {number} n - 要排序的欄位索引 (0-based)
     */
    function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.getElementById("inventoryTable");
        switching = true;
        
        // 預設排序方向：升序 (asc)
        dir = "asc"; 
        
        // 重置所有表頭的圖示
        var headers = table.getElementsByTagName("th");
        for (var h = 0; h < headers.length; h++) {
            if (headers[h].classList.contains("sortable")) {
                headers[h].classList.remove("asc", "desc");
                headers[h].getElementsByTagName("i")[0].className = "fas fa-sort";
            }
        }

        // 開始迴圈進行泡沫排序概念的交換
        while (switching) {
            switching = false;
            rows = table.rows;
            
            // 從第 1 列開始 (第 0 列是表頭)
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                
                // 取得上下兩列要比較的儲存格
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                
                // 取得內容 (轉小寫以忽略大小寫差異)
                var xContent = x.innerHTML.toLowerCase();
                var yContent = y.innerHTML.toLowerCase();
                
                // 判斷是否為數字 (如果是數字字串，轉為浮點數比較)
                var xNum = parseFloat(xContent);
                var yNum = parseFloat(yContent);
                var isNum = !isNaN(xNum) && !isNaN(yNum);

                if (dir == "asc") {
                    if (isNum) {
                        if (xNum > yNum) { shouldSwitch = true; break; }
                    } else {
                        if (xContent > yContent) { shouldSwitch = true; break; }
                    }
                } else if (dir == "desc") {
                    if (isNum) {
                        if (xNum < yNum) { shouldSwitch = true; break; }
                    } else {
                        if (xContent < yContent) { shouldSwitch = true; break; }
                    }
                }
            }
            
            if (shouldSwitch) {
                // 執行交換
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchcount++;      
            } else {
                // 如果第一次比對就沒做任何交換，代表可能已經是升序，改為降序再跑一次
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }

        // 更新被點擊欄位的圖示
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