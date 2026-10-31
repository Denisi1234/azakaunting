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

                    <hr>
                    <h4><?= lang('Payroll.recurring_components') ?></h4>
                    <div class="table-responsive">
                        <table class="table table-condensed">
                            <thead><tr><th><?= lang('Payroll.component') ?></th><th><?= lang('Payroll.amount') ?></th><th><?= lang('Payroll.rate') ?> (%)</th><th><?= lang('Payroll.effective_from') ?></th><th><?= lang('Payroll.effective_to') ?></th><th><?= lang('Common.delete') ?></th></tr></thead>
                            <tbody>
                            <?php foreach ($assigned_components as $assignment): ?>
                                <tr>
                                    <td>
                                        <input type="hidden" name="assignment_id[]" value="<?= esc($assignment->employee_payroll_component_id) ?>">
                                        <select name="component_id[]" class="form-control input-sm">
                                            <?php foreach ($payroll_components as $component): ?>
                                                <option value="<?= esc($component->payroll_component_id) ?>" <?= (int) $component->payroll_component_id === (int) $assignment->payroll_component_id ? 'selected' : '' ?>><?= esc($component->component_name) ?> (<?= esc(ucfirst($component->component_type)) ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input name="component_amount[]" class="form-control input-sm" type="number" min="0" step="0.01" value="<?= esc($assignment->amount) ?>"></td>
                                    <td><input name="component_rate[]" class="form-control input-sm" type="number" min="0" step="0.001" value="<?= esc($assignment->rate) ?>"></td>
                                    <td><input name="effective_from[]" class="form-control input-sm" type="date" value="<?= esc($assignment->effective_from) ?>"></td>
                                    <td><input name="effective_to[]" class="form-control input-sm" type="date" value="<?= esc($assignment->effective_to ?? '') ?>"></td>
                                    <td><label><input type="checkbox" name="remove_assignment[]" value="<?= esc($assignment->employee_payroll_component_id) ?>"></label></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php for ($row = 0; $row < 3; $row++): ?>
                                <tr>
                                    <td><input type="hidden" name="assignment_id[]" value=""><select name="component_id[]" class="form-control input-sm"><option value="">—</option><?php foreach ($payroll_components as $component): ?><option value="<?= esc($component->payroll_component_id) ?>"><?= esc($component->component_name) ?> (<?= esc(ucfirst($component->component_type)) ?>)</option><?php endforeach; ?></select></td>
                                    <td><input name="component_amount[]" class="form-control input-sm" type="number" min="0" step="0.01" value="0"></td>
                                    <td><input name="component_rate[]" class="form-control input-sm" type="number" min="0" step="0.001" value="0"></td>
                                    <td><input name="effective_from[]" class="form-control input-sm" type="date" value="<?= date('Y-m-d') ?>"></td>
                                    <td><input name="effective_to[]" class="form-control input-sm" type="date"></td>
                                    <td></td>
                                </tr>
                            <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-3"><?= lang('Payroll.notes') ?></label>
                        <div class="col-xs-8">
                            <textarea name="notes" class="form-control input-sm" rows="3"><?= esc($structure->notes ?? '') ?></textarea>
                        </div>
                    </div>

                    <hr>
                    <h4><?= lang('Payroll.statutory_profile') ?></h4>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.tax_status') ?></label><div class="col-xs-8"><input type="text" class="form-control input-sm" value="<?= lang('Payroll.resident') ?>" readonly><input type="hidden" name="tax_status" value="resident"></div></div>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.fund_code') ?></label><div class="col-xs-8"><input name="fund_code" class="form-control input-sm" value="<?= esc($profile->fund_code ?? 'NSSF') ?>"></div></div>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.tax_identifier') ?></label><div class="col-xs-8"><input name="tax_identifier" class="form-control input-sm" value="<?= esc($profile->tax_identifier ?? '') ?>"></div></div>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.social_security_number') ?></label><div class="col-xs-8"><input name="social_security_number" class="form-control input-sm" value="<?= esc($profile->social_security_number ?? '') ?>"></div></div>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.employee_bank_name') ?></label><div class="col-xs-8"><input name="employee_bank_name" class="form-control input-sm" value="<?= esc($profile->employee_bank_name ?? '') ?>"></div></div>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.employee_bank_branch') ?></label><div class="col-xs-8"><input name="employee_bank_branch" class="form-control input-sm" value="<?= esc($profile->employee_bank_branch ?? '') ?>"></div></div>
                    <div class="form-group"><label class="control-label col-xs-3"><?= lang('Payroll.employee_bank_account') ?></label><div class="col-xs-8"><input name="employee_bank_account" class="form-control input-sm" value="<?= esc($profile->employee_bank_account ?? '') ?>"></div></div>
                    <div class="form-group"><div class="col-xs-offset-3 col-xs-8"><label><input type="checkbox" name="primary_employment" value="1" <?= ! isset($profile->primary_employment) || $profile->primary_employment ? 'checked' : '' ?>> <?= lang('Payroll.primary_employment') ?></label></div></div>

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
