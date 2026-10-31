<?php
/**
 * @var int   $person_id
 * @var array $permission_ids
 * @var array $grants
 */

$detailed_reports = [
    'reports_sales'      => 'detailed',
    'reports_receivings' => 'detailed',
    'reports_customers'  => 'specific',
    'reports_discounts'  => 'specific',
    'reports_employees'  => 'specific',
    'reports_suppliers'  => 'specific',
];
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<?php
if (isset($error)) {
    echo '<div class="alert alert-dismissible alert-danger">' . esc($error) . '</div>';
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-stats">&nbsp;</span><?= lang('Reports.graphical_reports') ?></h3>
            </div>
            <div class="list-group">
                <?php foreach ($permission_ids as $permission_id) {
                    if (can_show_report($permission_id, ['inventory', 'receiving'])) {
                        $link = get_report_link($permission_id, 'graphical_summary');
                ?>
                        <a class="list-group-item" href="<?= $link['path'] ?>"><?= esc($link['label']) ?></a>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-list">&nbsp;</span><?= lang('Reports.summary_reports') ?></h3>
            </div>
                <div class="list-group" style="margin-bottom: 0;">
                    <?= anchor('accounting/profitLoss', '<span class="glyphicon glyphicon-stats">&nbsp;</span>Financial Statement (P&L)', ['class' => 'list-group-item list-group-item-info']) ?>
                    <?= anchor('accounting/balanceSheet', '<span class="glyphicon glyphicon-book">&nbsp;</span>Balance Sheet (Financial Position)', ['class' => 'list-group-item list-group-item-info']) ?>
                    <?= anchor('accounting/bankReconciliation', '<span class="glyphicon glyphicon-credit-card">&nbsp;</span>Bank & Digital Reconciliation', ['class' => 'list-group-item list-group-item-info']) ?>
                    <?php if (in_array('reports_payments', $permission_ids, true)) : ?>
                        <?= anchor('payment_reconciliation', '<span class="glyphicon glyphicon-transfer">&nbsp;</span>' . lang('Reports.payment_reconciliation'), ['class' => 'list-group-item list-group-item-info']) ?>
                    <?php endif; ?>
                </div>
            <div class="list-group">
                <?php if (in_array('reports_sales', $permission_ids, true)) {
                    $location_profit_link = get_report_link('reports_locations', 'summary');
                ?>
                    <a class="list-group-item" href="<?= $location_profit_link['path'] ?>"><?= esc(lang('Reports.stock_location') . ' ' . lang('Reports.profit')) ?></a>
                <?php } ?>
                <?php foreach ($permission_ids as $permission_id) {
                    if (can_show_report($permission_id, ['inventory', 'receiving'])) {
                        $link = get_report_link($permission_id, 'summary');
                ?>
                        <a class="list-group-item" href="<?= $link['path'] ?>"><?= esc($link['label']) ?></a>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-list-alt">&nbsp;</span><?= lang('Reports.detailed_reports') ?></h3>
            </div>
            <div class="list-group">
                <?php foreach ($detailed_reports as $report_name => $prefix) {
                    if (in_array($report_name, $permission_ids, true)) {
                        $link = get_report_link($report_name, $prefix);
                ?>
                        <a class="list-group-item" href="<?= $link['path'] ?>"><?= esc($link['label']) ?></a>
                        <?php if ($report_name === 'reports_sales') {
                            $item_sales_link = get_report_link('reports_item_sales', 'detailed', 'Reports.detailed_item_sales_report');
                        ?>
                            <a class="list-group-item" href="<?= $item_sales_link['path'] ?>"><?= esc($item_sales_link['label']) ?></a>
                        <?php } ?>
                <?php
                    }
                }
                ?>
            </div>
        </div>

        <?php if (in_array('reports_inventory', $permission_ids, true)) { ?>
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><span class="glyphicon glyphicon-book">&nbsp;</span><?= lang('Reports.inventory_reports') ?></h3>
                </div>
                <div class="list-group">
                    <?php
                    $inventory_low_report = get_report_link('reports_inventory_low');
                    $inventory_summary_report = get_report_link('reports_inventory_summary');
                    ?>
                    <a class="list-group-item" href="<?= $inventory_low_report['path'] ?>"><?= esc($inventory_low_report['label']) ?></a>
                    <a class="list-group-item" href="<?= $inventory_summary_report['path'] ?>"><?= esc($inventory_summary_report['label']) ?></a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<?= view('partial/footer') ?>
