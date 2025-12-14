<?php ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-chart-line me-2 text-primary"></i>上機確實率看板</h3>
    
    <form class="d-flex gap-2" method="GET" action="index.php">
        <input type="hidden" name="route" value="dashboard">
        
        <div class="input-group">
            <span class="input-group-text bg-white"><i class="fas fa-filter text-muted"></i></span>
            
            <select name="dept" class="form-select" onchange="this.form.submit()">
                <option value="ALL" <?= $target_dept == 'ALL' ? 'selected' : '' ?>>ALL (全廠區)</option>
                <?php foreach (DEPARTMENTS as $d): ?>
                    <option value="<?= $d ?>" <?= $target_dept == $d ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>

            <select name="cat" class="form-select border-start-0" onchange="this.form.submit()">
                <option value="Contract Tool Part" <?= ($target_cat ?? '') == 'Contract Tool Part' ? 'selected' : '' ?>>Contract Tool Part</option>
                <option value="Warranty Tool Part" <?= ($target_cat ?? '') == 'Warranty Tool Part' ? 'selected' : '' ?>>Warranty Tool Part</option>
                <option value="Consumables Part" <?= ($target_cat ?? '') == 'Consumables Part' ? 'selected' : '' ?>>Consumables Part</option>
                <option value="ALL" <?= ($target_cat ?? '') == 'ALL' ? 'selected' : '' ?>>ALL (全部分類)</option>
            </select>
        </div>
    </form>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-center text-primary mb-3"><i class="fas fa-calendar-alt me-2"></i>近 3 個月</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartMonthly"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-center text-success mb-3"><i class="fas fa-calendar-week me-2"></i>近 4 週 (週次)</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartWeekly"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h5 class="card-title text-center text-warning mb-3"><i class="fas fa-calendar-day me-2"></i>近 7 天</h5>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartDaily"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3">各單位今日詳細數據 (<?= date('Y-m-d') ?>) - <?= $target_cat ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light">
                    <tr>
                        <th>部門單位</th>
                        <th>上機總數</th>
                        <th>已登入 iPart</th>
                        <th>今日確實率</th>
                        <th>狀態</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dept_stats as $stat): ?>
                    <tr>
                        <td class="fw-bold"><?= $stat['name'] ?></td>
                        <td><?= $stat['on'] ?></td>
                        <td><?= $stat['logged'] ?></td>
                        <td class="fw-bold <?= ($stat['rate'] == 100) ? 'text-success' : (($stat['rate'] >= 90) ? 'text-warning' : 'text-danger') ?>">
                            <?= ($stat['rate'] == -1) ? '-' : $stat['rate'].'%' ?>
                        </td>
                        <td>
                            <?php if ($stat['rate'] == -1): ?>
                                <span class="badge bg-secondary">無作業</span>
                            <?php elseif ($stat['rate'] == 100): ?>
                                <span class="badge bg-success">正常</span>
                            <?php else: ?>
                                <span class="badge bg-danger">異常</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalTitle"><i class="fas fa-info-circle me-2"></i>詳細資訊</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-striped mb-0 text-center">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>部門</th>
                            <th>上機數 (On)</th>
                            <th>iPart 登入數</th>
                            <th>狀況</th>
                        </tr>
                    </thead>
                    <tbody id="modalBody">
                        </tbody>
                </table>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary fw-bold px-4" data-bs-dismiss="modal">關閉</button>
            </div>
        </div>
    </div>
</div>

