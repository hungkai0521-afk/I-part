<?php ob_start(); ?>
<script src="assets/js/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3><i class="fas fa-chart-line me-2 text-primary"></i>上機確實率看板</h3>
    <div>
        <span class="text-muted me-2 small">部門:</span>
        <?php foreach (DEPARTMENTS as $d): ?>
            <a href="index.php?route=dashboard&dept=<?= $d ?>" class="badge bg-<?= ($target_dept == $d) ? 'primary' : 'secondary' ?> text-decoration-none me-1"><?= $d ?></a>
        <?php endforeach; ?>
        <a href="index.php?route=dashboard&dept=ALL" class="badge bg-<?= ($target_dept == 'ALL') ? 'dark' : 'secondary' ?> text-decoration-none">ALL</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-bold text-center border-bottom-0 pt-3"><i class="fas fa-calendar me-1 text-primary"></i> 近 3 個月</div>
            <div class="card-body"><canvas id="chartMonth"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-bold text-center border-bottom-0 pt-3"><i class="fas fa-calendar-week me-1 text-success"></i> 近 4 週 (週次)</div>
            <div class="card-body"><canvas id="chartWeek"></canvas></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white fw-bold text-center border-bottom-0 pt-3"><i class="fas fa-calendar-day me-1 text-warning"></i> 近 7 天</div>
            <div class="card-body"><canvas id="chartDay"></canvas></div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold py-3">各單位今日詳細數據</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>部門單位</th><th>上機總數</th><th>已登入</th><th>今日確實率</th><th>狀態</th></tr></thead>
            <tbody>
                <?php foreach ($dept_stats as $stat): ?>
                <tr>
                    <td class="fw-bold"><?= $stat['name'] ?></td>
                    <td><?= $stat['on'] ?></td>
                    <td><?= $stat['logged'] ?></td>
                    <td>
                        <?php if ($stat['rate'] == -1): ?>
                            <span class="fw-bold text-muted">-</span>
                        <?php else: ?>
                            <span class="fw-bold <?= ($stat['rate'] < 99) ? 'text-danger' : 'text-success' ?>"><?= $stat['rate'] ?>%</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($stat['on'] == 0): ?>
                            <span class="badge bg-light text-dark">無作業</span>
                        <?php elseif ($stat['rate'] >= 99): ?>
                            <span class="badge bg-success rounded-pill">正常</span>
                        <?php else: ?>
                            <span class="badge bg-danger rounded-pill">異常</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function getTooltipLabel(context) {
        let label = context.dataset.label || '';
        let value = context.parsed.y;
        let raw = context.dataset.rawCounts[context.dataIndex];
        let lines = [`${label}: ${value}%`];
        if (value < 100 && raw.missing && raw.missing.length > 0) {
            lines.push(''); lines.push('⚠️ 未登錄明細:');
            raw.missing.forEach(item => { lines.push(`• ${item}`); });
        } else if (value < 100 && raw.on > 0) {
            lines[0] += ` (${raw.logged}/${raw.on})`;
        }
        return lines;
    }

    const commonOptions = { 
        responsive: true, 
        scales: { y: { beginAtZero: true, max: 100, suggestedMax: 100 }, x: { grid: { display: false } } }, 
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: getTooltipLabel } } } 
    };

    new Chart(document.getElementById('chartMonth'), { 
        type: 'bar', 
        data: { 
            labels: <?= json_encode($trend_monthly['labels']) ?>, 
            datasets: [{ label: '確實率', data: <?= json_encode($trend_monthly['rates'], JSON_NUMERIC_CHECK) ?>, rawCounts: <?= json_encode($trend_monthly['raw']) ?>, backgroundColor: 'rgba(59, 130, 246, 0.8)', borderRadius: 4 }] 
        }, options: commonOptions 
    });

    new Chart(document.getElementById('chartWeek'), { 
        type: 'bar', 
        data: { 
            labels: <?= json_encode($trend_weekly['labels']) ?>, 
            datasets: [{ label: '確實率', data: <?= json_encode($trend_weekly['rates'], JSON_NUMERIC_CHECK) ?>, rawCounts: <?= json_encode($trend_weekly['raw']) ?>, backgroundColor: 'rgba(16, 185, 129, 0.8)', borderRadius: 4 }] 
        }, options: commonOptions 
    });

    new Chart(document.getElementById('chartDay'), { 
        type: 'bar', 
        data: { 
            labels: <?= json_encode($trend_daily['labels']) ?>, 
            datasets: [{ label: '確實率', data: <?= json_encode($trend_daily['rates'], JSON_NUMERIC_CHECK) ?>, rawCounts: <?= json_encode($trend_daily['raw']) ?>, backgroundColor: 'rgba(245, 158, 11, 0.8)', borderRadius: 4 }] 
        }, options: commonOptions 
    });
</script>
<?php $content = ob_get_clean(); require 'layout.php'; ?>