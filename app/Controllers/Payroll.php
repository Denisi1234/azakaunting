<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Expense_category;
use App\Models\Payroll_item;
use App\Models\Payroll_run;
use App\Models\Salary_structure;
use App\Models\Timeclock;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Payroll extends Secure_Controller
{
    private Payroll_run $payrollRun;
    private Payroll_item $payrollItem;
    private Salary_structure $salaryStructure;
    private Expense $expense;
    private Expense_category $expenseCategory;
    protected Employee $employee;

    public function __construct()
    {
        parent::__construct('payroll');

        $this->payrollRun       = model(Payroll_run::class);
        $this->payrollItem      = model(Payroll_item::class);
        $this->salaryStructure  = model(Salary_structure::class);
        $this->employee         = model(Employee::class);
        $this->expense          = model(Expense::class);
        $this->expenseCategory  = model(Expense_category::class);
    }

    public function getIndex(): string
    {
        $data['table_headers'] = get_payroll_manage_table_headers();
        $data['controller_name'] = 'payroll';
        return view('payroll/manage', $data);
    }

    public function getSearch(): ResponseInterface
    {
        $search = $this->request->getGet('search');
        $limit  = (int) ($this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT) ?? 20);
        $offset = (int) ($this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT) ?? 0);
        $sort   = $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'payroll_run_id';
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'desc';

        $builder = $this->db->table('payroll_runs');
        if (!empty($search)) {
            $builder->groupStart()
                ->like('notes', $search)
                ->orLike('status', $search)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResult();

        $dataRows = [];
        foreach ($rows as $r) {
            $stats = $this->db->table('payroll_items')
                ->selectCount('payroll_item_id', 'emp_count')
                ->selectSum('net_pay', 'total_net')
                ->where('payroll_run_id', $r->payroll_run_id)
                ->where('deleted', 0)
                ->get()
                ->getRow();

            $statusBadge = match ($r->status) {
                'paid'     => '<span class="label label-success">' . strtoupper(lang('Payroll.paid')) . '</span>',
                'approved' => '<span class="label label-info">' . strtoupper(lang('Payroll.approved')) . '</span>',
                default    => '<span class="label label-warning">' . strtoupper(lang('Payroll.draft')) . '</span>',
            };

            $actions = '<div class="btn-group">';
            $actions .= '<a href="' . site_url("payroll/items/{$r->payroll_run_id}") . '" class="btn btn-default btn-xs" title="' . lang('Payroll.view_items') . '"><span class="glyphicon glyphicon-list"></span> ' . lang('Payroll.view_items') . '</a>';
            if ($r->status === 'draft') {
                $actions .= '<button onclick="runApprove(' . $r->payroll_run_id . ')" class="btn btn-info btn-xs"><span class="glyphicon glyphicon-ok"></span> ' . lang('Payroll.approve') . '</button>';
            } elseif ($r->status === 'approved') {
                $actions .= '<button onclick="runPay(' . $r->payroll_run_id . ')" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-usd"></span> ' . lang('Payroll.mark_paid') . '</button>';
            }
            $actions .= '</div>';

            $dataRows[] = [
                'payroll_run_id'  => $r->payroll_run_id,
                'period'          => $r->period_start . ' &rarr; ' . $r->period_end,
                'employees_count' => (int) ($stats->emp_count ?? 0),
                'total_net'       => to_currency((float) ($stats->total_net ?? 0)),
                'status'          => $statusBadge,
                'notes'           => esc($r->notes ?? ''),
                'edit'            => $actions,
            ];
        }

        return $this->response->setJSON(['total' => $total, 'rows' => $dataRows]);
    }

    public function getView(int $runId = NEW_ENTRY): string
    {
        $data['run_info'] = $this->payrollRun->get_info($runId);
        return view('payroll/form', $data);
    }

    public function postSave(int $runId = NEW_ENTRY): RedirectResponse
    {
        $periodStart = $this->request->getPost('period_start', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $periodEnd   = $this->request->getPost('period_end', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $notes       = $this->request->getPost('notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $runData = [
            'period_start' => $periodStart,
            'period_end'   => $periodEnd,
            'notes'        => $notes,
            'created_by'   => session('person_id') ?? 1,
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        if ($runId === NEW_ENTRY) {
            $runData['status'] = 'draft';
            $this->payrollRun->save_value($runData);
            $newRunId = $runData['payroll_run_id'];

            // Auto-populate payslips from salary structures
            $employees = $this->employee->get_all()->getResult();
            foreach ($employees as $emp) {
                if ($emp->deleted) {
                    continue;
                }

                $struct = $this->salaryStructure->get_info($emp->person_id);
                $basicSalary = (float) $struct->basic_salary;
                $allowances  = (float) $struct->allowances;
                $deductions  = (float) $struct->deductions;
                $totalHours  = 0.0;

                if ($struct->pay_type === 'hourly') {
                    // Pull approved worked hours from timeclocks if available
                    $clockRows = $this->db->table('timeclocks')
                        ->where('employee_id', $emp->person_id)
                        ->where('clock_in >=', $periodStart . ' 00:00:00')
                        ->where('clock_in <=', $periodEnd . ' 23:59:59')
                        ->where('clock_out IS NOT NULL')
                        ->get()
                        ->getResult();

                    foreach ($clockRows as $clock) {
                        $diff = strtotime($clock->clock_out) - strtotime($clock->clock_in);
                        if ($diff > 0) {
                            $totalHours += ($diff / 3600);
                        }
                    }
                    $basicSalary = round($totalHours * (float) $struct->hourly_rate, 2);
                }

                $netPay = max(0, ($basicSalary + $allowances) - $deductions);

                $itemData = [
                    'payroll_run_id' => $newRunId,
                    'employee_id'    => $emp->person_id,
                    'pay_type'       => $struct->pay_type,
                    'total_hours'    => round($totalHours, 2),
                    'basic_salary'   => $basicSalary,
                    'allowances'     => $allowances,
                    'deductions'     => $deductions,
                    'net_pay'        => $netPay,
                    'status'         => 'draft',
                    'notes'          => '',
                ];
                $this->payrollItem->save_value($itemData);
            }

            return redirect()->to("payroll/items/{$newRunId}");
        }

        $this->payrollRun->save_value($runData, $runId);
        return redirect()->to('payroll');
    }

    public function getItems(int $runId): string
    {
        $data['run']   = $this->payrollRun->get_info($runId);
        $data['items'] = $this->payrollItem->get_by_run($runId)->getResult();
        return view('payroll/items', $data);
    }

    public function postApprove(int $runId): ResponseInterface
    {
        $run = $this->payrollRun->get_info($runId);
        if ($run->status === 'draft') {
            $data = [
                'status'      => 'approved',
                'approved_at' => date('Y-m-d H:i:s'),
            ];
            $this->payrollRun->save_value($data, $runId);
            $this->db->table('payroll_items')->where('payroll_run_id', $runId)->update(['status' => 'approved']);
            return $this->response->setJSON(['success' => true, 'message' => lang('Payroll.successful_updating')]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Run cannot be approved']);
    }

    public function postPay(int $runId): ResponseInterface
    {
        $run = $this->payrollRun->get_info($runId);
        if ($run->status === 'approved' || $run->status === 'draft') {
            $now = date('Y-m-d H:i:s');
            $data = [
                'status'  => 'paid',
                'paid_at' => $now,
            ];
            $this->payrollRun->save_value($data, $runId);
            $this->db->table('payroll_items')->where('payroll_run_id', $runId)->update(['status' => 'paid', 'paid_date' => date('Y-m-d')]);

            // Auto-post total payroll to Expenses under 'Salaries & Wages'
            $totalNet = $this->db->table('payroll_items')
                ->selectSum('net_pay', 'total')
                ->where('payroll_run_id', $runId)
                ->where('deleted', 0)
                ->get()
                ->getRow()->total ?? 0;

            if ($totalNet > 0) {
                // Ensure Wages category exists
                $cat = $this->db->table('expense_categories')->like('category_name', 'Payroll')->orLike('category_name', 'Wages')->get()->getRow();
                $catId = $cat->expense_category_id ?? 1;

                $expenseData = [
                    'date'                => date('Y-m-d H:i:s'),
                    'amount'              => $totalNet,
                    'payment_type'        => 'Cash',
                    'expense_category_id' => $catId,
                    'description'         => "Payroll Run #{$runId} ({$run->period_start} to {$run->period_end})",
                    'employee_id'         => session('person_id') ?? 1,
                    'deleted'             => 0,
                ];
                $this->expense->save_value($expenseData);
            }

            return $this->response->setJSON(['success' => true, 'message' => lang('Payroll.successful_updating')]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Run cannot be marked as paid']);
    }

    public function getPayslip(int $itemId): string
    {
        $data['item'] = $this->payrollItem->get_info($itemId);
        return view('payroll/payslip', $data);
    }

    public function postDelete(): ResponseInterface
    {
        $ids = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!empty($ids) && $this->payrollRun->delete_list($ids)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Payroll.successful_deleted')]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'Could not delete']);
    }

    public function getReport(): string
    {
        $runs = $this->payrollRun->orderBy('payroll_run_id', 'desc')->get()->getResult();
        $summary = [];
        foreach ($runs as $r) {
            $stats = $this->db->table('payroll_items')
                ->selectCount('payroll_item_id', 'emp_count')
                ->selectSum('net_pay', 'total_net')
                ->where('payroll_run_id', $r->payroll_run_id)
                ->where('deleted', 0)
                ->get()
                ->getRow();

            $summary[] = [
                'run'       => $r,
                'count'     => (int) ($stats->emp_count ?? 0),
                'total_net' => to_currency((float) ($stats->total_net ?? 0)),
            ];
        }

        $data['summary'] = $summary;
        return view('payroll/report', $data);
    }
}
