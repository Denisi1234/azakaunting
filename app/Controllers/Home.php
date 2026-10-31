<?php

namespace App\Controllers;

use App\Models\Employee;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class Home extends Secure_Controller
{
    public function __construct()
    {
        $methodName = urldecode(service('request')->getUri()->getSegment(2));

        if ($methodName === 'logout') {
            $this->employee = model(Employee::class);

            return;
        }

        parent::__construct('home', null, 'home');
    }

    public function getIndex(): string
    {
        $this->employee->is_logged_in();
        $data = $this->getDashboardMetrics();

        return view('home/home', $data);
    }

    private function getDashboardMetrics(): array
    {
        $db     = Database::connect();
        $prefix = $db->getPrefix();

        $currentMonthStart = date('Y-m-01 00:00:00');
        $currentMonthEnd   = date('Y-m-t 23:59:59');
        $lastMonthStart    = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $lastMonthEnd      = date('Y-m-t 23:59:59', strtotime('last day of last month'));

        $totalSales     = $this->querySalesTotal($db, $prefix);
        $salesThisMonth = $this->querySalesTotal($db, $prefix, $currentMonthStart, $currentMonthEnd);
        $salesLastMonth = $this->querySalesTotal($db, $prefix, $lastMonthStart, $lastMonthEnd);

        $totalExpenses     = $this->queryExpensesTotal($db, $prefix);
        $expensesThisMonth = $this->queryExpensesTotal($db, $prefix, $currentMonthStart, $currentMonthEnd);
        $expensesLastMonth = $this->queryExpensesTotal($db, $prefix, $lastMonthStart, $lastMonthEnd);

        $totalReceivings     = $this->queryReceivingsTotal($db, $prefix);
        $receivingsThisMonth = $this->queryReceivingsTotal($db, $prefix, $currentMonthStart, $currentMonthEnd);
        $receivingsLastMonth = $this->queryReceivingsTotal($db, $prefix, $lastMonthStart, $lastMonthEnd);

        $paymentSent          = $totalReceivings + $totalExpenses;
        $paymentSentThisMonth = $receivingsThisMonth + $expensesThisMonth;
        $paymentSentLastMonth = $receivingsLastMonth + $expensesLastMonth;

        $paymentReceived          = $this->queryPaymentsReceivedTotal($db, $prefix);
        $paymentReceivedThisMonth = $this->queryPaymentsReceivedTotal($db, $prefix, $currentMonthStart, $currentMonthEnd);
        $paymentReceivedLastMonth = $this->queryPaymentsReceivedTotal($db, $prefix, $lastMonthStart, $lastMonthEnd);

        $todayStart      = date('Y-m-d 00:00:00');
        $todayEnd        = date('Y-m-d 23:59:59');
        $todaySales      = $this->querySalesTotal($db, $prefix, $todayStart, $todayEnd);
        $todaySalesCount = $this->querySalesCount($db, $prefix, $todayStart, $todayEnd);
        $totalSalesCount = $this->querySalesCount($db, $prefix);
        $totalCogs       = $this->queryCogsTotal($db, $prefix);
        $grossProfit     = $totalSales - $totalCogs - $totalExpenses;

        $dailySeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $dayTimestamp = strtotime("-{$i} days");
            $dayStart     = date('Y-m-d 00:00:00', $dayTimestamp);
            $dayEnd       = date('Y-m-d 23:59:59', $dayTimestamp);
            $daySales     = $this->querySalesTotal($db, $prefix, $dayStart, $dayEnd);
            $dayPurchases = $this->queryReceivingsTotal($db, $prefix, $dayStart, $dayEnd)
                + $this->queryExpensesTotal($db, $prefix, $dayStart, $dayEnd);
            $dailySeries[] = [
                'label'     => date('D d', $dayTimestamp),
                'sales'     => round($daySales, 2),
                'purchases' => round($dayPurchases, 2),
                'target'    => round(max($daySales, $dayPurchases) * 1.2, 2),
            ];
        }

        $monthlySeries = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthTimestamp = strtotime("-{$i} months", strtotime(date('Y-m-01')));
            $monthStart     = date('Y-m-01 00:00:00', $monthTimestamp);
            $monthEnd       = date('Y-m-t 23:59:59', $monthTimestamp);
            $monthSales     = $this->querySalesTotal($db, $prefix, $monthStart, $monthEnd);
            $monthPurchases = $this->queryReceivingsTotal($db, $prefix, $monthStart, $monthEnd)
                + $this->queryExpensesTotal($db, $prefix, $monthStart, $monthEnd);

            $monthlySeries[] = [
                'label'     => date('M', $monthTimestamp),
                'sales'     => round($monthSales, 2),
                'purchases' => round($monthPurchases, 2),
                'target'    => round(max($monthSales, $monthPurchases) * 1.2, 2),
            ];
        }

        $categoryBreakdown = $this->queryCategoryBreakdown($db, $prefix);
        $paymentBreakdown  = $this->queryPaymentBreakdown($db, $prefix);
        $recentInvoices    = $this->queryRecentInvoices($db, $prefix);
        $stockMetrics      = $this->queryStockMetrics($db, $prefix);
        $lowStockItems     = $this->queryLowStockItems($db, $prefix);

        return [
            'dashboard' => [
                'today_sales'             => $todaySales,
                'today_sales_count'       => $todaySalesCount,
                'total_sales'             => $totalSales,
                'total_sales_count'       => $totalSalesCount,
                'sales_this_month'        => $salesThisMonth,
                'sales_growth'            => $this->calculateGrowthPercent($salesThisMonth, $salesLastMonth),
                'gross_profit'            => $grossProfit,
                'total_expenses'          => $totalExpenses,
                'expenses_growth'         => $this->calculateGrowthPercent($expensesThisMonth, $expensesLastMonth),
                'total_receivings'        => $totalReceivings,
                'payment_sent'            => $paymentSent,
                'payment_sent_growth'     => $this->calculateGrowthPercent($paymentSentThisMonth, $paymentSentLastMonth),
                'payment_received'        => $paymentReceived,
                'payment_received_growth' => $this->calculateGrowthPercent($paymentReceivedThisMonth, $paymentReceivedLastMonth),
                'daily_series'            => $dailySeries,
                'monthly_series'          => $monthlySeries,
                'category_breakdown'      => $categoryBreakdown,
                'payment_breakdown'       => $paymentBreakdown,
                'recent_invoices'         => $recentInvoices,
                'stock_metrics'           => $stockMetrics,
                'low_stock_items'         => $lowStockItems,
            ],
        ];
    }

    private function querySalesCount(BaseConnection $db, string $prefix, ?string $start = null, ?string $end = null): int
    {
        $sql   = "SELECT COUNT(*) AS cnt FROM {$prefix}sales WHERE sale_status = 0";
        $binds = [];

        if ($start !== null && $end !== null) {
            $sql .= ' AND sale_time BETWEEN ? AND ?';
            $binds = [$start, $end];
        }

        $row = $db->query($sql, $binds)->getRowArray();

        return (int) ($row['cnt'] ?? 0);
    }

    private function queryCogsTotal(BaseConnection $db, string $prefix): float
    {
        $sql = "SELECT COALESCE(SUM(si.quantity_purchased * si.item_cost_price), 0) AS total
                FROM {$prefix}sales_items si
                INNER JOIN {$prefix}sales s ON s.sale_id = si.sale_id
                WHERE s.sale_status = 0";

        $row = $db->query($sql)->getRowArray();

        return (float) ($row['total'] ?? 0.0);
    }

    private function queryPaymentBreakdown(BaseConnection $db, string $prefix): array
    {
        $sql = "SELECT sp.payment_type AS label,
                       COUNT(*) AS count,
                       COALESCE(SUM(sp.payment_amount - sp.cash_refund), 0) AS total
                FROM {$prefix}sales_payments sp
                INNER JOIN {$prefix}sales s ON s.sale_id = sp.sale_id
                WHERE s.sale_status = 0
                GROUP BY sp.payment_type
                HAVING total > 0
                ORDER BY total DESC";

        $rows   = $db->query($sql)->getResultArray();
        $sum    = array_sum(array_map(static fn (array $row): float => (float) $row['total'], $rows));
        $colors = ['#2C3E50', '#18BC9C', '#3498DB', '#F39C12', '#E74C3C'];
        $result = [];

        foreach ($rows as $index => $row) {
            $value    = (float) $row['total'];
            $percent  = $sum > 0 ? round(($value / $sum) * 100, 1) : 0.0;
            $result[] = [
                'label'   => (string) $row['label'],
                'count'   => (int) $row['count'],
                'value'   => $value,
                'percent' => $percent,
                'color'   => $colors[$index % count($colors)],
            ];
        }

        return $result;
    }

    private function queryLowStockItems(BaseConnection $db, string $prefix): array
    {
        $sql = "SELECT i.item_id,
                       i.name,
                       i.category,
                       i.reorder_level,
                       COALESCE(SUM(iq.quantity), 0) AS current_qty
                FROM {$prefix}items i
                LEFT JOIN {$prefix}item_quantities iq ON iq.item_id = i.item_id
                WHERE i.deleted = 0 AND i.stock_type = 0
                GROUP BY i.item_id, i.name, i.category, i.reorder_level
                HAVING current_qty <= i.reorder_level
                ORDER BY current_qty ASC
                LIMIT 6";

        return $db->query($sql)->getResultArray();
    }

    private function querySalesTotal(BaseConnection $db, string $prefix, ?string $start = null, ?string $end = null): float
    {
        $sql = "SELECT COALESCE(SUM(
                    CASE
                        WHEN si.discount_type = 0 THEN si.quantity_purchased * si.item_unit_price * (1 - si.discount / 100)
                        ELSE si.quantity_purchased * si.item_unit_price - si.discount
                    END
                ), 0) AS total
                FROM {$prefix}sales_items si
                INNER JOIN {$prefix}sales s ON s.sale_id = si.sale_id
                WHERE s.sale_status = 0";

        $binds = [];
        if ($start !== null && $end !== null) {
            $sql .= ' AND s.sale_time BETWEEN ? AND ?';
            $binds = [$start, $end];
        }

        $row = $db->query($sql, $binds)->getRowArray();

        return (float) ($row['total'] ?? 0.0);
    }

    private function queryExpensesTotal(BaseConnection $db, string $prefix, ?string $start = null, ?string $end = null): float
    {
        $sql = "SELECT COALESCE(SUM(amount + COALESCE(tax_amount, 0)), 0) AS total
                FROM {$prefix}expenses
                WHERE deleted = 0";

        $binds = [];
        if ($start !== null && $end !== null) {
            $sql .= ' AND date BETWEEN ? AND ?';
            $binds = [$start, $end];
        }

        $row = $db->query($sql, $binds)->getRowArray();

        return (float) ($row['total'] ?? 0.0);
    }

    private function queryReceivingsTotal(BaseConnection $db, string $prefix, ?string $start = null, ?string $end = null): float
    {
        $sql = "SELECT COALESCE(SUM(
                    CASE
                        WHEN ri.discount_type = 0 THEN ri.quantity_purchased * ri.receiving_quantity * ri.item_unit_price * (1 - ri.discount / 100)
                        ELSE ri.quantity_purchased * ri.receiving_quantity * ri.item_unit_price - ri.discount
                    END
                ), 0) AS total
                FROM {$prefix}receivings_items ri
                INNER JOIN {$prefix}receivings r ON r.receiving_id = ri.receiving_id
                WHERE ri.quantity_purchased > 0";

        $binds = [];
        if ($start !== null && $end !== null) {
            $sql .= ' AND r.receiving_time BETWEEN ? AND ?';
            $binds = [$start, $end];
        }

        $row = $db->query($sql, $binds)->getRowArray();

        return (float) ($row['total'] ?? 0.0);
    }

    private function queryPaymentsReceivedTotal(BaseConnection $db, string $prefix, ?string $start = null, ?string $end = null): float
    {
        $sql = "SELECT COALESCE(SUM(sp.payment_amount - sp.cash_refund), 0) AS total
                FROM {$prefix}sales_payments sp
                INNER JOIN {$prefix}sales s ON s.sale_id = sp.sale_id
                WHERE s.sale_status = 0";

        $binds = [];
        if ($start !== null && $end !== null) {
            $sql .= ' AND sp.payment_time BETWEEN ? AND ?';
            $binds = [$start, $end];
        }

        $row = $db->query($sql, $binds)->getRowArray();

        return (float) ($row['total'] ?? 0.0);
    }

    private function queryCategoryBreakdown(BaseConnection $db, string $prefix): array
    {
        $sql = "SELECT COALESCE(NULLIF(TRIM(i.category), ''), 'General') AS label,
                       COALESCE(SUM(
                           CASE
                               WHEN si.discount_type = 0 THEN si.quantity_purchased * si.item_unit_price * (1 - si.discount / 100)
                               ELSE si.quantity_purchased * si.item_unit_price - si.discount
                           END
                       ), 0) AS total
                FROM {$prefix}sales_items si
                INNER JOIN {$prefix}sales s ON s.sale_id = si.sale_id
                INNER JOIN {$prefix}items i ON i.item_id = si.item_id
                WHERE s.sale_status = 0 AND si.quantity_purchased > 0
                GROUP BY label
                HAVING total > 0
                ORDER BY total DESC
                LIMIT 5";

        $rows = $db->query($sql)->getResultArray();

        if ($rows === []) {
            $fallbackSql = "SELECT COALESCE(NULLIF(TRIM(category), ''), 'General') AS label,
                                   COUNT(*) AS total
                            FROM {$prefix}items
                            WHERE deleted = 0
                            GROUP BY label
                            ORDER BY total DESC
                            LIMIT 5";
            $rows = $db->query($fallbackSql)->getResultArray();
        }

        $sum    = array_sum(array_map(static fn (array $row): float => (float) $row['total'], $rows));
        $colors = ['#ff6b6b', '#a78bfa', '#60a5fa', '#38bdf8', '#a3e635'];
        $result = [];

        foreach ($rows as $index => $row) {
            $value    = (float) $row['total'];
            $percent  = $sum > 0 ? round(($value / $sum) * 100, 1) : 0.0;
            $result[] = [
                'label'   => (string) $row['label'],
                'value'   => $value,
                'percent' => $percent,
                'color'   => $colors[$index % count($colors)],
            ];
        }

        return $result;
    }

    private function queryRecentInvoices(BaseConnection $db, string $prefix): array
    {
        $sql = "SELECT s.sale_id,
                       s.invoice_number,
                       s.sale_time,
                       s.sale_status,
                       TRIM(CONCAT(COALESCE(p.first_name, ''), ' ', COALESCE(p.last_name, ''))) AS customer_name,
                       COALESCE((
                           SELECT SUM(sp.payment_amount - sp.cash_refund)
                           FROM {$prefix}sales_payments sp
                           WHERE sp.sale_id = s.sale_id
                       ), 0) AS paid_amount,
                       COALESCE((
                           SELECT SUM(
                               CASE
                                   WHEN si.discount_type = 0 THEN si.quantity_purchased * si.item_unit_price * (1 - si.discount / 100)
                                   ELSE si.quantity_purchased * si.item_unit_price - si.discount
                               END
                           )
                           FROM {$prefix}sales_items si
                           WHERE si.sale_id = s.sale_id
                       ), 0) AS sale_total
                FROM {$prefix}sales s
                LEFT JOIN {$prefix}people p ON p.person_id = s.customer_id
                ORDER BY s.sale_time DESC
                LIMIT 8";

        return $db->query($sql)->getResultArray();
    }

    private function queryStockMetrics(BaseConnection $db, string $prefix): array
    {
        $soldRow = $db->query(
            "SELECT COALESCE(SUM(si.quantity_purchased), 0) AS qty
             FROM {$prefix}sales_items si
             INNER JOIN {$prefix}sales s ON s.sale_id = si.sale_id
             WHERE s.sale_status = 0 AND si.quantity_purchased > 0",
        )->getRowArray();

        $returnedRow = $db->query(
            "SELECT COALESCE(SUM(ABS(si.quantity_purchased)), 0) AS qty
             FROM {$prefix}sales_items si
             INNER JOIN {$prefix}sales s ON s.sale_id = si.sale_id
             WHERE s.sale_status = 0 AND si.quantity_purchased < 0",
        )->getRowArray();

        $purchasedRow = $db->query(
            "SELECT COALESCE(SUM(ri.quantity_purchased * ri.receiving_quantity), 0) AS qty
             FROM {$prefix}receivings_items ri
             WHERE ri.quantity_purchased > 0",
        )->getRowArray();

        $stockRow = $db->query(
            "SELECT COALESCE(SUM(iq.quantity), 0) AS qty
             FROM {$prefix}item_quantities iq
             INNER JOIN {$prefix}items i ON i.item_id = iq.item_id
             WHERE i.deleted = 0",
        )->getRowArray();

        $itemsCountRow = $db->query(
            "SELECT COUNT(*) AS cnt FROM {$prefix}items WHERE deleted = 0",
        )->getRowArray();

        $customersCountRow = $db->query(
            "SELECT COUNT(*) AS cnt FROM {$prefix}customers WHERE deleted = 0",
        )->getRowArray();

        $suppliersCountRow = $db->query(
            "SELECT COUNT(*) AS cnt FROM {$prefix}suppliers WHERE deleted = 0",
        )->getRowArray();

        $lowStockRow = $db->query(
            "SELECT COUNT(*) AS cnt FROM (
                SELECT i.item_id
                FROM {$prefix}items i
                LEFT JOIN {$prefix}item_quantities iq ON iq.item_id = i.item_id
                WHERE i.deleted = 0 AND i.stock_type = 0
                GROUP BY i.item_id, i.reorder_level
                HAVING COALESCE(SUM(iq.quantity), 0) <= i.reorder_level
            ) AS low_stock",
        )->getRowArray();

        return [
            'sales_items'      => (float) ($soldRow['qty'] ?? 0),
            'return_items'     => (float) ($returnedRow['qty'] ?? 0),
            'purchase_items'   => (float) ($purchasedRow['qty'] ?? 0),
            'stock_on_hand'    => (float) ($stockRow['qty'] ?? 0),
            'active_items'     => (int) ($itemsCountRow['cnt'] ?? 0),
            'active_customers' => (int) ($customersCountRow['cnt'] ?? 0),
            'active_suppliers' => (int) ($suppliersCountRow['cnt'] ?? 0),
            'low_stock_count'  => (int) ($lowStockRow['cnt'] ?? 0),
        ];
    }

    private function calculateGrowthPercent(float $current, float $previous): float
    {
        if ($previous <= 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Logs the currently logged in employee out of the system.  Used in app/Views/partial/header.php
     *
     * @noinspection PhpUnused
     */
    public function getLogout(): RedirectResponse
    {
        $this->employee->logout();

        return redirect()->to('login');
    }

    /**
     * Load the "change employee password" form
     */
    public function getChangePassword(int $employeeId = NEW_ENTRY): ResponseInterface|string
    {
        $loggedInEmployee = $this->employee->get_logged_in_employee_info();
        $currentPersonId  = (int) $loggedInEmployee->person_id;

        $employeeId = $employeeId === NEW_ENTRY ? $currentPersonId : $employeeId;

        if (! $this->employee->isAdmin($currentPersonId) && $employeeId !== $currentPersonId) {
            return $this->response->setStatusCode(403)->setBody(lang('Employees.unauthorized_modify'));
        }

        $personInfo = $this->employee->get_info($employeeId);

        foreach (get_object_vars($personInfo) as $property => $value) {
            $personInfo->{$property} = $value;
        }
        $data['person_info'] = $personInfo;

        return view('home/form_change_password', $data);
    }

    /**
     * Change employee password
     */
    public function postSave(int $employeeId = NEW_ENTRY): ResponseInterface
    {
        $currentUser     = $this->employee->get_logged_in_employee_info();
        $currentPersonId = (int) $currentUser->person_id;

        $employeeId = $employeeId === NEW_ENTRY ? $currentPersonId : $employeeId;

        if (! $this->employee->isAdmin($currentPersonId) && $employeeId !== $currentPersonId) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => lang('Employees.unauthorized_modify'),
            ]);
        }

        if (! empty($this->request->getPost('current_password')) && $employeeId !== NEW_ENTRY) {
            if ($this->employee->check_password($this->request->getPost('username', FILTER_SANITIZE_FULL_SPECIAL_CHARS), $this->request->getPost('current_password'))) {
                // Validate password length BEFORE hashing
                $newPassword = $this->request->getPost('password');

                if (strlen($newPassword) < 8) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => lang('Employees.password_minlength'),
                        'id'      => NEW_ENTRY,
                    ]);
                }

                $employeeData = [
                    'username'     => $this->request->getPost('username', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
                    'password'     => password_hash($newPassword, PASSWORD_DEFAULT),
                    'hash_version' => 2,
                ];

                if ($this->employee->change_password($employeeData, $employeeId)) {
                    return $this->response->setJSON([
                        'success' => true,
                        'message' => lang('Employees.successful_change_password'),
                        'id'      => $employeeId,
                    ]);
                }

                return $this->response->setJSON([
                    'success' => false,
                    'message' => lang('Employees.unsuccessful_change_password'),
                    'id'      => NEW_ENTRY,
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Employees.current_password_invalid'),
                'id'      => NEW_ENTRY,
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => lang('Employees.current_password_invalid'),
            'id'      => NEW_ENTRY,
        ]);
    }
}
