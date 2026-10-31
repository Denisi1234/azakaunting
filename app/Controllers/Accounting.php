<?php

namespace App\Controllers;

use App\Models\Expense;
use App\Models\Item;
use App\Models\Receiving;
use App\Models\Sale;
use Config\Database;
use Config\OSPOS;

class Accounting extends Secure_Controller
{
    private array $config;
    private Sale $sale;
    private Expense $expense;
    private Item $item;
    private Receiving $receiving;

    public function __construct()
    {
        parent::__construct('reports');

        $this->sale      = model(Sale::class);
        $this->expense   = model(Expense::class);
        $this->item      = model(Item::class);
        $this->receiving = model(Receiving::class);
        $this->config    = config(OSPOS::class)->settings;
    }

    public function getIndex(): string
    {
        return $this->getProfitLoss();
    }

    /**
     * Profit & Loss Financial Statement
     */
    public function getProfitLoss(): string
    {
        $startDate = $this->request->getGet('start_date') ?: date('Y-m-01');
        $endDate   = $this->request->getGet('end_date') ?: date('Y-m-d');

        $db     = Database::connect();
        $prefix = $db->getPrefix();

        // 1. Sales Revenue & Cost of Goods Sold
        $salesRow = $db->query("
            SELECT
                COUNT(DISTINCT s.sale_id) AS total_sales_count,
                IFNULL(SUM(si.quantity_purchased * si.item_unit_price), 0) AS gross_sales,
                IFNULL(SUM(CASE
                    WHEN si.discount_type = 1 THEN (si.quantity_purchased * si.item_unit_price * si.discount / 100)
                    ELSE (si.quantity_purchased * si.discount)
                END), 0) AS total_discount,
                IFNULL(SUM(si.quantity_purchased * si.item_cost_price), 0) AS cogs
            FROM {$prefix}sales s
            JOIN {$prefix}sales_items si ON s.sale_id = si.sale_id
            WHERE s.sale_status = 0
              AND DATE(s.sale_time) BETWEEN ? AND ?
        ", [$startDate, $endDate])->getRowArray();

        $grossSales  = (float) ($salesRow['gross_sales'] ?? 0);
        $discounts   = (float) ($salesRow['total_discount'] ?? 0);
        $netSales    = $grossSales - $discounts;
        $cogs        = (float) ($salesRow['cogs'] ?? 0);
        $grossProfit = $netSales - $cogs;

        // 2. Taxes collected
        $taxRow = $db->query("
            SELECT IFNULL(SUM(st.sale_tax_amount), 0) AS total_tax
            FROM {$prefix}sales s
            JOIN {$prefix}sales_taxes st ON s.sale_id = st.sale_id
            WHERE s.sale_status = 0
              AND DATE(s.sale_time) BETWEEN ? AND ?
        ", [$startDate, $endDate])->getRowArray();
        $totalTax = (float) ($taxRow['total_tax'] ?? 0);

        // 3. Operating Expenses by Category
        $expenseRows = $db->query("
            SELECT
                ec.category_name,
                IFNULL(SUM(e.amount), 0) AS category_total
            FROM {$prefix}expenses e
            LEFT JOIN {$prefix}expense_categories ec ON e.expense_category_id = ec.expense_category_id
            WHERE e.deleted = 0
              AND DATE(e.date) BETWEEN ? AND ?
            GROUP BY ec.category_name
            ORDER BY category_total DESC
        ", [$startDate, $endDate])->getResultArray();

        $totalExpenses = 0.0;

        foreach ($expenseRows as $exp) {
            $totalExpenses += (float) $exp['category_total'];
        }

        $netOperatingProfit = $grossProfit - $totalExpenses;

        $data = [
            'active_tab'           => 'profit_loss',
            'start_date'           => $startDate,
            'end_date'             => $endDate,
            'gross_sales'          => $grossSales,
            'discounts'            => $discounts,
            'net_sales'            => $netSales,
            'cogs'                 => $cogs,
            'gross_profit'         => $grossProfit,
            'total_tax'            => $totalTax,
            'expense_rows'         => $expenseRows,
            'total_expenses'       => $totalExpenses,
            'net_operating_profit' => $netOperatingProfit,
            'config'               => $this->config,
        ];

        return view('accounting/manage', $data);
    }

    /**
     * Balance Sheet Summary
     */
    public function getBalanceSheet(): string
    {
        $asOfDate = $this->request->getGet('end_date') ?: date('Y-m-d');

        $db     = Database::connect();
        $prefix = $db->getPrefix();

        // 1. Current Assets: Cash on Hand from Drawer & Completed Payments
        $cashRow = $db->query("
            SELECT
                IFNULL(SUM(payment_amount - cash_refund), 0) AS total_cash
            FROM {$prefix}sales_payments
            WHERE payment_type = 'Cash'
        ")->getRowArray();
        $cashOnHand = (float) ($cashRow['total_cash'] ?? 0);

        // Current Assets: Bank & Digital Wallet Collections
        $bankRow = $db->query("
            SELECT
                payment_type,
                IFNULL(SUM(payment_amount), 0) AS amount
            FROM {$prefix}sales_payments
            WHERE payment_type IN ('Bank Transfer', 'Debit Card', 'Credit Card', 'Wallet')
            GROUP BY payment_type
        ")->getResultArray();
        $totalBankDigital = 0.0;

        foreach ($bankRow as $b) {
            $totalBankDigital += (float) $b['amount'];
        }

        // Current Assets: Inventory at Cost Valuation
        $invRow = $db->query("
            SELECT
                IFNULL(SUM(iq.quantity * i.cost_price), 0) AS total_inventory_cost
            FROM {$prefix}item_quantities iq
            JOIN {$prefix}items i ON iq.item_id = i.item_id
            WHERE i.deleted = 0
        ")->getRowArray();
        $totalInventoryCost = (float) ($invRow['total_inventory_cost'] ?? 0);

        // Current Assets: Accounts Receivable (Unpaid Invoices / Due)
        $arRow = $db->query("
            SELECT
                IFNULL(SUM(sp.payment_amount), 0) AS total_due
            FROM {$prefix}sales_payments sp
            JOIN {$prefix}sales s ON sp.sale_id = s.sale_id
            WHERE sp.payment_type = 'Due'
        ")->getRowArray();
        $accountsReceivable = (float) ($arRow['total_due'] ?? 0);

        $totalAssets = $cashOnHand + $totalBankDigital + $totalInventoryCost + $accountsReceivable;

        // 2. Liabilities: Output Tax Payable
        $taxRow = $db->query("
            SELECT IFNULL(SUM(sale_tax_amount), 0) AS tax_payable
            FROM {$prefix}sales_taxes
        ")->getRowArray();
        $taxPayable = (float) ($taxRow['tax_payable'] ?? 0);

        // 3. Equity: Total Retained Earnings (All time Net Profit)
        $allSalesRow = $db->query("
            SELECT
                IFNULL(SUM(si.quantity_purchased * (si.item_unit_price - si.item_cost_price)), 0) AS gross_profit
            FROM {$prefix}sales s
            JOIN {$prefix}sales_items si ON s.sale_id = si.sale_id
            WHERE s.sale_status = 0
        ")->getRowArray();
        $allTimeGrossProfit = (float) ($allSalesRow['gross_profit'] ?? 0);

        $allExpRow = $db->query("
            SELECT IFNULL(SUM(amount), 0) AS total_exp
            FROM {$prefix}expenses
            WHERE deleted = 0
        ")->getRowArray();
        $allTimeExpenses = (float) ($allExpRow['total_exp'] ?? 0);

        $retainedEarnings = $allTimeGrossProfit - $allTimeExpenses;
        $ownerEquity      = $totalAssets - $taxPayable;

        $data = [
            'active_tab'          => 'balance_sheet',
            'as_of_date'          => $asOfDate,
            'cash_on_hand'        => $cashOnHand,
            'bank_rows'           => $bankRow,
            'total_bank_digital'  => $totalBankDigital,
            'inventory_valuation' => $totalInventoryCost,
            'accounts_receivable' => $accountsReceivable,
            'total_assets'        => $totalAssets,
            'tax_payable'         => $taxPayable,
            'total_liabilities'   => $taxPayable,
            'retained_earnings'   => $retainedEarnings,
            'owner_equity'        => $ownerEquity,
            'total_equity_liab'   => $totalAssets,
            'config'              => $this->config,
        ];

        return view('accounting/manage', $data);
    }

    /**
     * Bank Reconciliation (Electronic Payments vs References)
     */
    public function getBankReconciliation(): string
    {
        $startDate = $this->request->getGet('start_date') ?: date('Y-m-01');
        $endDate   = $this->request->getGet('end_date') ?: date('Y-m-d');

        $db     = Database::connect();
        $prefix = $db->getPrefix();

        $rows = $db->query("
            SELECT
                s.sale_id,
                s.sale_time,
                s.invoice_number,
                CONCAT(p.first_name, ' ', p.last_name) AS customer_name,
                sp.payment_type,
                sp.payment_amount,
                sp.reference_code
            FROM {$prefix}sales_payments sp
            JOIN {$prefix}sales s ON sp.sale_id = s.sale_id
            LEFT JOIN {$prefix}people p ON s.customer_id = p.person_id
            WHERE sp.payment_type IN ('Bank Transfer', 'Debit Card', 'Credit Card', 'Wallet')
              AND DATE(s.sale_time) BETWEEN ? AND ?
            ORDER BY s.sale_time DESC
        ", [$startDate, $endDate])->getResultArray();

        $totalElectronic = 0.0;
        $totalWithRef    = 0.0;
        $totalPendingRef = 0.0;

        foreach ($rows as $r) {
            $amt = (float) $r['payment_amount'];
            $totalElectronic += $amt;
            if (! empty($r['reference_code'])) {
                $totalWithRef += $amt;
            } else {
                $totalPendingRef += $amt;
            }
        }

        $data = [
            'active_tab'        => 'bank_reconciliation',
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'transactions'      => $rows,
            'total_electronic'  => $totalElectronic,
            'total_with_ref'    => $totalWithRef,
            'total_pending_ref' => $totalPendingRef,
            'config'            => $this->config,
        ];

        return view('accounting/manage', $data);
    }
}
