<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Salary_structure;
use CodeIgniter\HTTP\ResponseInterface;

class Salary_structures extends Secure_Controller
{
    private Salary_structure $salaryStructure;
    protected Employee $employee;

    public function __construct()
    {
        parent::__construct('payroll');

        $this->salaryStructure = model(Salary_structure::class);
        $this->employee        = model(Employee::class);
    }

    public function getIndex(): string
    {
        $employees = $this->employee->get_all()->getResult();
        $list      = [];

        foreach ($employees as $emp) {
            if ($emp->deleted) {
                continue;
            }
            $struct = $this->salaryStructure->get_info($emp->person_id);
            $list[] = [
                'employee_id'  => $emp->person_id,
                'name'         => $emp->first_name . ' ' . $emp->last_name,
                'username'     => $emp->username,
                'pay_type'     => $struct->pay_type ?? 'monthly',
                'basic_salary' => (float) ($struct->basic_salary ?? 0),
                'hourly_rate'  => (float) ($struct->hourly_rate ?? 0),
                'allowances'   => (float) ($struct->allowances ?? 0),
                'deductions'   => (float) ($struct->deductions ?? 0),
                'notes'        => $struct->notes ?? '',
            ];
        }

        $data['structures'] = $list;

        return view('payroll/salary_structures', $data);
    }

    public function getView(int $employeeId = NEW_ENTRY): string
    {
        $data['employee_id']        = $employeeId;
        $data['structure']          = $this->salaryStructure->get_info($employeeId);
        $data['profile']            = $this->db->table('payroll_employee_profiles')->where('employee_id', $employeeId)->get()->getRow() ?? (object) [];
        $data['payroll_components'] = $this->db->table('payroll_components')
            ->where('active', 1)
            ->where('statutory', 0)
            ->whereIn('component_type', ['earning', 'deduction'])
            ->orderBy('sort_order')->get()->getResult();
        $data['assigned_components'] = $this->db->table('employee_payroll_components AS epc')
            ->select('epc.*, pc.component_name, pc.component_type')
            ->join('payroll_components AS pc', 'pc.payroll_component_id = epc.payroll_component_id')
            ->where('epc.employee_id', $employeeId)->where('epc.active', 1)
            ->orderBy('pc.sort_order')->get()->getResult();
        $emp                   = $this->employee->get_info($employeeId);
        $data['employee_name'] = $emp ? $emp->first_name . ' ' . $emp->last_name : '';

        return view('payroll/salary_structure_form', $data);
    }

    public function postSave(int $employeeId = NEW_ENTRY): ResponseInterface
    {
        $data = [
            'employee_id'  => $employeeId,
            'pay_type'     => $this->request->getPost('pay_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'monthly',
            'basic_salary' => parse_decimals($this->request->getPost('basic_salary')),
            'hourly_rate'  => parse_decimals($this->request->getPost('hourly_rate')),
            'allowances'   => parse_decimals($this->request->getPost('allowances')),
            'deductions'   => parse_decimals($this->request->getPost('deductions')),
            'notes'        => $this->request->getPost('notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        $this->db->transStart();
        $this->salaryStructure->save_value($data);
        $profileData = [
            'employee_id'            => $employeeId,
            'tax_status'             => 'resident',
            'fund_code'              => strtoupper((string) $this->request->getPost('fund_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS)),
            'tax_identifier'         => $this->request->getPost('tax_identifier', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'social_security_number' => $this->request->getPost('social_security_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'primary_employment'     => $this->request->getPost('primary_employment') ? 1 : 0,
            'employee_bank_name'     => $this->request->getPost('employee_bank_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'employee_bank_branch'   => $this->request->getPost('employee_bank_branch', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'employee_bank_account'  => $this->request->getPost('employee_bank_account', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];
        $exists  = $this->db->table('payroll_employee_profiles')->where('employee_id', $employeeId)->countAllResults() > 0;
        $builder = $this->db->table('payroll_employee_profiles');
        if ($exists) {
            $builder->where('employee_id', $employeeId)->update($profileData);
        } else {
            $builder->insert($profileData);
        }

        $componentIds       = $this->request->getPost('component_id') ?? [];
        $assignmentIds      = $this->request->getPost('assignment_id') ?? [];
        $amounts            = $this->request->getPost('component_amount') ?? [];
        $rates              = $this->request->getPost('component_rate') ?? [];
        $effectiveFrom      = $this->request->getPost('effective_from') ?? [];
        $effectiveTo        = $this->request->getPost('effective_to') ?? [];
        $removedAssignments = $this->request->getPost('remove_assignment') ?? [];
        if (! is_array($componentIds) || ! is_array($assignmentIds) || ! is_array($amounts) || ! is_array($rates) || ! is_array($effectiveFrom) || ! is_array($effectiveTo) || ! is_array($removedAssignments)) {
            $this->db->transRollback();

            return redirect()->back()->withInput()->with('error', lang('Payroll.save_failed'));
        }
        $removed = array_map('intval', $removedAssignments);

        foreach ($assignmentIds as $index => $assignmentId) {
            $assignmentId = (int) $assignmentId;
            if ($assignmentId < 1) {
                continue;
            }
            $assignmentBuilder = $this->db->table('employee_payroll_components')
                ->where('employee_payroll_component_id', $assignmentId)
                ->where('employee_id', $employeeId);
            if (in_array($assignmentId, $removed, true)) {
                $assignmentBuilder->update(['active' => 0, 'effective_to' => date('Y-m-d')]);

                continue;
            }
            if ((int) ($componentIds[$index] ?? 0) < 1) {
                continue;
            }
            if (! $this->isAssignableComponent((int) $componentIds[$index])) {
                continue;
            }
            $assignmentBuilder->update([
                'payroll_component_id' => (int) $componentIds[$index],
                'amount'               => parse_decimals($amounts[$index] ?? 0),
                'rate'                 => parse_decimals($rates[$index] ?? 0),
                'effective_from'       => ($effectiveFrom[$index] ?? '') ?: date('Y-m-d'),
                'effective_to'         => ($effectiveTo[$index] ?? '') ?: null,
            ]);
        }

        foreach ($componentIds as $index => $componentId) {
            if ((int) ($assignmentIds[$index] ?? 0) > 0 || (int) $componentId < 1) {
                continue;
            }
            if (! $this->isAssignableComponent((int) $componentId)) {
                continue;
            }
            $this->db->table('employee_payroll_components')->insert([
                'employee_id'          => $employeeId,
                'payroll_component_id' => (int) $componentId,
                'amount'               => parse_decimals($amounts[$index] ?? 0),
                'rate'                 => parse_decimals($rates[$index] ?? 0),
                'effective_from'       => ($effectiveFrom[$index] ?? '') ?: date('Y-m-d'),
                'effective_to'         => ($effectiveTo[$index] ?? '') ?: null,
                'active'               => 1,
            ]);
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            return redirect()->to('salary_structures')->with('error', lang('Payroll.save_failed'));
        }

        return $this->response->redirect(site_url('salary_structures'));
    }

    private function isAssignableComponent(int $componentId): bool
    {
        return $this->db->table('payroll_components')
            ->where('payroll_component_id', $componentId)
            ->where('active', 1)
            ->where('statutory', 0)
            ->whereIn('component_type', ['earning', 'deduction'])
            ->countAllResults() === 1;
    }
}
