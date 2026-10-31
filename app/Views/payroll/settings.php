<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar">
    <a href="<?= site_url('payroll') ?>" class="btn btn-default btn-sm pull-right"><?= lang('Common.back') ?></a>
    <h3><?= lang('Payroll.settings') ?></h3>
</div>

<?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif; ?>
<?php if (session('error')): ?><div class="alert alert-danger"><?= esc(session('error')) ?></div><?php endif; ?>

<?= form_open('payroll/settings') ?>
<div class="panel panel-default">
    <div class="panel-heading"><strong><?= lang('Payroll.tanzania_rules') ?></strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-4 form-group">
                <label><?= lang('Payroll.jurisdiction') ?></label>
                <select class="form-control" name="jurisdiction">
                    <option value="MAINLAND" selected>Tanzania Mainland</option>
                </select>
            </div>
            <div class="col-sm-4 form-group"><label><?= lang('Payroll.employee_count') ?></label><input class="form-control" type="number" min="0" name="employee_count" value="<?= esc($settings->employee_count ?? 1) ?>" required></div>
            <div class="col-sm-4 form-group"><label><?= lang('Payroll.sdl_employee_limit') ?></label><input class="form-control" type="number" min="0" name="sdl_employee_limit" value="<?= esc($settings->sdl_employee_limit ?? 10) ?>" required></div>
            <div class="col-sm-4 form-group"><label><?= lang('Payroll.nssf_employee_rate') ?> (%)</label><input class="form-control" type="number" min="0" max="10" step="0.001" name="nssf_employee_rate" value="<?= esc($settings->nssf_employee_rate ?? 10) ?>" required></div>
            <div class="col-sm-4 form-group"><label><?= lang('Payroll.nssf_employer_rate') ?> (%)</label><input class="form-control" type="number" min="0" max="20" step="0.001" name="nssf_employer_rate" value="<?= esc($settings->nssf_employer_rate ?? 10) ?>" required></div>
            <div class="col-sm-4 form-group"><label><?= lang('Payroll.sdl_rate') ?> (%)</label><input class="form-control" type="number" min="0" max="100" step="0.001" name="sdl_rate" value="<?= esc($settings->sdl_rate ?? 3.5) ?>" required></div>
            <div class="col-sm-4 form-group"><label><?= lang('Payroll.wcf_rate') ?> (%)</label><input class="form-control" type="number" min="0" max="100" step="0.001" name="wcf_rate" value="<?= esc($settings->wcf_rate ?? 0) ?>" required></div>
        </div>
        <p class="text-muted">Employer contribution rates and thresholds are editable. WCF starts at 0 until configured for your registration and sector.</p>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><strong><?= lang('Payroll.paye_bands') ?> (TZS monthly taxable income)</strong></div>
    <div class="panel-body table-responsive">
        <table class="table table-striped">
            <thead><tr><th><?= lang('Payroll.income_from') ?></th><th><?= lang('Payroll.income_to') ?></th><th><?= lang('Payroll.base_tax') ?></th><th><?= lang('Payroll.rate') ?> (%)</th></tr></thead>
            <tbody>
            <?php foreach ($tax_bands as $band): ?>
                <tr>
                    <td><input type="hidden" name="payroll_tax_band_id[]" value="<?= esc($band->payroll_tax_band_id) ?>"><input class="form-control" type="number" min="0" step="0.01" name="income_from[]" value="<?= esc($band->income_from) ?>" required></td>
                    <td><input class="form-control" type="number" min="0" step="0.01" name="income_to[]" value="<?= esc($band->income_to ?? '') ?>" placeholder="No upper limit"></td>
                    <td><input class="form-control" type="number" min="0" step="0.01" name="base_tax[]" value="<?= esc($band->base_tax) ?>" required></td>
                    <td><input class="form-control" type="number" min="0" max="100" step="0.001" name="rate[]" value="<?= esc($band->rate) ?>" required></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button class="btn btn-primary" type="submit"><?= lang('Common.submit') ?></button>
        <a href="<?= site_url('payroll') ?>" class="btn btn-default"><?= lang('Common.cancel') ?></a>
    </div>
</div>
<?= form_close() ?>

<div class="panel panel-default">
    <div class="panel-heading"><strong><?= lang('Payroll.component_catalog') ?></strong></div>
    <div class="panel-body table-responsive">
        <table class="table table-striped"><thead><tr><th><?= lang('Payroll.component_code') ?></th><th><?= lang('Payroll.component') ?></th><th><?= lang('Payroll.component_type') ?></th><th><?= lang('Payroll.calculation_method') ?></th><th><?= lang('Payroll.taxable') ?></th><th><?= lang('Payroll.status') ?></th></tr></thead>
            <tbody><?php foreach ($components as $component): ?><tr><td><?= esc($component->component_code) ?></td><td><?= esc($component->component_name) ?></td><td><?= esc(ucfirst($component->component_type)) ?></td><td><?= esc(ucfirst($component->calculation_method)) ?></td><td><?= $component->taxable ? lang('Common.yes') : lang('Common.no') ?></td><td><?= $component->active ? lang('Payroll.active') : lang('Payroll.inactive') ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
    <div class="panel-body">
        <?= form_open('payroll/components', ['class' => 'form-horizontal']) ?>
        <div class="row">
            <div class="col-sm-2 form-group"><label><?= lang('Payroll.component_code') ?></label><input name="component_code" class="form-control" maxlength="40" required></div>
            <div class="col-sm-2 form-group"><label><?= lang('Payroll.component') ?></label><input name="component_name" class="form-control" maxlength="100" required></div>
            <div class="col-sm-2 form-group"><label><?= lang('Payroll.component_type') ?></label><select name="component_type" class="form-control"><option value="earning"><?= lang('Payroll.earnings') ?></option><option value="deduction"><?= lang('Payroll.deductions') ?></option></select></div>
            <div class="col-sm-2 form-group"><label><?= lang('Payroll.calculation_method') ?></label><select name="calculation_method" class="form-control"><option value="fixed"><?= lang('Payroll.fixed_amount') ?></option><option value="percentage"><?= lang('Payroll.percentage') ?></option></select></div>
            <div class="col-sm-2 form-group"><label><?= lang('Payroll.percentage_base') ?></label><select name="percentage_base" class="form-control"><option value="gross_pay"><?= lang('Payroll.gross_pay') ?></option><option value="basic_salary"><?= lang('Payroll.basic_salary') ?></option></select></div>
            <div class="col-sm-2 form-group"><label><?= lang('Payroll.default_amount') ?></label><input name="default_amount" class="form-control" type="number" min="0" step="0.01" value="0"></div>
        </div>
        <label><input type="checkbox" name="taxable" value="1" checked> <?= lang('Payroll.taxable') ?></label>
        <div class="text-right"><button type="submit" class="btn btn-primary"><?= lang('Payroll.add_component') ?></button></div>
        <?= form_close() ?>
    </div>
</div>

<?= view('partial/footer') ?>
