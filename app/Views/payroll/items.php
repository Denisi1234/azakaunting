<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar">
    <a href="<?= site_url('payroll') ?>" class="btn btn-default btn-sm pull-right">
        <span class="glyphicon glyphicon-arrow-left">&nbsp;</span><?= lang('Payroll.payroll') ?>
    </a>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <span class="glyphicon glyphicon-list-alt">&nbsp;</span>
            <?= lang('Payroll.period') ?>: <?= esc($run->period_start) ?> &rarr; <?= esc($run->period_end) ?>
            <span class="pull-right">
                <?= lang('Payroll.status') ?>: <strong><?= strtoupper(esc($run->status)) ?></strong>
            </span>
        </h3>
    </div>
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered" style="border-collapse:separate; border-spacing:0;">
                <thead>
                    <tr style="background:#1a3a5c; color:#fff;">
                        <th style="background:#1a3a5c; color:#fff;"><?= lang('Payroll.employee') ?></th>
                        <th style="background:#1a3a5c; color:#fff;"><?= lang('Payroll.basic_salary') ?></th>
                        <th style="background:#1a3a5c; color:#fff;"><?= lang('Payroll.allowances') ?></th>
                        <th style="background:#1a3a5c; color:#fff;"><?= lang('Payroll.deductions') ?></th>
                        <th style="background:#1a3a5c; color:#fff;"><?= lang('Payroll.net_pay') ?></th>
                        <th style="background:#1a3a5c; color:#fff;"><?= lang('Payroll.status') ?></th>
                        <th class="print_hide" style="background:#1a3a5c; color:#fff;"><?= lang('Common.action') ?? 'Action' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sumBasic = 0; $sumAllowances = 0; $sumDeductions = 0; $sumNet = 0;
                    foreach ($items as $item): 
                        $sumBasic += (float) $item->basic_salary;
                        $sumAllowances += (float) $item->allowances;
                        $sumDeductions += (float) $item->deductions;
                        $sumNet += (float) $item->net_pay;
                    ?>
                        <tr>
                            <td><strong><?= esc($item->first_name . ' ' . $item->last_name) ?></strong> (<?= esc($item->username) ?>)</td>
                            <td><?= to_currency((float) $item->basic_salary) ?></td>
                            <td style="color:#27ae60;"><?= to_currency((float) $item->allowances) ?></td>
                            <td style="color:#c0392b;"><?= to_currency((float) $item->deductions) ?></td>
                            <td><strong style="color:#1a3a5c;"><?= to_currency((float) $item->net_pay) ?></strong></td>
                            <td>
                                <?php if ($item->status === 'paid'): ?>
                                    <span class="label label-success"><?= strtoupper(lang('Payroll.paid')) ?></span>
                                <?php elseif ($item->status === 'approved'): ?>
                                    <span class="label label-info"><?= strtoupper(lang('Payroll.approved')) ?></span>
                                <?php else: ?>
                                    <span class="label label-warning"><?= strtoupper(lang('Payroll.draft')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="print_hide">
                                <a href="<?= site_url("payroll/payslip/{$item->payroll_item_id}") ?>" class="btn btn-default btn-xs" target="_blank" title="<?= lang('Payroll.print_payslip') ?>">
                                    <span class="glyphicon glyphicon-print"></span> <?= lang('Payroll.payslip') ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background:#1a3a5c; color:#fff; font-weight:bold;">
                        <td style="color:#fff;"><?= lang('Reports.total') ?? 'Total' ?>:</td>
                        <td style="color:#fff;"><?= to_currency($sumBasic) ?></td>
                        <td style="color:#fff;"><?= to_currency($sumAllowances) ?></td>
                        <td style="color:#fff;"><?= to_currency($sumDeductions) ?></td>
                        <td style="color:#fff;"><?= to_currency($sumNet) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
