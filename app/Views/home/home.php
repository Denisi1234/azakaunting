<?php
/**
 * @var array $config
 * @var array $dashboard
 */
$totalSales            = (float) ($dashboard['total_sales'] ?? 0.0);
$salesGrowth           = (float) ($dashboard['sales_growth'] ?? 0.0);
$totalExpenses         = (float) ($dashboard['total_expenses'] ?? 0.0);
$expensesGrowth        = (float) ($dashboard['expenses_growth'] ?? 0.0);
$paymentSent           = (float) ($dashboard['payment_sent'] ?? 0.0);
$paymentSentGrowth     = (float) ($dashboard['payment_sent_growth'] ?? 0.0);
$paymentReceived       = (float) ($dashboard['payment_received'] ?? 0.0);
$paymentReceivedGrowth = (float) ($dashboard['payment_received_growth'] ?? 0.0);

$dailySeries   = $dashboard['daily_series'] ?? [];
$monthlySeries = $dashboard['monthly_series'] ?? [];

$kpiCards = [
    ['label' => 'Total Sales', 'value' => $totalSales, 'growth' => $salesGrowth, 'icon' => 'glyphicon-usd', 'tone' => 'violet'],
    ['label' => 'Total Expense', 'value' => $totalExpenses, 'growth' => $expensesGrowth, 'icon' => 'glyphicon-list-alt', 'tone' => 'indigo'],
    ['label' => 'Payment Sent', 'value' => $paymentSent, 'growth' => $paymentSentGrowth, 'icon' => 'glyphicon-send', 'tone' => 'teal'],
    ['label' => 'Payment Received', 'value' => $paymentReceived, 'growth' => $paymentReceivedGrowth, 'icon' => 'glyphicon-inbox', 'tone' => 'lime'],
];
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<div class="ospos-pos-dashboard">
    <h2 class="dash-page-title">Dashboard Overview</h2>

    <div class="dash-kpi-grid">
        <?php foreach ($kpiCards as $card): ?>
            <?php
            $growth      = (float) $card['growth'];
            $isUp        = $growth >= 0;
            $growthClass = $isUp ? 'dash-trend-up' : 'dash-trend-down';
            $growthIcon  = $isUp ? 'glyphicon-arrow-up' : 'glyphicon-arrow-down';
            ?>
            <div class="dash-kpi-card">
                <div class="dash-kpi-icon dash-tone-<?= esc($card['tone']) ?>">
                    <span class="glyphicon <?= esc($card['icon']) ?>"></span>
                </div>
                <div class="dash-kpi-label"><?= esc($card['label']) ?></div>
                <div class="dash-kpi-value"><?= esc(to_currency($card['value'])) ?></div>
                <div class="dash-kpi-foot <?= $growthClass ?>">
                    <span class="glyphicon <?= $growthIcon ?>"></span>
                    <strong><?= esc(number_format(abs($growth), 1)) ?>%</strong>
                    <span class="dash-kpi-foot-text">Than Last Month</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dash-card dash-chart-card">
        <div class="dash-card-head">
            <h4 class="dash-card-title">Sales &amp; Purchases</h4>
            <select class="dash-select" id="dash-chart-range">
                <option value="monthly" selected>6 Months</option>
                <option value="daily">7 Days</option>
            </select>
        </div>
        <div id="dash-sales-chart" class="dash-sales-chart"></div>
        <div class="dash-chart-legend">
            <span><i class="dash-dot" style="background:#dedbf1"></i> Sales Target</span>
            <span><i class="dash-dot" style="background:#f87171"></i> Sales</span>
            <span><i class="dash-dot" style="background:#a78bfa"></i> Purchases</span>
        </div>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyData = <?= json_encode($monthlySeries) ?>;
        const dailyData = <?= json_encode($dailySeries) ?>;

        const renderSalesChart = function (seriesList) {
            const labels = seriesList.map(function (item) { return item.label; });
            const targetSeries = seriesList.map(function (item) { return Number(item.target) || 0; });
            const salesSeries = seriesList.map(function (item) { return Number(item.sales) || 0; });
            const purchasesSeries = seriesList.map(function (item) { return Number(item.purchases) || 0; });

            if (typeof Chartist === 'undefined') {
                return;
            }

            new Chartist.Bar('#dash-sales-chart', {
                labels: labels,
                series: [targetSeries, salesSeries, purchasesSeries]
            }, {
                seriesBarDistance: 16,
                low: 0,
                axisY: {
                    offset: 42,
                    scaleMinSpace: 26,
                    labelInterpolationFnc: function (value) {
                        if (value >= 1000) {
                            const shortValue = value / 1000;
                            return (shortValue % 1 === 0 ? shortValue.toFixed(0) : shortValue.toFixed(1)) + 'k';
                        }
                        return value;
                    }
                },
                chartPadding: {
                    top: 10,
                    right: 10,
                    bottom: 0,
                    left: 0
                }
            });
        };

        renderSalesChart(monthlyData);

        const rangeSelect = document.getElementById('dash-chart-range');

        if (rangeSelect) {
            rangeSelect.addEventListener('change', function () {
                renderSalesChart(this.value === 'daily' ? dailyData : monthlyData);
            });
        }
    });
</script>

<?= view('partial/footer') ?>
