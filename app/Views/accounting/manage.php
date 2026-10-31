<?= view('partial/header') ?>

<div id="title_bar" class="btn-toolbar print_hide">
    <button onclick="window.print();" class="btn btn-info btn-sm pull-right">
        <span class="glyphicon glyphicon-print">&nbsp;</span><?= lang('Common.print') ?>
    </button>
</div>

<!-- Tab Navigation using native OSPOS/Bootstrap pill tabs -->
<ul class="nav nav-tabs print_hide" style="margin-bottom: 20px;">
    <li class="<?= $active_tab === 'profit_loss' ? 'active' : '' ?>">
        <?= anchor('accounting/profitLoss?start_date=' . ($start_date ?? '') . '&end_date=' . ($end_date ?? ''), '<span class="glyphicon glyphicon-stats">&nbsp;</span>Income Statement (P&L)') ?>
    </li>
    <li class="<?= $active_tab === 'balance_sheet' ? 'active' : '' ?>">
        <?= anchor('accounting/balanceSheet?end_date=' . ($as_of_date ?? date('Y-m-d')), '<span class="glyphicon glyphicon-book">&nbsp;</span>Balance Sheet') ?>
    </li>
    <li class="<?= $active_tab === 'bank_reconciliation' ? 'active' : '' ?>">
        <?= anchor('accounting/bankReconciliation?start_date=' . ($start_date ?? '') . '&end_date=' . ($end_date ?? ''), '<span class="glyphicon glyphicon-credit-card">&nbsp;</span>Bank & Digital Reconciliation') ?>
    </li>
</ul>

