<?= view('partial/header') ?>

<link rel="stylesheet" href="<?= base_url('css/invoice.css') ?>">

<style>
    @media print {
        .print_hide { display: none !important; }
        body { background: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        #payslip_receipt { border: none !important; box-shadow: none !important; margin: 0; padding: 20px; }
    }
    #payslip_receipt {
        max-width: 780px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 40px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06);
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        color: #333;
    }
    .ps-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #1a3a5c;
        padding-bottom: 16px;
        margin-bottom: 24px;
    }
    .ps-header .logo img { max-height: 56px; max-width: 140px; }
    .ps-header .logo h2 { margin: 0; color: #1a3a5c; font-size: 22px; font-weight: 700; }
    .ps-header .company { text-align: right; }
    .ps-header .company h3 { margin: 0 0 4px; color: #1a3a5c; font-size: 18px; font-weight: 700; }
    .ps-header .company p { margin: 0; font-size: 12px; color: #666; line-height: 1.5; }
    .ps-title {
        text-align: center;
        margin-bottom: 24px;
    }
    .ps-title h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #1a3a5c;
        letter-spacing: 3px;
        text-transform: uppercase;
    }
    .ps-title .ps-subtitle {
        font-size: 12px;
        color: #999;
        margin-top: 4px;
        letter-spacing: 1px;
    }
    .ps-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .ps-info-cell {
        padding: 10px 14px;
        border-bottom: 1px solid #e0e0e0;
        font-size: 13px;
    }
    .ps-info-cell:nth-child(odd) { background: #f7f9fc; }
    .ps-info-cell strong { color: #1a3a5c; }
    .ps-info-cell .label-value { float: right; color: #333; }
    .ps-section-title {
        font-size: 13px;
        font-weight: 700;
        color: #1a3a5c;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-bottom: 1px solid #1a3a5c;
        padding-bottom: 6px;
        margin: 20px 0 10px;
    }
    .ps-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }
    .ps-table th {
        background: #1a3a5c;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 12px;
        border: none;
    }
    .ps-table td {
        padding: 8px 12px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
    }
    .ps-table tr:last-child td { border-bottom: none; }
    .ps-table .amount { text-align: right; font-variant-numeric: tabular-nums; }
    .ps-table .deduction { color: #c0392b; }
    .ps-table .addition { color: #27ae60; }
    .ps-table .total-row {
        background: #1a3a5c;
        color: #fff;
        font-weight: 700;
        font-size: 14px;
    }
    .ps-table .total-row td { border: none; padding: 10px 12px; }
    .ps-bank {
        background: #f7f9fc;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 12px 16px;
        margin-bottom: 20px;
        font-size: 12px;
    }
    .ps-bank strong { color: #1a3a5c; }
    .ps-notes {
        background: #fff8e1;
        border-left: 3px solid #f39c12;
        padding: 10px 14px;
        margin-bottom: 20px;
        font-size: 12px;
        border-radius: 0 4px 4px 0;
    }
    .ps-signatures {
        display: flex;
        justify-content: space-between;
        margin-top: 40px;
        padding-top: 20px;
    }
    .ps-sig {
        width: 45%;
        text-align: center;
    }
    .ps-sig .line {
        border-top: 1px solid #999;
        margin-top: 40px;
        padding-top: 6px;
        font-size: 11px;
        color: #999;
    }
    .ps-footer {
        margin-top: 30px;
        padding-top: 12px;
        border-top: 1px solid #e0e0e0;
        text-align: center;
        font-size: 10px;
        color: #aaa;
        line-height: 1.6;
    }
    .ps-status-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .ps-status-paid { background: #d4edda; color: #155724; }
    .ps-status-approved { background: #cce5ff; color: #004085; }
    .ps-status-draft { background: #e2e3e5; color: #383d41; }
</style>

<div class="row print_hide" style="margin-bottom: 15px;">
    <div class="col-md-10 col-md-offset-1 text-right">
        <button class="btn btn-primary btn-sm" onclick="window.print();">
            <span class="glyphicon glyphicon-print"></span> <?= lang('Payroll.print_payslip') ?>
        </button>
        <a href="<?= base_url('payroll') ?>" class="btn btn-default btn-sm">
            <span class="glyphicon glyphicon-arrow-left"></span> <?= lang('Common.back') ?>
        </a>
    </div>
</div>

<div id="payslip_receipt">
    <!-- Header -->
    <div class="ps-header">
        <div class="logo">
            <?php
            $logo = config('OSPOS')->settings['company_logo'] ?? '';
            if (!empty($logo)) {
                echo '<img src="' . base_url('uploads/' . $logo) . '" alt="Logo">';
            } else {
                echo '<div style="font-size:24px; font-weight:700; color:#1a3a5c;">' . esc(config('OSPOS')->settings['company'] ?? 'Aza Akaunting') . '</div>';
            }
            ?>
        </div>
        <div class="company">
            <h3><?= esc(config('OSPOS')->settings['company'] ?? 'Aza Akaunting') ?></h3>
            <p><?= esc(config('OSPOS')->settings['address'] ?? '') ?></p>
            <p><?= esc(config('OSPOS')->settings['phone'] ?? '') ?></p>
            <p><?= esc(config('OSPOS')->settings['email'] ?? '') ?></p>
            <p><?= esc(config('OSPOS')->settings['website'] ?? '') ?></p>
        </div>
    </div>

    <!-- Title -->
    <div class="ps-title">
        <h2><?= lang('Payroll.payslip') ?></h2>
        <div class="ps-subtitle"><?= lang('Payroll.payslip_no') ?? 'Payslip' ?> #<?= esc($item->payroll_item_id) ?> &nbsp;|&nbsp; <?= lang('Payroll.period') ?>: <?= esc($item->period_start) ?> — <?= esc($item->period_end) ?></div>
    </div>

    <!-- Employee & meta info grid -->
    <div class="ps-info-grid">
        <div class="ps-info-cell">
            <strong><?= lang('Payroll.employee') ?>:</strong>
            <span class="label-value"><?= esc($item->first_name . ' ' . $item->last_name) ?></span>
        </div>
        <div class="ps-info-cell">
            <strong><?= lang('Employees.username') ?>:</strong>
            <span class="label-value"><?= esc($item->username ?? '') ?></span>
        </div>
        <div class="ps-info-cell">
            <strong><?= lang('Payroll.pay_type') ?>:</strong>
            <span class="label-value"><?= ucfirst(esc($item->pay_type)) ?>
                <?php if ($item->pay_type === 'hourly'): ?>
                    (<?= esc($item->total_hours) ?> <?= lang('Payroll.hours') ?? 'hrs' ?>)
                <?php endif; ?>
            </span>
        </div>
        <div class="ps-info-cell">
            <strong><?= lang('Payroll.status') ?>:</strong>
            <span class="label-value">
                <span class="ps-status-badge ps-status-<?= esc($item->status) ?>"><?= strtoupper(esc($item->status)) ?></span>
            </span>
        </div>
        <?php if ($item->paid_date): ?>
        <div class="ps-info-cell">
            <strong><?= lang('Payroll.paid_date') ?>:</strong>
            <span class="label-value"><?= esc($item->paid_date) ?></span>
        </div>
        <?php endif; ?>
        <div class="ps-info-cell">
            <strong><?= lang('Payroll.generated') ?? 'Generated' ?>:</strong>
            <span class="label-value"><?= date('Y-m-d H:i') ?></span>
        </div>
    </div>

    <!-- Earnings & deductions -->
    <div class="ps-section-title"><?= lang('Payroll.earnings') ?? 'Earnings & Deductions' ?></div>
    <table class="ps-table">
        <thead>
            <tr>
                <th><?= lang('Payroll.description') ?? 'Description' ?></th>
                <th class="amount"><?= lang('Payroll.amount') ?? 'Amount' ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= lang('Payroll.basic_salary') ?></td>
                <td class="amount"><?= to_currency((float) $item->basic_salary) ?></td>
            </tr>
            <tr>
                <td><?= lang('Payroll.allowances') ?></td>
                <td class="amount addition">+ <?= to_currency((float) $item->allowances) ?></td>
            </tr>
            <tr>
                <td><?= lang('Payroll.deductions') ?></td>
                <td class="amount deduction">- <?= to_currency((float) $item->deductions) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td><?= lang('Payroll.net_pay') ?></td>
                <td class="amount"><?= to_currency((float) $item->net_pay) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Signatures -->
    <div class="ps-signatures">
        <div class="ps-sig">
            <div class="line"><?= lang('Payroll.employee_signature') ?? 'Employee Signature' ?></div>
        </div>
        <div class="ps-sig">
            <div class="line"><?= lang('Payroll.authorized_signature') ?? 'Authorized Signature' ?></div>
        </div>
    </div>

    <!-- Footer -->
    <div class="ps-footer">
        <?= lang('Payroll.payslip_footer') ?? 'This is a computer-generated payslip. Please verify all details and report discrepancies within 7 days.' ?>
        <br>
        <?= lang('Common.powered_by') ?> Aza Akaunting &nbsp;|&nbsp; <?= date('Y-m-d H:i:s') ?>
    </div>
</div>

<?= view('partial/footer') ?>
