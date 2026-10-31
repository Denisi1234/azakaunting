<?php
/**
 * @var int $sale_id_num
 * @var bool $print_after_sale
 * @var string $customer_info
 * @var string $company_info
 * @var string $quote_number
 * @var string $transaction_date
 * @var float $total
 * @var float $discount
 * @var array $cart
 * @var float $subtotal
 * @var array $taxes
 * @var array $payments
 * @var array $config
 */
?>

<?= view('partial/header') ?>

<?php
if (isset($error_message)) {
    echo '<div class="alert alert-dismissible alert-danger">' . $error_message . '</div>';
    exit;
}
?>

<?php if (!empty($customer_email)): ?>
    <script type="text/javascript">
        $(document).ready(function() {
            const send_email = function() {
                $.get('<?= site_url() . esc("/sales/sendPdf/$sale_id_num/quote") ?>',
                    function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        })
                    }, 'json'
                );
            };

            $("#show_email_button").click(send_email);

            <?php if (!empty($email_receipt)): ?>
                send_email();
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<?= view('partial/print_receipt', ['print_after_sale' => $print_after_sale, 'selected_printer' => 'invoice_printer']) ?>

<div class="print_hide" id="control_buttons" style="text-align: right; margin-bottom: 15px;">
    <a href="javascript:printdoc();">
        <div class="btn btn-info btn-sm" id="show_print_button"><span class="glyphicon glyphicon-print">&nbsp;</span><?= lang('Common.print') ?></div>
    </a>
    <?= anchor("sales/delivery_note/$sale_id_num", '<span class="glyphicon glyphicon-file">&nbsp;</span>' . lang('Sales.delivery_note'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_delivery_note_button']) ?>
    <?php if (isset($customer_email) && !empty($customer_email)): ?>
        <a href="javascript:void(0);">
            <div class="btn btn-info btn-sm" id="show_email_button"><span class="glyphicon glyphicon-envelope">&nbsp;</span><?= lang('Sales.send_quote') ?></div>
        </a>
    <?php endif; ?>
    <?= anchor('sales', '<span class="glyphicon glyphicon-shopping-cart">&nbsp;</span>' . lang('Sales.register'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_sales_button']) ?>
    <?= anchor('sales/discardsuspendedsale', '<span class="glyphicon glyphicon-remove">&nbsp;</span>' . lang('Sales.discard'), ['class' => 'btn btn-danger btn-sm', 'id' => 'discard_quote_button']) ?>
</div>

<style>
    .tz-quote-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #333;
        background: #fff;
        max-width: 850px;
        margin: 0 auto;
        padding: 25px 30px;
    }
    .tz-header-table {
        width: 100%;
        margin-bottom: 25px;
    }
    .tz-logo-col {
        vertical-align: top;
        width: 60%;
    }
    .tz-quote-title-col {
        vertical-align: top;
        width: 40%;
        text-align: right;
    }
    .tz-quote-title {
        font-size: 26px;
        font-weight: 700;
        color: #1a365d;
        margin: 0 0 5px 0;
    }
    .tz-quote-ref {
        font-size: 15px;
        font-weight: 600;
        color: #333;
    }
    .tz-company-name {
        font-size: 18px;
        font-weight: 700;
        margin: 8px 0 3px 0;
        color: #222;
    }
    .tz-company-details {
        font-size: 12px;
        line-height: 1.5;
        color: #555;
    }
    .tz-meta-section {
        display: flex;
        justify-content: space-between;
        margin-bottom: 25px;
        font-size: 12px;
    }
    .tz-bill-to {
        width: 55%;
        line-height: 1.5;
    }
    .tz-bill-to-title {
        font-weight: 700;
        color: #777;
        margin-bottom: 4px;
        text-transform: uppercase;
        font-size: 11px;
    }
    .tz-meta-details {
        width: 40%;
        text-align: right;
        line-height: 1.6;
    }
    .tz-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
        font-size: 12px;
    }
    .tz-table th {
        background-color: #f7fafc;
        border-top: 1px solid #cbd5e0;
        border-bottom: 1px solid #cbd5e0;
        color: #718096;
        padding: 8px;
        font-weight: 600;
        text-align: left;
    }
    .tz-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #edf2f7;
        vertical-align: top;
    }
    .tz-totals-table {
        width: 45%;
        float: right;
        border-collapse: collapse;
        margin-bottom: 25px;
        font-size: 12px;
    }
    .tz-totals-table td {
        padding: 6px 8px;
    }
    .tz-totals-table .tz-total-label {
        font-weight: 600;
        color: #4a5568;
    }
    .tz-totals-table .tz-total-val {
        text-align: right;
        font-weight: 600;
    }
    .tz-status-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        border: 1px solid #e2e8f0;
        color: #718096;
        background: #f7fafc;
    }
    .tz-payment-notes {
        clear: both;
        margin-top: 25px;
        padding-top: 15px;
        border-top: 1px solid #edf2f7;
        font-size: 12px;
        line-height: 1.6;
    }
    .tz-terms {
        margin-top: 20px;
        font-size: 11px;
        color: #4a5568;
        line-height: 1.6;
    }
    .tz-sign-section {
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        page-break-inside: avoid;
    }
    .tz-sign-box {
        width: 45%;
        font-size: 12px;
    }
    .tz-stamp-box {
        width: 35%;
        height: 80px;
        border: 2px dashed #cbd5e0;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #a0aec0;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    @media print {
        body {
            background: #fff;
            padding: 0;
        }
        .tz-quote-container {
            max-width: 100%;
            padding: 0;
            margin: 0;
        }
        .print_hide {
            display: none !important;
        }
    }
