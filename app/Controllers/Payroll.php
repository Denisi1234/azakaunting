<?php

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Payroll_item;
use App\Models\Payroll_run;
use App\Models\Salary_structure;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class Payroll extends Secure_Controller
{
    private Payroll_run $payrollRun;
    private Payroll_item $payrollItem;
    private Salary_structure $salaryStructure;
    protected Employee $employee;

    public function __construct()
    {
        parent::__construct('payroll');

        $this->payrollRun      = model(Payroll_run::class);
        $this->payrollItem     = model(Payroll_item::class);
        $this->salaryStructure = model(Salary_structure::class);
        $this->employee        = model(Employee::class);
    }

    public function getIndex(): string
    {
        $data['table_headers']   = get_payroll_manage_table_headers();
        $data['controller_name'] = 'payroll';

        return view('payroll/manage', $data);
    }

    public function getSettings(): string
    {
        $settings          = $this->db->table('payroll_settings')->where('payroll_setting_id', 1)->get()->getRow();
        $data['settings']  = $settings;
        $data['tax_bands'] = $this->db->table('payroll_tax_bands')
            ->where('jurisdiction', $settings->jurisdiction ?? 'MAINLAND')
            ->where('tax_status', 'resident')
            ->orderBy('income_from')
            ->get()->getResult();
        $data['components'] = $this->db->table('payroll_components')->orderBy('sort_order')->get()->getResult();

        return view('payroll/settings', $data);
    }

    public function postSettings(): RedirectResponse
    {
        $rules = [
            'jurisdiction'       => 'required|in_list[MAINLAND]',
            'employee_count'     => 'required|integer|greater_than_equal_to[0]',
            'nssf_employee_rate' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[10]',
            'nssf_employer_rate' => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[20]',
            'sdl_rate'           => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'sdl_employee_limit' => 'required|integer|greater_than_equal_to[0]',
            'wcf_rate'           => 'required|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        if ((float) $this->request->getPost('nssf_employee_rate') + (float) $this->request->getPost('nssf_employer_rate') > 20) {
            return redirect()->back()->withInput()->with('error', lang('Payroll.nssf_total_limit'));
        }

        $bandIds     = $this->request->getPost('payroll_tax_band_id') ?? [];
        $lowerBounds = $this->request->getPost('income_from') ?? [];
        $upperBounds = $this->request->getPost('income_to') ?? [];
        $baseTaxes   = $this->request->getPost('base_tax') ?? [];
        $rates       = $this->request->getPost('rate') ?? [];
        if (! is_array($bandIds) || ! is_array($lowerBounds) || ! is_array($upperBounds) || ! is_array($baseTaxes) || ! is_array($rates)) {
            return redirect()->back()->withInput()->with('error', lang('Payroll.invalid_tax_bands'));
        }
        $bandIds     = array_values($bandIds);
        $lowerBounds = array_values($lowerBounds);
        $upperBounds = array_values($upperBounds);
        $baseTaxes   = array_values($baseTaxes);
        $rates       = array_values($rates);
        $arrayCounts = array_map('count', [$bandIds, $lowerBounds, $upperBounds, $baseTaxes, $rates]);
        if ($arrayCounts[0] === 0 || count(array_unique($arrayCounts)) !== 1) {
            return redirect()->back()->withInput()->with('error', lang('Payroll.invalid_tax_bands'));
        }

        $previousUpper = 0.0;

        foreach ($bandIds as $index => $bandId) {
            $lower   = filter_var($lowerBounds[$index], FILTER_VALIDATE_FLOAT);
            $upper   = $upperBounds[$index] === '' ? null : filter_var($upperBounds[$index], FILTER_VALIDATE_FLOAT);
            $baseTax = filter_var($baseTaxes[$index], FILTER_VALIDATE_FLOAT);
            $rate    = filter_var($rates[$index], FILTER_VALIDATE_FLOAT);
            $isLast  = $index === array_key_last($bandIds);
            if ($lower === false || $baseTax === false || $rate === false || $lower !== $previousUpper || $baseTax < 0 || $rate < 0 || $rate > 100) {
                return redirect()->back()->withInput()->with('error', lang('Payroll.invalid_tax_bands'));
            }
            if ((! $isLast && ($upper === null || $upper <= $lower)) || ($isLast && $upper !== null) || ($upper !== null && $upper <= $lower)) {
                return redirect()->back()->withInput()->with('error', lang('Payroll.invalid_tax_bands'));
            }
            $previousUpper = $upper;
        }
        $matchedBands = $this->db->table('payroll_tax_bands')
            ->whereIn('payroll_tax_band_id', array_map('intval', $bandIds))
            ->where('jurisdiction', $this->request->getPost('jurisdiction'))
            ->where('tax_status', 'resident')->countAllResults();
        if ($matchedBands !== count($bandIds)) {
            return redirect()->back()->withInput()->with('error', lang('Payroll.invalid_tax_bands'));
        }

        $this->db->table('payroll_settings')->where('payroll_setting_id', 1)->update([
            'jurisdiction'       => $this->request->getPost('jurisdiction'),
            'employee_count'     => (int) $this->request->getPost('employee_count'),
            'nssf_employee_rate' => (float) $this->request->getPost('nssf_employee_rate'),
            'nssf_employer_rate' => (float) $this->request->getPost('nssf_employer_rate'),
            'sdl_rate'           => (float) $this->request->getPost('sdl_rate'),
            'sdl_employee_limit' => (int) $this->request->getPost('sdl_employee_limit'),
            'wcf_rate'           => (float) $this->request->getPost('wcf_rate'),
            'updated_by'         => (int) (session('person_id') ?? 1),
            'updated_at'         => date('Y-m-d H:i:s'),
        ]);

        foreach ($bandIds as $index => $bandId) {
            $this->db->table('payroll_tax_bands')->where('payroll_tax_band_id', (int) $bandId)
                ->where('jurisdiction', $this->request->getPost('jurisdiction'))
                ->where('tax_status', 'resident')->update([
                    'income_from' => (float) $lowerBounds[$index],
                    'income_to'   => ($upperBounds[$index] ?? '') === '' ? null : (float) $upperBounds[$index],
                    'base_tax'    => (float) $baseTaxes[$index],
                    'rate'        => (float) $rates[$index],
                ]);
        }

        return redirect()->to('payroll/settings')->with('message', lang('Payroll.settings_saved'));
    }

    public function postComponent(): RedirectResponse
    {
        $rules = [
            'component_code'     => 'required|alpha_numeric_punct|max_length[40]',
            'component_name'     => 'required|max_length[100]',
            'component_type'     => 'required|in_list[earning,deduction]',
            'calculation_method' => 'required|in_list[fixed,percentage]',
            'default_amount'     => 'permit_empty|decimal|greater_than_equal_to[0]',
            'percentage_base'    => 'permit_empty|in_list[basic_salary,gross_pay]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->to('payroll/settings')->with('error', implode(' ', $this->validator->getErrors()));
        }

        $componentCode = strtoupper((string) $this->request->getPost('component_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if ($this->db->table('payroll_components')->where('component_code', $componentCode)->countAllResults() > 0) {
            return redirect()->to('payroll/settings')->with('error', lang('Payroll.component_code_exists'));
        }

        $this->db->table('payroll_components')->insert([
            'component_code'     => $componentCode,
            'component_name'     => $this->request->getPost('component_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'component_type'     => $this->request->getPost('component_type'),
            'calculation_method' => $this->request->getPost('calculation_method'),
            'default_amount'     => parse_decimals($this->request->getPost('default_amount') ?? 0),
            'percentage_base'    => $this->request->getPost('percentage_base') ?: null,
            'taxable'            => $this->request->getPost('taxable') ? 1 : 0,
            'statutory'          => 0,
            'active'             => 1,
            'sort_order'         => ((int) $this->db->table('payroll_components')->selectMax('sort_order')->get()->getRow()->sort_order) + 10,
        ]);

        return redirect()->to('payroll/settings')->with('message', lang('Payroll.component_saved'));
    }

    public function getSearch(): ResponseInterface
    {
        $search = $this->request->getGet('search');
        $limit  = (int) ($this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT) ?? 20);
        $offset = (int) ($this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT) ?? 0);
        $sort   = $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'payroll_run_id';
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'desc';

        $builder = $this->db->table('payroll_runs');
        if (! empty($search)) {
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

                $struct      = $this->salaryStructure->get_info($emp->person_id);
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

                $componentRows = $this->db->table('employee_payroll_components AS epc')
                    ->select('epc.*, pc.component_code, pc.component_name, pc.component_type, pc.calculation_method, pc.percentage_base, pc.taxable')
                    ->join('payroll_components AS pc', 'pc.payroll_component_id = epc.payroll_component_id')
                    ->where('epc.employee_id', $emp->person_id)
                    ->where('epc.active', 1)
                    ->where('epc.effective_from <=', $periodEnd)
                    ->groupStart()->where('epc.effective_to IS NULL')->orWhere('epc.effective_to >=', $periodStart)->groupEnd()
                    ->where('pc.active', 1)->get()->getResult();
                $componentLines     = [];
                $nonTaxableEarnings = 0.0;
                $baseGross          = $basicSalary + $allowances;

                foreach ($componentRows as $componentRow) {
                    $base   = $componentRow->percentage_base === 'basic_salary' ? $basicSalary : $baseGross;
                    $amount = $componentRow->calculation_method === 'percentage'
                        ? round($base * (float) $componentRow->rate / 100, 2)
                        : round((float) $componentRow->amount, 2);
                    if ($amount <= 0) {
                        continue;
                    }
                    if ($componentRow->component_type === 'earning') {
                        $allowances += $amount;
                        if (! $componentRow->taxable) {
                            $nonTaxableEarnings += $amount;
                        }
                    } elseif ($componentRow->component_type === 'deduction') {
                        $deductions += $amount;
                    }
                    $componentLines[] = ['component' => $componentRow, 'amount' => $amount];
                }

                $calculation = $this->calculatePayroll($emp->person_id, $basicSalary, $allowances, $deductions, $periodEnd, $nonTaxableEarnings);

                $itemData = [
                    'payroll_run_id'         => $newRunId,
                    'employee_id'            => $emp->person_id,
                    'pay_type'               => $struct->pay_type,
                    'total_hours'            => round($totalHours, 2),
                    'basic_salary'           => $basicSalary,
                    'allowances'             => $allowances,
                    'deductions'             => $deductions,
                    'net_pay'                => $calculation['net_pay'],
                    'gross_pay'              => $calculation['gross_pay'],
                    'taxable_pay'            => $calculation['taxable_pay'],
                    'paye'                   => $calculation['paye'],
                    'employee_contributions' => $calculation['employee_contributions'],
                    'employer_contributions' => $calculation['employer_contributions'],
                    'employer_cost'          => $calculation['employer_cost'],
                    'status'                 => 'draft',
                    'notes'                  => '',
                ];
                $this->payrollItem->save_value($itemData);
                $itemId = (int) $itemData['payroll_item_id'];
                $this->saveLine($itemId, 'BASIC', lang('Payroll.basic_salary'), 'earning', $basicSalary, true);
                if ((float) $struct->allowances > 0) {
                    $this->saveLine($itemId, 'ALLOWANCE', lang('Payroll.allowances'), 'earning', (float) $struct->allowances, true);
                }

                foreach ($componentLines as $componentLine) {
                    $component = $componentLine['component'];
                    $this->saveLine(
                        $itemId,
                        $component->component_code,
                        $component->component_name,
                        $component->component_type,
                        $componentLine['amount'],
                        (bool) $component->taxable,
                        $component->calculation_method === 'percentage' ? (float) $component->rate : 0.0,
                    );
                }
                if ((float) $struct->deductions > 0) {
                    $this->saveLine($itemId, 'OTHER_DEDUCTION', lang('Payroll.deductions'), 'deduction', (float) $struct->deductions, false);
                }
                $this->saveLine($itemId, 'NSSF_EE', 'NSSF employee contribution', 'deduction', $calculation['nssf_employee'], false, $calculation['nssf_employee_rate']);
                $this->saveLine($itemId, 'PAYE', 'PAYE', 'deduction', $calculation['paye'], false, $calculation['paye_rate']);
                $this->saveLine($itemId, 'NSSF_ER', 'NSSF employer contribution', 'employer_contribution', $calculation['nssf_employer'], false, $calculation['nssf_employer_rate']);
                $this->saveLine($itemId, 'SDL', 'Skills Development Levy', 'employer_contribution', $calculation['sdl'], false, $calculation['sdl_rate']);
                $this->saveLine($itemId, 'WCF', 'Workers Compensation Fund', 'employer_contribution', $calculation['wcf'], false, $calculation['wcf_rate']);
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
                'approved_by' => (int) (session('person_id') ?? 1),
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
        if ($run->status === 'approved') {
            $now  = date('Y-m-d H:i:s');
            $data = [
                'status'            => 'paid',
                'paid_at'           => $now,
                'paid_by'           => (int) (session('person_id') ?? 1),
                'payment_method'    => $this->request->getPost('payment_method', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?: 'Bank transfer',
                'payment_reference' => $this->request->getPost('payment_reference', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            ];
            $this->payrollRun->save_value($data, $runId);
            $this->db->table('payroll_items')->where('payroll_run_id', $runId)->update(['status' => 'paid', 'paid_date' => date('Y-m-d')]);

            return $this->response->setJSON(['success' => true, 'message' => lang('Payroll.successful_updating')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Run cannot be marked as paid']);
    }

    public function getPayslip(int $itemId): string
    {
        $data['item']  = $this->payrollItem->get_info($itemId);
        $data['lines'] = $this->payrollItem->get_lines($itemId);

        return view('payroll/payslip', $data);
    }

    public function getRemittance(int $runId): ResponseInterface
    {
        $run   = $this->payrollRun->get_info($runId);
        $lines = $this->db->table('payroll_item_lines AS l')
            ->select('l.component_code, l.description, pi.employee_id, p.first_name, p.last_name, pr.tax_identifier, pr.social_security_number, SUM(l.amount) AS amount')
            ->join('payroll_items AS pi', 'pi.payroll_item_id = l.payroll_item_id')
            ->join('people AS p', 'p.person_id = pi.employee_id', 'LEFT')
            ->join('payroll_employee_profiles AS pr', 'pr.employee_id = pi.employee_id', 'LEFT')
            ->where('pi.payroll_run_id', $runId)
            ->where('pi.deleted', 0)
            ->whereIn('l.component_code', ['NSSF_EE', 'NSSF_ER', 'PAYE', 'SDL', 'WCF'])
            ->groupBy('l.component_code, l.description, pi.employee_id, p.first_name, p.last_name, pr.tax_identifier, pr.social_security_number')
            ->orderBy('l.component_code')->orderBy('p.last_name')->orderBy('p.first_name')
            ->get()->getResultArray();

        $output = fopen('php://temp', 'r+b');
        fputcsv($output, ['Run', $runId, 'Period', $run->period_start . ' to ' . $run->period_end]);
        fputcsv($output, ['Code', 'Description', 'Employee ID', 'Employee Name', 'TIN', 'Social Security Number', 'Amount']);

        foreach ($lines as $line) {
            fputcsv($output, [
                $line['component_code'],
                $line['description'],
                $line['employee_id'],
                trim(($line['first_name'] ?? '') . ' ' . ($line['last_name'] ?? '')),
                $line['tax_identifier'] ?? '',
                $line['social_security_number'] ?? '',
                $line['amount'],
            ]);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $this->response->download("payroll-remittance-{$runId}.csv", $csv);
    }

    public function postDelete(): ResponseInterface
    {
        $ids = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (! empty($ids) && $this->payrollRun->delete_list($ids)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Payroll.successful_deleted')]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Could not delete']);
    }

    public function getReport(): string
    {
        $runs    = $this->payrollRun->orderBy('payroll_run_id', 'desc')->get()->getResult();
        $summary = [];

        foreach ($runs as $r) {
            $stats = $this->db->table('payroll_items')
                ->selectCount('payroll_item_id', 'emp_count')
                ->selectSum('net_pay', 'total_net')
                ->selectSum('gross_pay', 'total_gross')
                ->selectSum('paye', 'total_paye')
                ->selectSum('employer_cost', 'total_employer_cost')
                ->where('payroll_run_id', $r->payroll_run_id)
                ->where('deleted', 0)
                ->get()
                ->getRow();

            $summary[] = [
                'run'                 => $r,
                'count'               => (int) ($stats->emp_count ?? 0),
                'total_net'           => to_currency((float) ($stats->total_net ?? 0)),
                'total_gross'         => to_currency((float) ($stats->total_gross ?? 0)),
                'total_paye'          => to_currency((float) ($stats->total_paye ?? 0)),
                'total_employer_cost' => to_currency((float) ($stats->total_employer_cost ?? 0)),
            ];
        }

        $data['summary'] = $summary;

        return view('payroll/report', $data);
    }

    private function calculatePayroll(
        int $employeeId,
        float $basicSalary,
        float $allowances,
        float $otherDeductions,
        string $periodEnd,
        float $nonTaxableEarnings = 0,
    ): array {
        $settings     = $this->db->table('payroll_settings')->where('payroll_setting_id', 1)->get()->getRow();
        $profile      = $this->db->table('payroll_employee_profiles')->where('employee_id', $employeeId)->get()->getRow();
        $grossPay     = round($basicSalary + $allowances, 2);
        $nssfEmployee = round($grossPay * ((float) ($settings->nssf_employee_rate ?? 10) / 100), 2);
        $nssfEmployer = round($grossPay * ((float) ($settings->nssf_employer_rate ?? 10) / 100), 2);
        $taxablePay   = max(0, $grossPay - $nssfEmployee - $nonTaxableEarnings);
        $band         = $this->db->table('payroll_tax_bands')
            ->where('jurisdiction', $settings->jurisdiction ?? 'MAINLAND')
            ->where('tax_status', $profile->tax_status ?? 'resident')
            ->where('effective_from <=', $periodEnd)
            ->groupStart()->where('effective_to IS NULL')->orWhere('effective_to >=', $periodEnd)->groupEnd()
            ->where('income_from <=', $taxablePay)
            ->groupStart()->where('income_to IS NULL')->orWhere('income_to >', $taxablePay)->groupEnd()
            ->orderBy('income_from', 'DESC')->get()->getRow();
        $paye = $band ? round((float) $band->base_tax + (($taxablePay - (float) $band->income_from) * (float) $band->rate / 100), 2) : 0.0;
        $sdl  = (int) ($settings->employee_count ?? 0) >= (int) ($settings->sdl_employee_limit ?? 10)
            ? round($grossPay * ((float) ($settings->sdl_rate ?? 0) / 100), 2)
            : 0.0;
        $wcf                   = round($grossPay * ((float) ($settings->wcf_rate ?? 0) / 100), 2);
        $netPay                = max(0, round($grossPay - $nssfEmployee - $paye - $otherDeductions, 2));
        $employerContributions = round($nssfEmployer + $sdl + $wcf, 2);

        return [
            'gross_pay'              => $grossPay,
            'taxable_pay'            => $taxablePay,
            'paye'                   => $paye,
            'nssf_employee'          => $nssfEmployee,
            'nssf_employee_rate'     => (float) ($settings->nssf_employee_rate ?? 10),
            'nssf_employer'          => $nssfEmployer,
            'nssf_employer_rate'     => (float) ($settings->nssf_employer_rate ?? 10),
            'paye_rate'              => (float) ($band->rate ?? 0),
            'sdl'                    => $sdl,
            'sdl_rate'               => (float) ($settings->sdl_rate ?? 0),
            'wcf'                    => $wcf,
            'wcf_rate'               => (float) ($settings->wcf_rate ?? 0),
            'employee_contributions' => $nssfEmployee,
            'employer_contributions' => $employerContributions,
            'employer_cost'          => round($grossPay + $employerContributions, 2),
            'net_pay'                => $netPay,
        ];
    }

    private function saveLine(int $itemId, string $code, string $description, string $type, float $amount, bool $taxable, float $rate = 0): void
    {
        if ($amount <= 0) {
            return;
        }
        $component = $this->db->table('payroll_components')->where('component_code', $code)->get()->getRow();
        $this->db->table('payroll_item_lines')->insert([
            'payroll_item_id'      => $itemId,
            'payroll_component_id' => $component->payroll_component_id ?? null,
            'component_code'       => $code,
            'description'          => $description,
            'component_type'       => $type,
            'rate'                 => round($rate, 4),
            'amount'               => round($amount, 2),
            'taxable'              => $taxable ? 1 : 0,
        ]);
    }
}
