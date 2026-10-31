<?= view('partial/header') ?>
<div class="container-fluid">
    <h3><?= lang('Sales.quote') ?> <?= esc($quote->quote_number) ?></h3>
    <?php if (session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div><?php endif; ?>
    <?php if (session()->getFlashdata('message')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div><?php endif; ?>
    <p><?= esc($quote->customer_name) ?> · <?= esc($quote->salesperson_name) ?> · <strong><?= lang('Quotations.status_' . $quote->status) ?></strong></p>
    <p><?= lang('Quotations.review_link') ?>: <a href="<?= esc(site_url('quotation/portal/' . $quote->public_token), 'attr') ?>"><?= esc(site_url('quotation/portal/' . $quote->public_token)) ?></a>
        <a class="btn btn-xs btn-default" target="_blank" href="https://wa.me/?text=<?= rawurlencode(site_url('quotation/portal/' . $quote->public_token)) ?>">WhatsApp</a>
        <a class="btn btn-xs btn-default" href="<?= site_url('quotations/pdf/' . (int) $quote->sale_id) ?>"><?= lang('Quotations.download_pdf') ?></a>
        <button class="btn btn-xs btn-default" id="send_quotation_email"><?= lang('Sales.send_quote') ?></button>
    </p>
    <script>$('#send_quotation_email').on('click', function () { $.getJSON('<?= site_url('sales/sendPdf/' . (int) $quote->sale_id . '/quote') ?>', function (response) { $.notify({message: response.message}, {type: response.success ? 'success' : 'danger'}); }); });</script>
    <div class="row"><div class="col-md-6">
        <?= form_open('quotations/update/' . (int) $quote->sale_id) ?>
            <div class="form-group"><label><?= lang('Quotations.reference') ?></label><input class="form-control" name="reference_number" value="<?= esc($quote->reference_number, 'attr') ?>"></div>
            <div class="form-group"><label><?= lang('Quotations.valid_until') ?></label><input class="form-control" type="date" name="valid_until" value="<?= esc($quote->valid_until, 'attr') ?>"></div>
            <div class="form-group"><label><?= lang('Quotations.internal_notes') ?></label><textarea class="form-control" name="internal_notes"><?= esc($quote->internal_notes) ?></textarea></div>
            <div class="form-group"><label><?= lang('Quotations.template') ?></label><?= form_dropdown('template_code', ['modern' => lang('Quotations.template_modern'), 'classic' => lang('Quotations.template_classic')], $quote->template_code, ['class' => 'form-control']) ?></div>
            <?php if (! in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)): ?><button class="btn btn-primary"><?= lang('Common.submit') ?></button><?php endif; ?>
        <?= form_close() ?>
        <h4><?= lang('Quotations.options') ?></h4>
        <?php foreach ($options as $option): ?><p><?= esc($option->description) ?> · <?= esc($option->quantity) ?> × <?= to_currency($option->unit_price) ?> <?= form_open('quotations/options/' . (int) $quote->sale_id . '/delete/' . (int) $option->quotation_option_id, ['class' => 'inline']) ?><button class="btn btn-xs btn-danger">×</button><?= form_close() ?></p><?php endforeach; ?>
        <?php if (! in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)): ?><?= form_open('quotations/options/' . (int) $quote->sale_id) ?><input class="form-control" name="description" required placeholder="<?= lang('Quotations.description') ?>"><input class="form-control" name="quantity" type="number" step="0.001" min="0.001" value="1"><input class="form-control" name="unit_price" type="number" step="0.01" min="0" value="0"><button class="btn btn-default"> <?= lang('Quotations.add_option') ?></button><?= form_close() ?><?php endif; ?>
    </div><div class="col-md-6">
        <h4><?= lang('Quotations.attachments') ?></h4>
        <?php foreach ($attachments as $attachment): ?><p><a href="<?= site_url('quotations/attachments/' . (int) $attachment->quotation_attachment_id) ?>"><?= esc($attachment->file_name) ?></a></p><?php endforeach; ?>
        <?php if (! in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)): ?><?= form_open_multipart('quotations/attachments/' . (int) $quote->sale_id) ?><input type="file" name="attachment" required><button class="btn btn-default"><?= lang('Quotations.upload') ?></button><?= form_close() ?><?php endif; ?>
        <h4><?= lang('Quotations.revision_history') ?></h4><ul><?php foreach ($revisions as $revision): ?><li><?= esc($revision->created_at) ?> · <?= esc($revision->revision_note) ?> · v<?= (int) $revision->revision_number ?></li><?php endforeach; ?></ul>
        <?php if ($quote->customer_response): ?><h4><?= lang('Quotations.customer_response') ?></h4><p><?= nl2br(esc($quote->customer_response)) ?></p><?php endif; ?>
    </div></div>
    <h4><?= lang('Quotations.items') ?></h4><div class="table-responsive"><table class="table"><thead><tr><th><?= lang('Quotations.description') ?></th><th><?= lang('Quotations.quantity') ?></th><th><?= lang('Quotations.unit_price') ?></th><th><?= lang('Quotations.total') ?></th></tr></thead><tbody><?php foreach ($items as $item): ?><tr><td><?= esc($item->item_name ?: $item->description) ?></td><td><?= esc($item->quantity_purchased) ?></td><td><?= to_currency($item->item_unit_price) ?></td><td><?= to_currency(($item->discount_type === PERCENT ? $item->quantity_purchased * $item->item_unit_price * (1 - $item->discount / 100) : $item->quantity_purchased * ($item->item_unit_price - $item->discount))) ?></td></tr><?php endforeach; ?><?php foreach ($taxes as $tax): ?><tr><td colspan="3"><?= esc($tax->name ?: $tax->tax_group) ?> (<?= esc($tax->tax_rate) ?>%)</td><td class="num"><?= to_currency($tax->sale_tax_amount) ?></td></tr><?php endforeach; ?></tbody></table><strong><?= lang('Sales.total') ?>: <?= to_currency($total) ?></strong></div>
    <div class="btn-toolbar">
        <?= form_open('quotations/duplicate/' . (int) $quote->sale_id) ?><button class="btn btn-default"><?= lang('Quotations.duplicate') ?></button><?= form_close() ?>
        <?php if ($quote->status === 'accepted'): ?><?= form_open('quotations/convert/' . (int) $quote->sale_id) ?><button class="btn btn-success"><?= lang('Quotations.convert') ?></button><?= form_close() ?><?php endif; ?>
        <?php if (! in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)): ?><?= form_open('quotations/cancel/' . (int) $quote->sale_id) ?><button class="btn btn-danger"><?= lang('Quotations.cancel') ?></button><?= form_close() ?><?php endif; ?>
    </div>
</div>
<?= view('partial/footer') ?>