<script>
    // 定義圖表渲染函式
    function renderChart(id, label, labels, rates, rawData, color) {
        const ctx = document.getElementById(id);
        
        // 檢查 Canvas 是否存在
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label + ' 確實率 (%)',
                    data: rates,
                    backgroundColor: color,
                    borderRadius: 4,
                    hoverBackgroundColor: color // 避免 Hover 時顏色變化太劇烈
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // 防止圖表無限拉長
                scales: { 
                    y: { beginAtZero: true, max: 100, ticks: { stepSize: 20 } } 
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '%';
                            }
                        }
                    }
                },
                // ★ 關鍵設定：允許點擊整條 Bar 的範圍 (包含空白處)
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                // ★ 滑鼠移上去變手指游標
                onHover: (event, chartElement) => {
                    event.native.target.style.cursor = chartElement[0] ? 'pointer' : 'default';
                },
                // ★ 點擊事件
                onClick: (e, activeEls) => {
                    // 如果有點擊到東西
                    if (activeEls.length > 0) {
                        const index = activeEls[0].index;
                        const dateLabel = labels[index];
                        const dataItem = rawData[index];
                        
                        showDetailModal(dateLabel, label, dataItem);
                    }
                }
            }
        });
    }

    // 顯示 Modal 的函式
    function showDetailModal(dateStr, typeStr, dataItem) {
        document.getElementById('modalTitle').textContent = `${dateStr} 詳細數據 (${typeStr})`;
        const tbody = document.getElementById('modalBody');
        tbody.innerHTML = ''; // 清空舊資料

        if (dataItem && dataItem.details && dataItem.details.length > 0) {
            dataItem.details.forEach(dept => {
                const isMissing = dept.on > dept.logged;
                // 計算缺漏數
                const diff = dept.on - dept.logged;
                
                let statusBadge = '<span class="badge bg-secondary">-</span>';
                if (dept.on > 0) {
                    if (isMissing) {
                        statusBadge = `<span class="badge bg-danger">缺 ${diff}</span>`;
                    } else {
                        statusBadge = '<span class="badge bg-success">OK</span>';
                    }
                }

                // 只有當有上機數或有登錄數時才顯示，避免顯示一堆 0/0 的部門
                // 或者如果您想顯示全部部門，請移除此 if
                // if (dept.on > 0 || dept.logged > 0) {
                    const row = `
                        <tr>
                            <td class="fw-bold text-primary">${dept.dept}</td>
                            <td>${dept.on}</td>
                            <td>${dept.logged}</td>
                            <td>${statusBadge}</td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                // }
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-muted py-4">查無詳細資料</td></tr>';
        }

        // 使用 Bootstrap Modal API 顯示
        try {
            const myModal = new bootstrap.Modal(document.getElementById('detailModal'));
            myModal.show();
        } catch (error) {
            console.error("Modal error:", error);
            alert("無法開啟詳細視窗，請確認 Bootstrap 載入正確。");
        }
    }

    // 從 PHP 傳遞資料給 JS
    const dailyRaw = <?= json_encode($trend_daily['raw'] ?? []) ?>;
    const weeklyRaw = <?= json_encode($trend_weekly['raw'] ?? []) ?>;
    const monthlyRaw = <?= json_encode($trend_monthly['raw'] ?? []) ?>;

    const dailyLabels = <?= json_encode($trend_daily['labels'] ?? []) ?>;
    const dailyRates = <?= json_encode($trend_daily['rates'] ?? []) ?>;

    const weeklyLabels = <?= json_encode($trend_weekly['labels'] ?? []) ?>;
    const weeklyRates = <?= json_encode($trend_weekly['rates'] ?? []) ?>;

    const monthlyLabels = <?= json_encode($trend_monthly['labels'] ?? []) ?>;
    const monthlyRates = <?= json_encode($trend_monthly['rates'] ?? []) ?>;

    // 執行繪圖
    renderChart('chartDaily', '日', dailyLabels, dailyRates, dailyRaw, '#ffc107');
    renderChart('chartWeekly', '週', weeklyLabels, weeklyRates, weeklyRaw, '#2ecc71');
    renderChart('chartMonthly', '月', monthlyLabels, monthlyRates, monthlyRaw, '#3498db');
</script>

<?php $content = ob_get_clean(); require 'layout.php'; ?>