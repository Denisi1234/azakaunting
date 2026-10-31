<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

class Payroll_item extends Model
{
    protected $table = 'payroll_items';
    protected $primaryKey = 'payroll_item_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'payroll_run_id',
        'employee_id',
        'pay_type',
        'total_hours',
        'basic_salary',
        'allowances',
        'deductions',
        'net_pay',
        'status',
        'paid_date',
        'notes',
        'deleted',
    ];

    public function get_by_run(int $runId): ResultInterface
    {
        return $this->db->table('payroll_items AS pi')
            ->select('pi.*, p.first_name, p.last_name, e.username')
            ->join('people AS p', 'p.person_id = pi.employee_id', 'LEFT')
            ->join('employees AS e', 'e.person_id = pi.employee_id', 'LEFT')
            ->where('pi.payroll_run_id', $runId)
            ->where('pi.deleted', 0)
            ->orderBy('p.first_name', 'ASC')
            ->get();
    }

    public function get_info(int $itemId): object
    {
        $row = $this->db->table('payroll_items AS pi')
            ->select('pi.*, p.first_name, p.last_name, p.email, p.phone_number, e.username, pr.period_start, pr.period_end')
            ->join('people AS p', 'p.person_id = pi.employee_id', 'LEFT')
            ->join('employees AS e', 'e.person_id = pi.employee_id', 'LEFT')
            ->join('payroll_runs AS pr', 'pr.payroll_run_id = pi.payroll_run_id', 'LEFT')
            ->where('pi.payroll_item_id', $itemId)
            ->get()
            ->getRow();

        return $row ?: (object) ['payroll_item_id' => NEW_ENTRY];
    }

    public function save_value(array &$data, int $id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('payroll_items');
        if ($id === NEW_ENTRY) {
            if ($builder->insert($data)) {
                $data['payroll_item_id'] = $this->db->insertID();
                return true;
            }
            return false;
        }

        $builder->where('payroll_item_id', $id);
        return $builder->update($data);
    }
}
