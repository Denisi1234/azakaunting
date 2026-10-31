<?php
/**
 * @var string $business_date
 * @var array $locations
 * @var array $rows
 * @var string|null $success
 * @var string|null $error
 */
?>
<?= view('partial/header') ?>

<div class="container-fluid">
    <div class="page-header"><h3><?= lang('Reports.payment_reconciliation') ?></h3></div>

    <?php if ($success !== null) : ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>
    <?php if ($error !== null) : ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>

    <?= form_open('payment_reconciliation', ['method' => 'get', 'class' => 'form-inline panel panel-default']) ?>
        <div class="panel-body">
            <div class="form-group">
                <?= form_label(lang('Reports.date'), 'business_date', ['class' => 'control-label']) ?>
                <?= form_input(['name' => 'business_date', 'id' => 'business_date', 'class' => 'form-control', 'type' => 'date', 'value' => $business_date]) ?>
            </div>
            <button class="btn btn-primary" type="submit"><?= lang('Common.submit') ?></button>
        </div>
    <?= form_close() ?>

    <?php if ($rows === []) : ?>
        <div class="alert alert-info"><?= lang('Reports.reconciliation_no_payments') ?></div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead><tr>
                    <th><?= lang('Reports.stock_location') ?></th>
                    <th><?= lang('Reports.payment_type') ?></th>
                    <th><?= lang('Reports.expected_amount') ?></th>
                    <th><?= lang('Reports.collected_amount') ?></th>
                    <th><?= lang('Reports.variance') ?></th>
                    <th><?= lang('Reports.reconciliation_note') ?></th>
                    <th><?= lang('Reports.reconciled_at') ?></th>
                    <th><?= lang('Reports.reconciled_by') ?></th>
                    <th></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?= esc($locations[$row['location_id']] ?? '') ?></td>
                            <td><?= esc($row['payment_type']) ?></td>
                            <td><?= esc(to_currency($row['expected_amount'])) ?></td>
                            <?php $formId = 'reconcile_' . $row['location_id'] . '_' . substr(md5($row['payment_type']), 0, 8); ?>
                            <td>
                                <?= form_open('payment_reconciliation/save', ['id' => $formId]) ?>
                                    <?= form_hidden('business_date', $business_date) ?>
                                    <?= form_hidden('location_id', (string) $row['location_id']) ?>
                                    <?= form_hidden('payment_type', $row['payment_type']) ?>
                                    <?= form_input(['name' => 'collected_amount', 'class' => 'form-control input-sm', 'type' => 'number', 'min' => '0', 'step' => '0.01', 'value' => $row['collected_amount'] === null ? '' : number_format($row['collected_amount'], 2, '.', ''), 'required' => true]) ?>
                                <?= form_close() ?>
                            </td>
                            <td><?= $row['variance'] === null ? '' : esc(to_currency($row['variance'])) ?></td>
                            <td><?= form_input(['name' => 'note', 'form' => $formId, 'class' => 'form-control input-sm', 'maxlength' => 255, 'placeholder' => lang('Reports.reconciliation_note'), 'value' => $row['note']]) ?></td>
                            <td><?= esc($row['reconciled_at'] ?? '') ?></td>
                            <td><?= esc($row['reconciled_by'] ?? '') ?></td>
                            <td><button class="btn btn-default btn-sm" type="submit" form="<?= esc($formId) ?>"><?= lang('Reports.save_reconciliation') ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-muted"><?= lang('Reports.reconciliation_help') ?></p>
    <?php endif; ?>

    <a class="btn btn-default" href="<?= site_url('reports') ?>"><?= lang('Reports.reports') ?></a>
</div>

<?= view('partial/footer') ?>
