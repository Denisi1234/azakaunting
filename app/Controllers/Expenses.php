<?php

namespace App\Controllers;

use App\Models\Expense;
use App\Models\Expense_category;
use CodeIgniter\HTTP\ResponseInterface;
use Config\OSPOS;

class Expenses extends Secure_Controller
{
    private Expense $expense;
    private Expense_category $expense_category;

    public function __construct()
    {
        parent::__construct('expenses');

        $this->expense          = model(Expense::class);
        $this->expense_category = model(Expense_category::class);
    }

    /**
     * @return void
     */
    public function getIndex(): string
    {
        $data['table_headers'] = get_expenses_manage_table_headers();

        // filters that will be loaded in the multiselect dropdown
        $data['filters'] = [
            'only_cash'   => lang('Expenses.cash_filter'),
            'only_due'    => lang('Expenses.due_filter'),
            'only_check'  => lang('Expenses.check_filter'),
            'only_credit' => lang('Expenses.credit_filter'),
            'only_debit'  => lang('Expenses.debit_filter'),
            'is_deleted'  => lang('Expenses.is_deleted'),
        ];

        // Restore filters from URL
        $data = array_merge($data, restoreTableFilters($this->request));

        return view('expenses/manage', $data);
    }

    /**
     * @return void
     */
    public function getSearch(): ResponseInterface
    {
        $search  = $this->request->getGet('search');
        $limit   = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset  = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort    = $this->sanitizeSortColumn(expense_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'expense_id');
        $order   = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $filters = [
            'start_date'  => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'    => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'only_cash'   => false,
            'only_due'    => false,
            'only_check'  => false,
            'only_credit' => false,
            'only_debit'  => false,
            'is_deleted'  => false,
        ];

        // Check if any filter is set in the multiselect dropdown
        $requestFilters = array_fill_keys($this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [], true);
        $filters        = array_merge($filters, $requestFilters);
        $expenses       = $this->expense->search($search, $filters, $limit, $offset, $sort, $order);
        $totalRows      = $this->expense->get_found_rows($search, $filters);
        $payments       = $this->expense->get_payments_summary($search, $filters);
        $paymentSummary = get_expenses_manage_payments_summary($payments, $expenses);
        $dataRows       = [];

        foreach ($expenses->getResult() as $expense) {
            $dataRows[] = get_expenses_data_row($expense);
        }

        if ($totalRows > 0) {
            $dataRows[] = get_expenses_data_last_row($expenses);
        }

        return $this->response->setJSON(['total' => $totalRows, 'rows' => $dataRows, 'payment_summary' => $paymentSummary]);
    }

    public function getView(int $expenseId = NEW_ENTRY): string
    {
        $data = [];

        $this->ensureDefaultCategories();

        $data['expenses_info'] = $this->expense->get_info($expenseId);
        $expenseId             = $data['expenses_info']->expense_id;

        $currentEmployeeId = $this->employee->get_logged_in_employee_info()->person_id;
        $canAssignEmployee = $this->employee->has_grant('employees', $currentEmployeeId);

        $data['employees'] = [];
        if ($canAssignEmployee) {
            foreach ($this->employee->get_all()->getResult() as $employee) {
                $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
            }
        } else {
            $storedEmployeeId                     = $expenseId === NEW_ENTRY ? $currentEmployeeId : $data['expenses_info']->employee_id;
            $storedEmployee                       = $this->employee->get_info($storedEmployeeId);
            $data['employees'][$storedEmployeeId] = $storedEmployee->first_name . ' ' . $storedEmployee->last_name;
        }
        $data['can_assign_employee'] = $canAssignEmployee;

        $expenseCategories = [];

        foreach ($this->expense_category->get_all(0, 0, true)->getResultArray() as $row) {
            $expenseCategories[$row['expense_category_id']] = $row['category_name'];
        }
        $data['expense_categories'] = $expenseCategories;

        if ($expenseId === NEW_ENTRY) {
            $data['expenses_info']->date        = date('Y-m-d H:i:s');
            $data['expenses_info']->employee_id = $currentEmployeeId;
        }

        $data['payments'] = [];

        foreach ($this->expense->get_expense_payment($expenseId)->getResult() as $payment) {
            foreach (get_object_vars($payment) as $property => $value) {
                $payment->{$property} = $value;
            }

            $data['payments'][] = $payment;
        }

        // Don't allow gift card to be a payment option in a sale transaction edit because it's a complex change
        $data['payment_options'] = $this->expense->get_payment_options();

        return view('expenses/form', $data);
    }

