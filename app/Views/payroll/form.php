<?= view('partial/header') ?>

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-calendar">&nbsp;</span><?= lang('Payroll.new_run') ?></h3>
            </div>
            <div class="panel-body">
                <?= form_open('payroll/save/' . ($run_info->payroll_run_id ?? NEW_ENTRY), ['class' => 'form-horizontal']) ?>
                    <div class="form-group">
                        <label class="control-label col-xs-3 required"><?= lang('Payroll.period_start') ?></label>
                        <div class="col-xs-8">
                            <input type="date" name="period_start" class="form-control input-sm" required value="<?= esc($run_info->period_start ?? date('Y-m-01')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-3 required"><?= lang('Payroll.period_end') ?></label>
                        <div class="col-xs-8">
                            <input type="date" name="period_end" class="form-control input-sm" required value="<?= esc($run_info->period_end ?? date('Y-m-t')) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-xs-3"><?= lang('Payroll.notes') ?></label>
                        <div class="col-xs-8">
                            <textarea name="notes" class="form-control input-sm" rows="3"><?= esc($run_info->notes ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="text-right">
                        <a href="<?= site_url('payroll') ?>" class="btn btn-default btn-sm"><?= lang('Common.cancel') ?></a>
                        <button type="submit" class="btn btn-primary btn-sm"><?= lang('Common.submit') ?></button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
