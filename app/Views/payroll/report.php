<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar">
    <a href="<?= site_url('payroll') ?>" class="btn btn-default btn-sm pull-right">
        <span class="glyphicon glyphicon-arrow-left">&nbsp;</span><?= lang('Payroll.payroll') ?>
    </a>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><span class="glyphicon glyphicon-stats">&nbsp;</span><?= lang('Payroll.report') ?></h3>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= lang('Payroll.period') ?></th>
                    <th><?= lang('Payroll.status') ?></th>
                    <th><?= lang('Payroll.total_employees') ?></th>
                    <th><?= lang('Payroll.total_net_pay') ?></th>
                    <th class="print_hide"><?= lang('Common.action') ?? 'Action' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($summary as $s): ?>
                    <tr>
                        <td><?= esc($s['run']->payroll_run_id) ?></td>
                        <td><?= esc($s['run']->period_start) ?> &rarr; <?= esc($s['run']->period_end) ?></td>
                        <td>
                            <?php if ($s['run']->status === 'paid'): ?>
                                <span class="label label-success"><?= strtoupper(lang('Payroll.paid')) ?></span>
                            <?php elseif ($s['run']->status === 'approved'): ?>
                                <span class="label label-info"><?= strtoupper(lang('Payroll.approved')) ?></span>
                            <?php else: ?>
                                <span class="label label-warning"><?= strtoupper(lang('Payroll.draft')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($s['count']) ?></td>
                        <td><strong><?= esc($s['total_net']) ?></strong></td>
                        <td class="print_hide">
                            <a href="<?= site_url("payroll/items/{$s['run']->payroll_run_id}") ?>" class="btn btn-default btn-xs">
                                <span class="glyphicon glyphicon-list"></span> <?= lang('Payroll.view_items') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= view('partial/footer') ?>