    public function getRow(int $rowId): ResponseInterface
    {
        $expenseInfo = $this->expense->get_info($rowId);
        $dataRow     = get_expenses_data_row($expenseInfo);

        return $this->response->setJSON($dataRow);
    }

    public function postSave(int $expenseId = NEW_ENTRY): ResponseInterface
    {
        $config  = config(OSPOS::class)->settings;
        $newDate = $this->request->getPost('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $dateFormatter = date_create_from_format($config['dateformat'] . ' ' . $config['timeformat'], $newDate);

        $currentEmployeeId   = $this->employee->get_logged_in_employee_info()->person_id;
        $submittedEmployeeId = $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT);

        if (! $this->employee->has_grant('employees', $currentEmployeeId)) {
            if ($expenseId === NEW_ENTRY) {
                $employeeId = $currentEmployeeId;
            } else {
                $existingExpense = $this->expense->get_info($expenseId);
                $employeeId      = $existingExpense->employee_id;
            }
        } else {
            $employeeId = $submittedEmployeeId;
        }

        $expenseCategoryId = (int) $this->request->getPost('expense_category_id', FILTER_SANITIZE_NUMBER_INT);
        $newCategoryName   = trim((string) $this->request->getPost('new_category_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($newCategoryName !== '') {
            $categoryData = [
                'category_name'        => $newCategoryName,
                'category_description' => '',
            ];
            if ($this->expense_category->save_value($categoryData, NEW_ENTRY)) {
                $expenseCategoryId = (int) $categoryData['expense_category_id'];
            }
        }

        $expenseData = [
            'date'                => $dateFormatter->format('Y-m-d H:i:s'),
            'supplier_id'         => $this->request->getPost('supplier_id') === '' ? null : $this->request->getPost('supplier_id', FILTER_SANITIZE_NUMBER_INT),
            'supplier_tax_code'   => $this->request->getPost('supplier_tax_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'amount'              => parse_decimals($this->request->getPost('amount')),
            'tax_amount'          => parse_decimals($this->request->getPost('tax_amount')),
            'payment_type'        => $this->request->getPost('payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'expense_category_id' => $expenseCategoryId,
            'description'         => $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'employee_id'         => $employeeId,
            'deleted'             => $this->request->getPost('deleted') !== null,
        ];

        if ($this->expense->save_value($expenseData, $expenseId)) {
            if ($expenseId === NEW_ENTRY) {
                return $this->response->setJSON(['success' => true, 'message' => lang('Expenses.successful_adding'), 'id' => $expenseData['expense_id']]);
            }

            return $this->response->setJSON(['success' => true, 'message' => lang('Expenses.successful_updating'), 'id' => $expenseId]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Expenses.error_adding_updating'), 'id' => NEW_ENTRY]);
    }

    public function postDelete(): ResponseInterface
    {
        $expensesToDelete = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($this->expense->delete_list($expensesToDelete)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Expenses.successful_deleted') . ' ' . count($expensesToDelete) . ' ' . lang('Expenses.one_or_multiple'), 'ids' => $expensesToDelete]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Expenses.cannot_be_deleted'), 'ids' => $expensesToDelete]);
    }

    private function ensureDefaultCategories(): void
    {
        if ($this->expense_category->get_total_rows() > 0) {
            return;
        }

        $defaults = [
            ['category_name' => 'General', 'category_description' => 'General business expenses'],
            ['category_name' => 'Rent & Lease', 'category_description' => 'Store and warehouse rent'],
            ['category_name' => 'Utilities', 'category_description' => 'Electricity, water, internet, and phone'],
            ['category_name' => 'Salaries & Wages', 'category_description' => 'Staff payroll and wages'],
            ['category_name' => 'Office & Store Supplies', 'category_description' => 'Packaging, stationery, and supplies'],
            ['category_name' => 'Transport & Delivery', 'category_description' => 'Fuel, shipping, and logistics'],
            ['category_name' => 'Maintenance & Repairs', 'category_description' => 'Equipment and facility maintenance'],
        ];

        foreach ($defaults as $categoryData) {
            $this->expense_category->save_value($categoryData, NEW_ENTRY);
        }
    }
}
