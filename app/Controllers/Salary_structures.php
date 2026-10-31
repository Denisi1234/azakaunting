<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Salary_structure;
use CodeIgniter\HTTP\RedirectResponse;
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
        $list = [];

        foreach ($employees as $emp) {
            if ($emp->deleted) {
                continue;
            }
            $struct = $this->salaryStructure->get_info($emp->person_id);
            $list[] = [
                'employee_id'   => $emp->person_id,
                'name'          => $emp->first_name . ' ' . $emp->last_name,
                'username'      => $emp->username,
                'pay_type'      => $struct->pay_type ?? 'monthly',
                'basic_salary'  => (float) ($struct->basic_salary ?? 0),
                'hourly_rate'   => (float) ($struct->hourly_rate ?? 0),
                'allowances'    => (float) ($struct->allowances ?? 0),
                'deductions'    => (float) ($struct->deductions ?? 0),
                'notes'         => $struct->notes ?? '',
            ];
        }

        $data['structures'] = $list;
        return view('payroll/salary_structures', $data);
    }

    public function getView(int $employeeId = NEW_ENTRY): string
    {
        $data['employee_id']   = $employeeId;
        $data['structure']     = $this->salaryStructure->get_info($employeeId);
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

        $this->salaryStructure->save_value($data);
        return $this->response->redirect(site_url('salary_structures'));
    }
}
