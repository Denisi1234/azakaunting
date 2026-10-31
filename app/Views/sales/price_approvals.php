<?php
/**
 * @var array $requests
 * @var array $history
 * @var string|null $success
 * @var string|null $error
 */
?>
<?= view('partial/header') ?>

<div class="container-fluid">
    <div class="page-header"><h3><?= lang('Sales.price_approvals') ?></h3></div>

    <?php if ($success !== null) : ?><div class="alert alert-success"><?= esc($success) ?></div><?php endif; ?>
    <?php if ($error !== null) : ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>

    <?php if ($requests === []) : ?>
        <div class="alert alert-info"><?= lang('Sales.price_approval_none') ?></div>
    <?php else : ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead><tr>
                    <th><?= lang('Sales.price_approval_requested_at') ?></th>
                    <th><?= lang('Sales.price_approval_requester') ?></th>
                    <th><?= lang('Sales.stock_location') ?></th>
                    <th><?= lang('Sales.item_name') ?></th>
                    <th><?= lang('Sales.price_approval_change') ?></th>
                    <th><?= lang('Sales.price_change_reason') ?></th>
                    <th><?= lang('Sales.price_approval_decision_reason') ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($requests as $request) : ?>
                        <tr>
                            <td><?= esc($request->created_at) ?></td>
                            <td><?= esc($request->requester_first_name . ' ' . $request->requester_last_name) ?></td>
                            <td><?= esc($request->location_name) ?></td>
                            <td><?= esc($request->item_name) ?></td>
                            <td>
                                <?= esc(to_currency($request->old_price)) ?> → <?= esc(to_currency($request->requested_price)) ?>
                                <?php if ($request->old_total !== null && $request->requested_total !== null) : ?>
                                    <br><small><?= esc(to_currency($request->old_total)) ?> → <?= esc(to_currency($request->requested_total)) ?></small>
                                <?php endif; ?>
                                <?php if ((float) $request->old_discount !== (float) $request->requested_discount || (int) $request->old_discount_type !== (int) $request->requested_discount_type) : ?>
                                    <br><small><?= lang('Sales.discount') ?>:
                                        <?= (int) $request->old_discount_type === PERCENT ? esc(to_decimals($request->old_discount) . '%') : esc(to_currency($request->old_discount)) ?>
                                        →
                                        <?= (int) $request->requested_discount_type === PERCENT ? esc(to_decimals($request->requested_discount) . '%') : esc(to_currency($request->requested_discount)) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($request->reason) ?></td>
                            <td>
                                <?= form_open('sales/priceApproval/' . $request->approval_id, ['class' => 'form-inline']) ?>
                                    <?= form_input(['name' => 'decision_reason', 'class' => 'form-control input-sm', 'maxlength' => 255, 'placeholder' => lang('Sales.price_approval_decision_reason'), 'required' => true]) ?>
                                    <button class="btn btn-success btn-sm" name="decision" value="approved" type="submit"><?= lang('Sales.price_approval_approve') ?></button>
                                    <button class="btn btn-danger btn-sm" name="decision" value="rejected" type="submit"><?= lang('Sales.price_approval_reject') ?></button>
                                <?= form_close() ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php if ($history !== []) : ?>
        <div class="panel panel-default">
            <div class="panel-heading"><strong><?= lang('Sales.price_approval_history') ?></strong></div>
            <div class="table-responsive"><table class="table table-striped table-bordered">
                <thead><tr>
                    <th><?= lang('Sales.price_approval_requested_at') ?></th>
                    <th><?= lang('Sales.price_approval_requester') ?></th>
                    <th><?= lang('Sales.stock_location') ?></th>
                    <th><?= lang('Sales.item_name') ?></th>
                    <th><?= lang('Sales.price_approval_status') ?></th>
                    <th><?= lang('Sales.price_approval_approved_by') ?></th>
                    <th><?= lang('Sales.price_approval_decision_reason') ?></th>
                    <th><?= lang('Sales.price_approval_sale') ?></th>
                </tr></thead>
                <tbody><?php foreach ($history as $decision) : ?>
                    <tr>
                        <td><?= esc($decision->created_at) ?></td>
                        <td><?= esc($decision->requester_first_name . ' ' . $decision->requester_last_name) ?></td>
                        <td><?= esc($decision->location_name) ?></td>
                        <td><?= esc($decision->item_name) ?></td>
                        <td><?= esc(lang('Sales.price_approval_status_' . $decision->status)) ?></td>
                        <td><?= esc(trim(($decision->approver_first_name ?? '') . ' ' . ($decision->approver_last_name ?? ''))) ?></td>
                        <td><?= esc($decision->decision_reason) ?></td>
                        <td><?= $decision->sale_id === null ? '' : esc('POS ' . $decision->sale_id) ?></td>
                    </tr>
                <?php endforeach; ?></tbody>
            </table></div>
        </div>
    <?php endif; ?>

    <a class="btn btn-default" href="<?= site_url('sales') ?>"><?= lang('Sales.back_to_register') ?></a>
</div>

<?= view('partial/footer') ?>
