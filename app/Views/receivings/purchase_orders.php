<?php
/** @var array $orders */
/** @var bool $canApprove */
/** @var string|null $success */
/** @var string|null $error */
?>

<?= view('partial/header') ?>

<?php if ($success !== null): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
<?php endif; ?>
<?php if ($error !== null): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
<?php endif; ?>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title"><?= lang('Receivings.purchase_orders') ?></h3>
    </div>
    <div class="panel-body table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th><?= lang('Receivings.purchase_order_number') ?></th>
                    <th><?= lang('Receivings.select_supplier') ?></th>
                    <th><?= lang('Receivings.order_date') ?></th>
                    <th><?= lang('Receivings.expected_date') ?></th>
                    <th><?= lang('Receivings.stock_location') ?></th>
                    <th><?= lang('Receivings.purchase_order_status') ?></th>
                    <th><?= lang('Receivings.purchase_order_items') ?></th>
                    <th><?= lang('Receivings.employee') ?></th>
                    <th><?= lang('Receivings.purchase_order_decision') ?></th>
                    <th><?= lang('Receivings.actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= esc($order['order_number']) ?></td>
                        <td><?= esc(trim(($order['company_name'] ?: $order['supplier_first_name']) . ' ' . $order['supplier_last_name'])) ?></td>
                        <td><?= esc($order['order_date']) ?></td>
                        <td><?= esc($order['expected_date'] ?? '') ?></td>
                        <td><?= esc($order['location_name']) ?></td>
                        <td><?= esc(lang('Receivings.purchase_order_status_' . $order['status'])) ?></td>
                        <td>
                            <ul class="list-unstyled">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li><?= esc($item['name']) ?>: <?= esc(to_quantity_decimals($item['received_quantity'])) ?> / <?= esc(to_quantity_decimals($item['ordered_quantity'])) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                        <td><?= esc(trim($order['creator_first_name'] . ' ' . $order['creator_last_name'])) ?></td>
                        <td>
                            <?php if ($order['approved_by'] !== null): ?>
                                <?= esc(lang('Receivings.approve') . ': ' . trim($order['approver_first_name'] . ' ' . $order['approver_last_name']) . ' — ' . $order['approval_reason']) ?>
                            <?php elseif ($order['cancelled_by'] !== null): ?>
                                <?= esc(lang('Receivings.purchase_order_status_cancelled') . ': ' . trim($order['canceller_first_name'] . ' ' . $order['canceller_last_name']) . ' — ' . $order['cancellation_reason']) ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($order['status'] === 'draft' && $canApprove): ?>
                                <?= form_open('purchase_orders/approve/' . $order['purchase_order_id'], ['class' => 'form-inline']) ?>
                                    <?= form_input(['name' => 'reason', 'class' => 'form-control input-sm', 'placeholder' => lang('Receivings.purchase_order_decision_reason'), 'maxlength' => 255, 'required' => true, 'minlength' => 5]) ?>
                                    <button type="submit" class="btn btn-success btn-sm"><?= lang('Receivings.approve') ?></button>
                                <?= form_close() ?>
                            <?php elseif (in_array($order['status'], ['ordered', 'partially_received'], true)): ?>
                                <?= form_open('purchase_orders/receive/' . $order['purchase_order_id'], ['class' => 'form-inline']) ?>
                                    <button type="submit" class="btn btn-primary btn-sm"><?= lang('Receivings.receive_purchase_order') ?></button>
                                <?= form_close() ?>
                            <?php endif; ?>
                            <?php if (in_array($order['status'], ['draft', 'ordered'], true)
                                && ($canApprove || (int) $order['created_by'] === (int) session()->get('person_id'))): ?>
                                <?= form_open('purchase_orders/cancel/' . $order['purchase_order_id'], ['class' => 'form-inline']) ?>
                                    <?= form_input(['name' => 'reason', 'class' => 'form-control input-sm', 'placeholder' => lang('Receivings.purchase_order_cancel_reason'), 'maxlength' => 255, 'required' => true, 'minlength' => 5]) ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?= esc(lang('Receivings.purchase_order_cancel_confirm'), 'js') ?>')"><?= lang('Receivings.cancel_receiving') ?></button>
                                <?= form_close() ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a class="btn btn-default" href="<?= site_url('receivings') ?>"><?= lang('Common.back') ?></a>
    </div>
</div>

<?= view('partial/footer') ?>
