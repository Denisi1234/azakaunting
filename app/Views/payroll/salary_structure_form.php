<?= view('partial/header') ?>

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <span class="glyphicon glyphicon-usd">&nbsp;</span>
                    <?= lang('Payroll.salary_structures') ?>: <?= esc($employee_name) ?>
                </h3>
            </div>
            <div class="panel-body">
                <?= form_open("salary_structures/save/{$employee_id}", ['class' => 'form-horizontal']) ?>
                    <div class="form-group">
                        <label class="control-label col-xs-3 required"><?= lang('Payroll.pay_type') ?></label>
                        <div class="col-xs-8">
                            <select name="pay_type" class="form-control input-sm" id="pay_type_select">
                                <option value="monthly" <?= ($structure->pay_type ?? 'monthly') === 'monthly' ? 'selected' : '' ?>><?= lang('Payroll.monthly') ?></option>
                                <option value="hourly" <?= ($structure->pay_type ?? 'monthly') === 'hourly' ? 'selected' : '' ?>><?= lang('Payroll.hourly') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group" id="group_basic_salary">
                        <label class="control-label col-xs-3"><?= lang('Payroll.basic_salary') ?></label>
                        <div class="col-xs-8">
                            <input type="text" name="basic_salary" class="form-control input-sm" value="<?= to_currency_no_money($structure->basic_salary ?? 0) ?>">
                        </div>
                    </div>

                    <div class="form-group" id="group_hourly_rate">
                        <label class="control-label col-xs-3"><?= lang('Payroll.hourly_rate') ?></label>
                        <div class="col-xs-8">
                            <input type="text" name="hourly_rate" class="form-control input-sm" value="<?= to_currency_no_money($structure->hourly_rate ?? 0) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-3"><?= lang('Payroll.allowances') ?></label>
                        <div class="col-xs-8">
                            <input type="text" name="allowances" class="form-control input-sm" value="<?= to_currency_no_money($structure->allowances ?? 0) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-3"><?= lang('Payroll.deductions') ?></label>
                        <div class="col-xs-8">
                            <input type="text" name="deductions" class="form-control input-sm" value="<?= to_currency_no_money($structure->deductions ?? 0) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-3"><?= lang('Payroll.notes') ?></label>
                        <div class="col-xs-8">
                            <textarea name="notes" class="form-control input-sm" rows="3"><?= esc($structure->notes ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="text-right">
                        <a href="<?= site_url('salary_structures') ?>" class="btn btn-default btn-sm"><?= lang('Common.cancel') ?></a>
                        <button type="submit" class="btn btn-primary btn-sm"><?= lang('Common.submit') ?></button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