</style>

<div class="tz-quote-container">
    <table class="tz-header-table">
        <tr>
            <td class="tz-logo-col">
                <?php if ($config['company_logo'] != ''): ?>
                    <img src="<?= base_url('uploads/' . esc($config['company_logo'], 'url')) ?>" alt="company_logo" style="max-height: 65px; margin-bottom: 8px;">
                <?php endif; ?>
                <?php if ($config['receipt_show_company_name']): ?>
                    <div class="tz-company-name"><?= esc($config['company']) ?></div>
                <?php endif; ?>
                <div class="tz-company-details">
                    <?= nl2br(esc($company_info)) ?>
                    <?php if (!empty($config['tax_id'])): ?>
                        <br><strong>TIN:</strong> <?= esc($config['tax_id']) ?>
                    <?php endif; ?>
                </div>
            </td>
            <td class="tz-quote-title-col">
                <div class="tz-quote-title"><?= lang('Sales.quote') ?></div>
                <div class="tz-quote-ref"><?= lang('Sales.quote_number') ?>: <?= esc($quote_number) ?></div>
            </td>
        </tr>
    </table>

    <div class="tz-meta-section">
        <div class="tz-bill-to">
            <div class="tz-bill-to-title"><?= lang('Sales.bill_to') ?? 'Bill To:' ?></div>
            <?php if (isset($customer)): ?>
                <strong><?= nl2br(esc($customer_info)) ?></strong>
            <?php else: ?>
                <span><?= lang('Sales.no_customer_selected') ?? 'Walk-in Customer' ?></span>
            <?php endif; ?>
        </div>
        <div class="tz-meta-details">
            <?php
            $quote_currency = $currency ?? null;
            $currency_label = !empty($quote_currency->currency_code)
                ? $quote_currency->currency_code . ' (' . $quote_currency->currency_symbol . ')'
                : ($config['currency_symbol'] ?: 'TZS');
            ?>
            <div><strong><?= lang('Sales.currency') ?>:</strong> <?= esc($currency_label) ?></div>
            <div><strong><?= lang('Sales.quote_date') ?>:</strong> <?= esc($transaction_date) ?></div>
            <div><strong><?= lang('Sales.quote_expiry_date') ?>:</strong> <?= !empty($quote_expiry_date) ? esc($quote_expiry_date) : '&mdash;' ?></div>
            <?php if (!empty($quote_reference)): ?>
                <div><strong><?= lang('Sales.quote_reference') ?>:</strong> <?= esc($quote_reference) ?></div>
            <?php endif; ?>
            <?php if (!empty($salesperson)): ?>
                <div><strong><?= lang('Sales.salesperson') ?>:</strong> <?= esc($salesperson) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <table class="tz-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 25%;"><?= lang('Sales.item_name') ?></th>
                <th style="width: 35%;"><?= lang('Common.description') ?? 'Description' ?></th>
                <th style="width: 10%; text-align: center;"><?= lang('Sales.quantity') ?></th>
                <th style="width: 12%; text-align: right;"><?= lang('Sales.price') ?></th>
                <th style="width: 13%; text-align: right;">Amount Net Tax</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $i = 1;
            foreach ($cart as $line => $item): 
                if ($item['print_option'] == PRINT_YES):
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><strong><?= esc($item['name']) ?></strong></td>
                    <td><?= !empty($item['description']) ? esc($item['description']) : esc($item['name']) ?></td>
                    <td style="text-align: center;"><?= to_quantity_decimals($item['quantity']) ?></td>
                    <td style="text-align: right;"><?= to_currency_no_money($item['price']) ?></td>
                    <td style="text-align: right;"><?= to_currency_no_money($item['discounted_total']) ?></td>
                </tr>
            <?php 
                endif;
            endforeach; 
            ?>
        </tbody>
    </table>

    <table class="tz-totals-table">
        <tr>
            <td class="tz-total-label"><?= lang('Sales.sub_total') ?></td>
            <td class="tz-total-val"><?= to_currency($subtotal) ?></td>
        </tr>
        <?php foreach ($taxes as $tax): ?>
            <tr>
                <td class="tz-total-label">Tax: <?= esc($tax['name'] ?? 'VAT:18%') ?></td>
                <td class="tz-total-val"><?= to_currency_tax($tax['sale_tax_amount']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($taxes)): ?>
            <tr>
                <td class="tz-total-label">Tax: VAT:18%</td>
                <td class="tz-total-val"><?= to_currency(0) ?></td>
            </tr>
        <?php endif; ?>
        <tr style="border-top: 1px solid #cbd5e0; font-size: 14px;">
            <td class="tz-total-label"><strong><?= lang('Sales.total') ?></strong></td>
            <td class="tz-total-val" style="color: #1a365d;"><strong><?= to_currency($total) ?></strong></td>
        </tr>
        <tr>
            <td class="tz-total-label">Pay Status</td>
            <td class="tz-total-val"><span class="tz-status-badge">PENDING</span></td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <?php
    $payment_details = !empty($quote_payment_details) ? $quote_payment_details : ($config['quote_payment_details'] ?? '');
    $quote_terms     = !empty($quote_terms_and_conditions) ? $quote_terms_and_conditions : ($config['quote_terms_and_conditions'] ?? '');
    ?>

    <?php if (!empty($comments)): ?>
        <div class="tz-payment-notes">
            <strong><?= lang('Sales.comments') ?>:</strong><br>
            <?= nl2br(esc($comments)) ?>
        </div>
    <?php endif; ?>

    <div class="tz-payment-notes">
        <?php if (!empty($payment_details)): ?>
            <strong><?= lang('Sales.quote_payment_details') ?>:</strong><br>
            <?= nl2br(esc($payment_details)) ?>
        <?php else: ?>
            <strong><?= lang('Sales.quote_payment_details') ?>:</strong><br>
            <?= lang('Sales.quote_payment_details_default') ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($quote_terms)): ?>
        <div class="tz-terms">
            <strong><?= lang('Sales.quote_terms_and_conditions') ?></strong><br>
            <?= nl2br(esc($quote_terms)) ?>
        </div>
    <?php endif; ?>

    <div class="tz-sign-section">
        <div class="tz-sign-box">
            <div>Signature .....................................................</div>
            <div style="font-size: 11px; color: #718096; margin-top: 5px;">Please quote above invoice/proforma number on all documents.</div>
        </div>
        <div class="tz-stamp-box">
            Stamp
        </div>
    </div>
</div>

<script type="text/javascript">
    $(window).on("load", function() {
        if (window.jsPrintSetup) {
            <?php if (!$config['print_header']): ?>
                jsPrintSetup.setOption('headerStrLeft', '');
                jsPrintSetup.setOption('headerStrCenter', '');
                jsPrintSetup.setOption('headerStrRight', '');
            <?php endif; ?>
            <?php if (!$config['print_footer']): ?>
                jsPrintSetup.setOption('footerStrLeft', '');
                jsPrintSetup.setOption('footerStrCenter', '');
                jsPrintSetup.setOption('footerStrRight', '');
            <?php endif; ?>
        }
    });
</script>

<?= view('partial/footer') ?>