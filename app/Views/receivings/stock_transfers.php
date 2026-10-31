<?php
/** @var array $transfers */
?>

<?= view('partial/header') ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xs-12">
            <h3><?= lang('Receivings.stock_transfers') ?></h3>
            <p><a class="btn btn-default btn-sm" href="<?= site_url('receivings') ?>"><?= lang('Receivings.receiving') ?></a></p>
            <?php if (! empty($error)) { ?><div class="alert alert-danger"><?= esc($error) ?></div><?php } ?>
            <?php if (! empty($success)) { ?><div class="alert alert-success"><?= esc($success) ?></div><?php } ?>
            <?php if ($transfers === []) { ?>
                <div class="alert alert-info"><?= lang('Receivings.transfer_empty') ?></div>
            <?php } ?>
            <?php foreach ($transfers as $transfer) { ?>
                <section class="panel panel-default">
                    <div class="panel-heading">
                        <strong><?= esc($transfer['transfer_number']) ?></strong>
                        <span class="label label-info pull-right"><?= esc(lang('Receivings.transfer_status_' . $transfer['status'])) ?></span>
                    </div>
                    <div class="panel-body">
                        <p><?= esc($transfer['source_name']) ?> &rarr; <?= esc($transfer['destination_name']) ?></p>
                        <p><?= esc(lang('Receivings.transfer_requested_by')) ?>: <?= esc($transfer['creator_first_name'] . ' ' . $transfer['creator_last_name']) ?> · <?= esc($transfer['created_at']) ?></p>
                        <?php if (! empty($transfer['approved_by'])) { ?><p><?= lang('Receivings.transfer_approve') ?>: <?= esc($transfer['approver_first_name'] . ' ' . $transfer['approver_last_name']) ?> · <?= esc($transfer['approved_at']) ?></p><?php } ?>
                        <?php if (! empty($transfer['dispatched_by'])) { ?><p><?= lang('Receivings.transfer_dispatch') ?>: <?= esc($transfer['dispatcher_first_name'] . ' ' . $transfer['dispatcher_last_name']) ?> · <?= esc($transfer['dispatched_at']) ?></p><?php } ?>
                        <?php if (! empty($transfer['received_by'])) { ?><p><?= lang('Receivings.transfer_receive') ?>: <?= esc($transfer['receiver_first_name'] . ' ' . $transfer['receiver_last_name']) ?> · <?= esc($transfer['received_at']) ?></p><?php } ?>
                        <?php if (! empty($transfer['cancelled_by'])) { ?><p><?= lang('Receivings.transfer_cancel') ?>: <?= esc($transfer['canceller_first_name'] . ' ' . $transfer['canceller_last_name']) ?> · <?= esc($transfer['cancelled_at']) ?></p><?php } ?>
                        <?php if (! empty($transfer['comment'])) { ?><p><?= esc($transfer['comment']) ?></p><?php } ?>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead><tr><th><?= lang('Receivings.item_name') ?></th><th><?= lang('Receivings.transfer_requested') ?></th><th><?= lang('Receivings.transfer_dispatched_qty') ?></th><th><?= lang('Receivings.transfer_received_qty') ?></th><th><?= lang('Receivings.transfer_in_transit') ?></th></tr></thead>
                                <tbody>
                                <?php foreach ($transfer['items'] as $item) { ?>
                                    <tr><td><?= esc($item['name']) ?></td><td><?= esc($item['requested_quantity']) ?></td><td><?= esc($item['dispatched_quantity']) ?></td><td><?= esc($item['received_quantity']) ?></td><td><?= esc((float) $item['dispatched_quantity'] - (float) $item['received_quantity']) ?></td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <?php if ($transfer['status'] === 'requested' && $transfer['canApprove']) { ?>
                                <div class="col-sm-3"><form method="post" action="<?= site_url('stock_transfers/approve/' . $transfer['transfer_id']) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit"><?= lang('Receivings.transfer_approve') ?></button></form></div>
                            <?php } ?>
                            <?php if ($transfer['status'] === 'approved' && $transfer['canDispatch']) { ?>
                                <div class="col-sm-3"><form method="post" action="<?= site_url('stock_transfers/dispatch/' . $transfer['transfer_id']) ?>"><?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit" onclick="return confirm('<?= esc(lang('Receivings.transfer_dispatch_confirm'), 'js') ?>')"><?= lang('Receivings.transfer_dispatch') ?></button></form></div>
                            <?php } ?>
                            <?php if (in_array($transfer['status'], ['in_transit', 'partially_received'], true) && $transfer['canReceive']) { ?>
                                <div class="col-xs-12"><form method="post" action="<?= site_url('stock_transfers/receive/' . $transfer['transfer_id']) ?>"><?= csrf_field() ?>
                                    <h4><?= lang('Receivings.transfer_receive') ?></h4>
                                    <?php foreach ($transfer['items'] as $item) { $remaining = (float) $item['dispatched_quantity'] - (float) $item['received_quantity']; if ($remaining <= 0) { continue; } ?>
                                        <div class="form-group row"><label class="col-sm-4 control-label"><?= esc($item['name']) ?> (<?= lang('Receivings.transfer_remaining') ?>: <?= esc($remaining) ?>)</label><div class="col-sm-3"><input class="form-control" type="number" min="0" max="<?= esc($remaining) ?>" step="0.0001" name="received_quantity[<?= (int) $item['item_id'] ?>]" value="0" required></div></div>
                                    <?php } ?>
                                    <div class="checkbox"><label><input type="checkbox" name="finalize" value="1"> <?= lang('Receivings.transfer_finalize') ?></label></div>
                                    <div class="form-group"><label><?= lang('Receivings.transfer_discrepancy') ?></label><input class="form-control" type="text" name="discrepancy_note" maxlength="255"></div>
                                    <button class="btn btn-success btn-sm" type="submit"><?= lang('Receivings.transfer_record_receipt') ?></button>
                                </form></div>
                            <?php } ?>
                            <?php if (in_array($transfer['status'], ['requested', 'approved'], true) && $transfer['canDispatch']) { ?>
                                <div class="col-sm-4"><form method="post" action="<?= site_url('stock_transfers/cancel/' . $transfer['transfer_id']) ?>"><?= csrf_field() ?><div class="form-group"><label><?= lang('Receivings.transfer_cancel_reason') ?></label><input class="form-control input-sm" type="text" name="reason" minlength="5" maxlength="255" required></div><button class="btn btn-default btn-sm" type="submit"><?= lang('Receivings.transfer_cancel') ?></button></form></div>
                            <?php } ?>
                        </div>
                        <?php if (! empty($transfer['discrepancy_note'])) { ?><p class="text-warning"><?= lang('Receivings.transfer_discrepancy') ?>: <?= esc($transfer['discrepancy_note']) ?></p><?php } ?>
                        <?php if (! empty($transfer['cancellation_reason'])) { ?><p class="text-warning"><?= lang('Receivings.transfer_cancel_reason') ?>: <?= esc($transfer['cancellation_reason']) ?></p><?php } ?>
                    </div>
                </section>
            <?php } ?>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>
