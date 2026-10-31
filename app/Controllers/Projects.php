<?php

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Project;
use CodeIgniter\HTTP\ResponseInterface;

class Projects extends Secure_Controller
{
    private Project $project;
    private Customer $customer;
    protected Employee $employee;

    public function __construct()
    {
        parent::__construct('projects');

        $this->project  = model(Project::class);
        $this->customer = model(Customer::class);
        $this->employee = model(Employee::class);
    }

    /**
     * @return void
     */
    public function getIndex(): string
    {
        $data['table_headers'] = get_projects_manage_table_headers();

        $data['filters'] = [
            'is_deleted' => lang('Projects.is_deleted'),
            'status'     => lang('Projects.status_filter'),
        ];

        $data = array_merge($data, restoreTableFilters($this->request));

        return view('projects/manage', $data);
    }

    public function getSearch(): ResponseInterface
    {
        $search  = $this->request->getGet('search');
        $limit   = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset  = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort    = $this->sanitizeSortColumn(project_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'project_id');
        $order   = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $filters = [
            'start_date'  => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'    => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'is_deleted'  => false,
            'status'      => $this->request->getGet('status', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'customer_id' => $this->request->getGet('customer_id', FILTER_SANITIZE_NUMBER_INT),
            'employee_id' => $this->request->getGet('employee_id', FILTER_SANITIZE_NUMBER_INT),
        ];

        $requestFilters = array_fill_keys($this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [], true);
        $filters        = array_merge($filters, $requestFilters);

        $projects  = $this->project->search($search, $filters, $limit, $offset, $sort, $order);
        $totalRows = $this->project->get_found_rows($search, $filters);
        $dataRows  = [];

        foreach ($projects->getResult() as $project) {
            $dataRows[] = get_projects_data_row($project);
        }

        if ($totalRows > 0) {
            $dataRows[] = get_projects_data_last_row($projects);
        }

        return $this->response->setJSON(['total' => $totalRows, 'rows' => $dataRows]);
    }

    public function getView(int $projectId = NEW_ENTRY): string
    {
        $data = [];

        $data['projects_info'] = $this->project->get_info($projectId);
        $projectId             = $data['projects_info']->project_id;

        $currentEmployeeId = $this->employee->get_logged_in_employee_info()->person_id;

        // Customers
        $data['customers'] = [];

        foreach ($this->customer->get_all(0, 0)->getResult() as $customer) {
            $label = $customer->first_name . ' ' . $customer->last_name;
            if (! empty($customer->company_name)) {
                $label .= ' [' . $customer->company_name . ']';
            }
            $data['customers'][$customer->person_id] = $label;
        }

        // Employees
        $data['employees'] = [];

        foreach ($this->employee->get_all()->getResult() as $employee) {
            $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
        }

        $data['statuses'] = [
            'active'    => lang('Projects.status_active'),
            'completed' => lang('Projects.status_completed'),
            'cancelled' => lang('Projects.status_cancelled'),
            'on_hold'   => lang('Projects.status_on_hold'),
        ];

        if ($projectId === NEW_ENTRY) {
            $data['projects_info']->project_number = '';
            $data['projects_info']->project_name   = '';
            $data['projects_info']->status         = 'active';
            $data['projects_info']->start_date     = date('Y-m-d');
            $data['projects_info']->budget_amount  = 0;
            $data['projects_info']->employee_id    = $currentEmployeeId;
        }

        // Linked sales & expenses
        $data['linked_sales']          = [];
        $data['linked_expenses']       = [];
        $data['linked_sales_total']    = 0;
        $data['linked_expenses_total'] = 0;

        if ($projectId !== NEW_ENTRY) {
            foreach ($this->project->get_linked_sales($projectId)->getResult() as $sale) {
                $data['linked_sales'][] = $sale;
                $data['linked_sales_total'] += (float) $sale->total;
            }

            foreach ($this->project->get_linked_expenses($projectId)->getResult() as $expense) {
                $data['linked_expenses'][] = $expense;
                $data['linked_expenses_total'] += (float) $expense->amount + (float) $expense->tax_amount;
            }
        }

        return view('projects/form', $data);
    }

    public function getRow(int $rowId): ResponseInterface
    {
        $projectInfo = $this->project->get_info($rowId);
        $dataRow     = get_projects_data_row($projectInfo);

        return $this->response->setJSON($dataRow);
    }

    public function postSave(int $projectId = NEW_ENTRY): ResponseInterface
    {
        $newDate = $this->request->getPost('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $currentEmployeeId   = $this->employee->get_logged_in_employee_info()->person_id;
        $submittedEmployeeId = $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT);

        if (! $this->employee->has_grant('employees', $currentEmployeeId)) {
            if ($projectId === NEW_ENTRY) {
                $employeeId = $currentEmployeeId;
            } else {
                $existingProject = $this->project->get_info($projectId);
                $employeeId      = $existingProject->employee_id;
            }
        } else {
            $employeeId = $submittedEmployeeId;
        }

        $projectData = [
            'project_number' => $this->request->getPost('project_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'project_name'   => $this->request->getPost('project_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'customer_id'    => $this->request->getPost('customer_id') === '' ? null : $this->request->getPost('customer_id', FILTER_SANITIZE_NUMBER_INT),
            'employee_id'    => $employeeId,
            'description'    => $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'status'         => $this->request->getPost('status', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'start_date'     => $newDate,
            'end_date'       => $this->request->getPost('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) === '' ? null : $this->request->getPost('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'budget_amount'  => parse_decimals($this->request->getPost('budget_amount')),
            'deleted'        => $this->request->getPost('deleted') !== null,
        ];

        if ($this->project->save_value($projectData, $projectId)) {
            if ($projectId === NEW_ENTRY) {
                return $this->response->setJSON(['success' => true, 'message' => lang('Projects.successful_adding'), 'id' => $projectData['project_id']]);
            }

            return $this->response->setJSON(['success' => true, 'message' => lang('Projects.successful_updating'), 'id' => $projectId]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Projects.error_adding_updating'), 'id' => NEW_ENTRY]);
    }

    public function postDelete(): ResponseInterface
    {
        $projectsToDelete = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($this->project->delete_list($projectsToDelete)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Projects.successful_deleted') . ' ' . count($projectsToDelete) . ' ' . lang('Projects.one_or_multiple'), 'ids' => $projectsToDelete]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Projects.cannot_be_deleted'), 'ids' => $projectsToDelete]);
    }

    public function postLinkSale(int $projectId): ResponseInterface
    {
        $saleId = $this->request->getPost('sale_id', FILTER_SANITIZE_NUMBER_INT);

        if ($this->project->link_sale($projectId, $saleId)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Projects.sale_linked')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Projects.error_linking')]);
    }

    public function postUnlinkSale(int $saleId): ResponseInterface
    {
        if ($this->project->unlink_sale($saleId)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Projects.sale_unlinked')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Projects.error_unlinking')]);
    }

    public function postLinkExpense(int $projectId): ResponseInterface
    {
        $expenseId = $this->request->getPost('expense_id', FILTER_SANITIZE_NUMBER_INT);

        if ($this->project->link_expense($projectId, $expenseId)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Projects.expense_linked')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Projects.error_linking')]);
    }

    public function postUnlinkExpense(int $expenseId): ResponseInterface
    {
        if ($this->project->unlink_expense($expenseId)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Projects.expense_unlinked')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Projects.error_unlinking')]);
    }
}
