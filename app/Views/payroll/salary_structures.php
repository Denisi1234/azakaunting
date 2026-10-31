<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar">
    <a href="<?= site_url('payroll') ?>" class="btn btn-default btn-sm pull-right">
        <span class="glyphicon glyphicon-arrow-left">&nbsp;</span><?= lang('Payroll.payroll') ?>
    </a>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><span class="glyphicon glyphicon-user">&nbsp;</span><?= lang('Payroll.salary_structures') ?></h3>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-hover table-bordered">
            <thead>
                <tr>
                    <th><?= lang('Payroll.employee') ?></th>
                    <th><?= lang('Payroll.pay_type') ?></th>
                    <th><?= lang('Payroll.basic_salary') ?></th>
                    <th><?= lang('Payroll.hourly_rate') ?></th>
                    <th><?= lang('Payroll.allowances') ?></th>
                    <th><?= lang('Payroll.deductions') ?></th>
                    <th><?= lang('Payroll.notes') ?></th>
                    <th><?= lang('Common.action') ?? 'Action' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($structures as $s): ?>
                    <tr>
                        <td><strong><?= esc($s['name']) ?></strong> (<?= esc($s['username']) ?>)</td>
                        <td><span class="label <?= $s['pay_type'] === 'hourly' ? 'label-info' : 'label-primary' ?>"><?= ucfirst(esc($s['pay_type'])) ?></span></td>
                        <td><?= to_currency($s['basic_salary']) ?></td>
                        <td><?= to_currency($s['hourly_rate']) ?></td>
                        <td><?= to_currency($s['allowances']) ?></td>
                        <td><?= to_currency($s['deductions']) ?></td>
                        <td><?= esc($s['notes']) ?></td>
                        <td>
                            <a href="<?= site_url("salary_structures/view/{$s['employee_id']}") ?>" class="btn btn-primary btn-xs">
                                <span class="glyphicon glyphicon-edit"></span> <?= lang('Common.edit') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= view('partial/footer') ?>