<?php if ($active_tab === 'profit_loss'): ?>
    <!-- Date Filter Form using native OSPOS form-inline styling -->
    <div id="toolbar" class="print_hide">
        <div class="pull-left form-inline">
            <?= form_open('accounting/profitLoss', ['method' => 'get', 'class' => 'form-inline']) ?>
                <div class="form-group form-group-sm">
                    <label>From:&nbsp;</label>
                    <input type="date" name="start_date" class="form-control input-sm" value="<?= esc($start_date) ?>">
                </div>
                <div class="form-group form-group-sm">
                    <label>&nbsp;To:&nbsp;</label>
                    <input type="date" name="end_date" class="form-control input-sm" value="<?= esc($end_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span class="glyphicon glyphicon-search">&nbsp;</span>Filter
                </button>
            <?= form_close() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading text-center">
                    <h3 class="panel-title" style="font-size: 18px; font-weight: bold;">
                        Statement of Profit & Loss (Income Statement)
                    </h3>
                    <small class="text-muted">For Period: <?= esc($start_date) ?> to <?= esc($end_date) ?></small>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th>Account Category</th>
                                <th style="width: 25%; text-align: right;">Amount (<?= esc($config['currency_symbol']) ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- REVENUE -->
                            <tr class="active">
                                <td colspan="2"><strong>1. Operating Revenue (Sales)</strong></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Gross Sales Revenue</td>
                                <td style="text-align: right;"><?= to_currency($gross_sales) ?></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Less: Sales Discounts & Allowances</td>
                                <td style="text-align: right; color: #a94442;">-<?= to_currency($discounts) ?></td>
                            </tr>
                            <tr style="font-weight: bold;">
                                <td style="padding-left: 20px;">Net Sales Revenue</td>
                                <td style="text-align: right;"><?= to_currency($net_sales) ?></td>
                            </tr>

                            <!-- COGS -->
                            <tr class="active">
                                <td colspan="2"><strong>2. Cost of Goods Sold (COGS)</strong></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Direct Wholesale Merchandise Cost</td>
                                <td style="text-align: right; color: #a94442;">-<?= to_currency($cogs) ?></td>
                            </tr>
                            <tr style="font-weight: bold; background-color: #dff0d8;">
                                <td>Gross Operating Profit (Net Sales - COGS)</td>
                                <td style="text-align: right; color: #3c763d; font-size: 15px;"><?= to_currency($gross_profit) ?></td>
                            </tr>

                            <!-- OPERATING EXPENSES -->
                            <tr class="active">
                                <td colspan="2"><strong>3. Operating Expenses (Overhead)</strong></td>
                            </tr>
                            <?php if (empty($expense_rows)): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted" style="padding: 10px;">No expenses recorded in this date range.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($expense_rows as $exp): ?>
                                    <tr>
                                        <td style="padding-left: 30px;"><?= esc($exp['category_name'] ?: 'Uncategorized') ?></td>
                                        <td style="text-align: right; color: #a94442;">-<?= to_currency($exp['category_total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr style="font-weight: bold;">
                                <td style="padding-left: 20px;">Total Operating Expenses</td>
                                <td style="text-align: right; color: #a94442;">-<?= to_currency($total_expenses) ?></td>
                            </tr>

                            <!-- NET PROFIT -->
                            <tr style="font-weight: bold; background-color: <?= $net_operating_profit >= 0 ? '#d9edf7' : '#f2dede' ?>;">
                                <td style="font-size: 16px;">Net Operating Income / (Loss)</td>
                                <td style="text-align: right; font-size: 16px; color: <?= $net_operating_profit >= 0 ? '#31708f' : '#a94442' ?>;">
                                    <?= to_currency($net_operating_profit) ?>
                                </td>
                            </tr>
                            <tr class="text-muted" style="font-size: 12px;">
                                <td>Statutory Output VAT Collected (Non-Revenue Liability)</td>
                                <td style="text-align: right;"><?= to_currency($total_tax) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($active_tab === 'balance_sheet'): ?>
    <!-- Balance Sheet View -->
    <div id="toolbar" class="print_hide">
        <div class="pull-left form-inline">
            <?= form_open('accounting/balanceSheet', ['method' => 'get', 'class' => 'form-inline']) ?>
                <div class="form-group form-group-sm">
                    <label>As of Date:&nbsp;</label>
                    <input type="date" name="end_date" class="form-control input-sm" value="<?= esc($as_of_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span class="glyphicon glyphicon-search">&nbsp;</span>View
                </button>
            <?= form_close() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading text-center">
                    <h3 class="panel-title" style="font-size: 18px; font-weight: bold;">
                        Statement of Financial Position (Balance Sheet)
                    </h3>
                    <small class="text-muted">As of <?= esc($as_of_date) ?></small>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr style="background-color: #f5f5f5;">
                                <th>Account Title</th>
                                <th style="width: 25%; text-align: right;">Amount (<?= esc($config['currency_symbol']) ?>)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- ASSETS -->
                            <tr class="info">
                                <td colspan="2"><strong>ASSETS (What the Business Owns)</strong></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Cash on Hand (Till & Registers)</td>
                                <td style="text-align: right;"><?= to_currency($cash_on_hand) ?></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Bank & Mobile Money Accounts</td>
                                <td style="text-align: right;"><?= to_currency($total_bank_digital) ?></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Merchandise Inventory (at Cost Valuation)</td>
                                <td style="text-align: right;"><?= to_currency($inventory_valuation) ?></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Accounts Receivable (Unpaid Invoices Due)</td>
                                <td style="text-align: right;"><?= to_currency($accounts_receivable) ?></td>
                            </tr>
                            <tr style="font-weight: bold; background-color: #dff0d8;">
                                <td style="font-size: 15px;">TOTAL ASSETS</td>
                                <td style="text-align: right; font-size: 15px; color: #3c763d;"><?= to_currency($total_assets) ?></td>
                            </tr>

                            <!-- LIABILITIES -->
                            <tr class="warning">
                                <td colspan="2"><strong>LIABILITIES (What the Business Owes)</strong></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Sales Tax & VAT Payable to Authority</td>
                                <td style="text-align: right;"><?= to_currency($tax_payable) ?></td>
                            </tr>
                            <tr style="font-weight: bold;">
                                <td style="padding-left: 20px;">TOTAL LIABILITIES</td>
                                <td style="text-align: right;"><?= to_currency($total_liabilities) ?></td>
                            </tr>

                            <!-- EQUITY -->
                            <tr class="active">
                                <td colspan="2"><strong>EQUITY (Owner's Net Worth in Business)</strong></td>
                            </tr>
                            <tr>
                                <td style="padding-left: 30px;">Retained Earnings / Operating Capital</td>
                                <td style="text-align: right;"><?= to_currency($owner_equity) ?></td>
                            </tr>
                            <tr style="font-weight: bold; background-color: #d9edf7;">
                                <td style="font-size: 15px;">TOTAL LIABILITIES & EQUITY</td>
                                <td style="text-align: right; font-size: 15px; color: #31708f;"><?= to_currency($total_equity_liab) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($active_tab === 'bank_reconciliation'): ?>
    <!-- Bank & Electronic Reconciliation View -->
    <div id="toolbar" class="print_hide">
        <div class="pull-left form-inline">
            <?= form_open('accounting/bankReconciliation', ['method' => 'get', 'class' => 'form-inline']) ?>
                <div class="form-group form-group-sm">
                    <label>From:&nbsp;</label>
                    <input type="date" name="start_date" class="form-control input-sm" value="<?= esc($start_date) ?>">
                </div>
                <div class="form-group form-group-sm">
                    <label>&nbsp;To:&nbsp;</label>
                    <input type="date" name="end_date" class="form-control input-sm" value="<?= esc($end_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span class="glyphicon glyphicon-search">&nbsp;</span>Filter
                </button>
            <?= form_close() ?>
        </div>
    </div>

    <!-- KPI Summary Row using native OSPOS alert boxes -->
    <div class="row" style="margin-bottom: 15px;">
        <div class="col-md-4">
            <div class="alert alert-info" style="margin-bottom: 0;">
                <h4><?= to_currency($total_electronic) ?></h4>
                <small>Total Bank & Electronic Collections</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-success" style="margin-bottom: 0;">
                <h4><?= to_currency($total_with_ref) ?></h4>
                <small>Matched with Bank/M-Pesa Reference Codes</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert <?= $total_pending_ref > 0 ? 'alert-warning' : 'alert-default' ?>" style="margin-bottom: 0;">
                <h4><?= to_currency($total_pending_ref) ?></h4>
                <small>Pending Reference Code Entry</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr style="background-color: #f5f5f5;">
                        <th style="width: 10%;">Trans. ID</th>
                        <th style="width: 15%;">Date & Time</th>
                        <th style="width: 15%;">Invoice #</th>
                        <th style="width: 20%;">Customer</th>
                        <th style="width: 15%;">Payment Channel</th>
                        <th style="width: 12%; text-align: right;">Amount</th>
                        <th style="width: 13%;">Bank/Wallet Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding: 20px;">
                                No bank transfers, card, or wallet payments recorded in this period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= anchor("sales/receipt/{$t['sale_id']}", 'POS ' . $t['sale_id'], ['target' => '_blank']) ?></td>
                                <td><?= to_datetime(strtotime($t['sale_time'])) ?></td>
                                <td><?= esc($t['invoice_number'] ?: '-') ?></td>
                                <td><?= esc($t['customer_name'] ?: 'Walk-in Customer') ?></td>
                                <td><span class="label label-info"><?= esc($t['payment_type']) ?></span></td>
                                <td style="text-align: right; font-weight: bold;"><?= to_currency($t['payment_amount']) ?></td>
                                <td>
                                    <?php if (!empty($t['reference_code'])): ?>
                                        <span class="label label-success"><?= esc($t['reference_code']) ?></span>
                                    <?php else: ?>
                                        <span class="label label-warning">Pending Ref</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?= view('partial/footer') ?>
