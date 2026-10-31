<?= view('partial/header') ?>
<div class="container-fluid">
    <h3><?= lang('Quotations.title') ?></h3>
    <div class="row">
        <?php foreach (['draft', 'sent', 'accepted', 'rejected', 'expired'] as $state): ?>
            <div class="col-sm-2"><a class="panel panel-default" href="<?= site_url('quotations?status=' . $state) ?>"><div class="panel-body"><strong><?= lang('Quotations.status_' . $state) ?></strong><div class="h3"><?= (int) ($dashboard[$state] ?? 0) ?></div></div></a></div>
        <?php endforeach; ?>
        <div class="col-sm-2"><div class="panel panel-info"><div class="panel-body"><strong><?= lang('Quotations.conversion_rate') ?></strong><div class="h3"><?= esc($dashboard['conversion_rate']) ?>%</div></div></div></div>
        <div class="col-sm-2"><div class="panel panel-success"><div class="panel-body"><strong><?= lang('Quotations.total_value') ?></strong><div class="h3"><?= to_currency($dashboard['total_value']) ?></div></div></div></div>
    </div>
    <?php if (! empty($dashboard['monthly'])): ?><div class="panel panel-default"><div class="panel-heading"><?= lang('Quotations.monthly_performance') ?></div><div class="table-responsive"><table class="table table-condensed"><thead><tr><th><?= lang('Common.date') ?></th><th><?= lang('Quotations.title') ?></th><th><?= lang('Quotations.converted') ?></th></tr></thead><tbody><?php foreach ($dashboard['monthly'] as $month): ?><tr><td><?= esc($month->month) ?></td><td><?= (int) $month->quotes ?></td><td><?= (int) $month->converted ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
    <?= form_open('quotations', ['method' => 'get', 'class' => 'form-inline']) ?>
        <?= form_input(['name' => 'search', 'value' => $search, 'class' => 'form-control', 'placeholder' => lang('Common.search')]) ?>
        <?= form_dropdown('status', ['all' => lang('Quotations.all_statuses'), 'draft' => lang('Quotations.status_draft'), 'sent' => lang('Quotations.status_sent'), 'viewed' => lang('Quotations.status_viewed'), 'accepted' => lang('Quotations.status_accepted'), 'rejected' => lang('Quotations.status_rejected'), 'expired' => lang('Quotations.status_expired'), 'converted' => lang('Quotations.status_converted'), 'cancelled' => lang('Quotations.status_cancelled'), 'revision_requested' => lang('Quotations.status_revision_requested')], $status, ['class' => 'form-control']) ?>
        <button class="btn btn-primary"><?= lang('Common.search') ?></button>
    <?= form_close() ?>
    <div class="table-responsive"><table class="table table-striped table-hover">
        <thead><tr><th><?= lang('Sales.quote_number') ?></th><th><?= lang('Sales.customer') ?></th><th><?= lang('Common.date') ?></th><th><?= lang('Quotations.valid_until') ?></th><th><?= lang('Quotations.reference') ?></th><th><?= lang('Quotations.status') ?></th><th><?= lang('Sales.total') ?></th><th></th></tr></thead>
        <tbody><?php foreach ($quotes as $quote): ?><tr>
            <td><?= esc($quote->quote_number) ?></td><td><?= esc($quote->customer_name) ?></td><td><?= esc($quote->sale_time) ?></td><td><?= esc($quote->valid_until) ?></td><td><?= esc($quote->reference_number) ?></td><td><span class="label label-default"><?= lang('Quotations.status_' . $quote->status) ?></span></td><td><?= to_currency($quote->amount) ?></td><td><?= anchor('quotations/view/' . (int) $quote->sale_id, lang('Quotations.open'), ['class' => 'btn btn-xs btn-primary']) ?></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
</div>
<?= view('partial/footer') ?>
