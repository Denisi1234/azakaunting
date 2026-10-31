<?php
/**
 * @var array $locations
 * @var int $location_id
 * @var object|null $shift
 * @var array $movements
 * @var float $expected_cash
 * @var array $history
 * @var bool $can_review_all
 * @var string|null $error
 * @var string|null $success
 */
?>
<?= view('partial/header') ?>

<div class="container-fluid">
    <div class="page-header">
        <h3><?= lang('Sales.shift_title') ?></h3>
    </div>

    <?php if ($error !== null) : ?>
        <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php if ($success !== null) : ?>
        <div class="alert alert-success"><?= esc($success) ?></div>
    <?php endif; ?>

    <?php if ($shift === null) : ?>
        <div class="panel panel-default">
            <div class="panel-heading"><strong><?= lang('Sales.shift_start') ?></strong></div>
            <div class="panel-body">
                <?= form_open('register_shifts/open', ['class' => 'form-horizontal']) ?>
                    <div class="form-group">
                        <?= form_label(lang('Sales.stock_location'), 'location_id', ['class' => 'control-label col-sm-3']) ?>
                        <div class="col-sm-6"><?= form_dropdown('location_id', $locations, $location_id, ['id' => 'location_id', 'class' => 'form-control', 'required' => true]) ?></div>
                    </div>
                    <div class="form-group">
                        <?= form_label(lang('Sales.shift_opening_cash'), 'opening_cash', ['class' => 'control-label col-sm-3']) ?>
                        <div class="col-sm-6"><?= form_input(['name' => 'opening_cash', 'id' => 'opening_cash', 'class' => 'form-control', 'type' => 'number', 'min' => '0', 'step' => '0.01', 'required' => true]) ?></div>
                    </div>
                    <div class="form-group">
                        <?= form_label(lang('Sales.shift_note'), 'opening_note', ['class' => 'control-label col-sm-3']) ?>
                        <div class="col-sm-6"><?= form_input(['name' => 'opening_note', 'id' => 'opening_note', 'class' => 'form-control', 'maxlength' => 255]) ?></div>
                    </div>
                    <div class="form-group"><div class="col-sm-offset-3 col-sm-6"><button class="btn btn-primary" type="submit"><?= lang('Sales.shift_start') ?></button></div></div>
                <?= form_close() ?>
            </div>
        </div>
    <?php else : ?>
        <div class="panel panel-primary">
            <div class="panel-heading"><strong><?= lang('Sales.shift_active') ?></strong></div>
            <div class="panel-body">
                <p><?= esc($shift->location_name) ?> · <?= esc($shift->first_name . ' ' . $shift->last_name) ?> · <?= esc($shift->opened_at) ?></p>
                <p><?= lang('Sales.shift_opening_cash') ?>: <strong><?= esc(to_currency($shift->opening_cash)) ?></strong></p>
                <p><?= lang('Sales.shift_expected_cash') ?>: <strong><?= esc(to_currency($expected_cash)) ?></strong></p>

                <div class="row">
                    <div class="col-md-6">
                        <h4><?= lang('Sales.shift_cash_movement') ?></h4>
                        <?= form_open('register_shifts/movement', ['class' => 'form-horizontal']) ?>
                            <div class="form-group">
                                <div class="col-sm-4"><?= form_dropdown('movement_type', ['in' => lang('Sales.shift_cash_in'), 'out' => lang('Sales.shift_cash_out')], 'out', ['class' => 'form-control']) ?></div>
                                <div class="col-sm-4"><?= form_input(['name' => 'amount', 'class' => 'form-control', 'type' => 'number', 'min' => '0.01', 'step' => '0.01', 'placeholder' => lang('Sales.shift_amount'), 'required' => true]) ?></div>
                                <div class="col-sm-4"><?= form_input(['name' => 'reason', 'class' => 'form-control', 'maxlength' => 255, 'placeholder' => lang('Sales.shift_reason'), 'required' => true]) ?></div>
                            </div>
                            <button class="btn btn-default" type="submit"><?= lang('Common.submit') ?></button>
                        <?= form_close() ?>
                    </div>
                    <div class="col-md-6">
                        <h4><?= lang('Sales.shift_close') ?></h4>
                        <?= form_open('register_shifts/close', ['class' => 'form-horizontal']) ?>
                            <div class="form-group">
                                <?= form_label(lang('Sales.shift_counted_cash'), 'closing_cash', ['class' => 'control-label col-sm-4']) ?>
                                <div class="col-sm-8"><?= form_input(['name' => 'closing_cash', 'id' => 'closing_cash', 'class' => 'form-control', 'type' => 'number', 'min' => '0', 'step' => '0.01', 'required' => true]) ?></div>
                            </div>
                            <div class="form-group">
                                <?= form_label(lang('Sales.shift_note'), 'closing_note', ['class' => 'control-label col-sm-4']) ?>
                                <div class="col-sm-8"><?= form_input(['name' => 'closing_note', 'id' => 'closing_note', 'class' => 'form-control', 'maxlength' => 255]) ?></div>
                            </div>
                            <button class="btn btn-primary" type="submit"><?= lang('Sales.shift_close') ?></button>
                        <?= form_close() ?>
                    </div>
                </div>

                <?php if ($movements !== []) : ?>
                    <h4><?= lang('Sales.shift_movement_history') ?></h4>
                    <div class="table-responsive"><table class="table table-striped">
                        <thead><tr><th><?= lang('Sales.shift_time') ?></th><th><?= lang('Sales.shift_movement') ?></th><th><?= lang('Sales.shift_amount') ?></th><th><?= lang('Sales.shift_reason') ?></th><th><?= lang('Sales.shift_employee') ?></th></tr></thead>
                        <tbody><?php foreach ($movements as $movement) : ?>
                            <tr><td><?= esc($movement->created_at) ?></td><td><?= esc(lang($movement->movement_type === 'in' ? 'Sales.shift_cash_in' : 'Sales.shift_cash_out')) ?></td><td><?= esc(to_currency($movement->amount)) ?></td><td><?= esc($movement->reason) ?></td><td><?= esc($movement->first_name . ' ' . $movement->last_name) ?></td></tr>
                        <?php endforeach; ?></tbody>
                    </table></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($history !== []) : ?>
        <div class="panel panel-default">
            <div class="panel-heading"><strong><?= lang('Sales.shift_history') ?></strong></div>
            <div class="panel-body table-responsive"><table class="table table-striped">
                <thead><tr><th><?= lang('Sales.shift_time') ?></th><?php if ($can_review_all) : ?><th><?= lang('Sales.shift_employee') ?></th><?php endif; ?><th><?= lang('Sales.stock_location') ?></th><th><?= lang('Sales.shift_opening_cash') ?></th><th><?= lang('Sales.shift_expected_cash') ?></th><th><?= lang('Sales.shift_counted_cash') ?></th><th><?= lang('Sales.shift_variance') ?></th></tr></thead>
                <tbody><?php foreach ($history as $pastShift) : ?>
                    <tr><td><?= esc($pastShift->opened_at) ?></td><?php if ($can_review_all) : ?><td><?= esc($pastShift->first_name . ' ' . $pastShift->last_name) ?></td><?php endif; ?><td><?= esc($pastShift->location_name) ?></td><td><?= esc(to_currency($pastShift->opening_cash)) ?></td><td><?= esc(to_currency($pastShift->expected_cash)) ?></td><td><?= esc(to_currency($pastShift->closing_cash)) ?></td><td><?= esc(to_currency($pastShift->cash_variance)) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
        </div>
    <?php endif; ?>

    <a class="btn btn-default" href="<?= site_url('sales') ?>"><?= lang('Sales.back_to_register') ?></a>
</div>

<?= view('partial/footer') ?>
