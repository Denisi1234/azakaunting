<?php

namespace App\Models;

use CodeIgniter\Model;

class Salary_structure extends Model
{
    protected $table            = 'salary_structures';
    protected $primaryKey       = 'employee_id';
    protected $useAutoIncrement = false;
    protected $allowedFields    = [
        'employee_id',
        'pay_type',
        'basic_salary',
        'hourly_rate',
        'allowances',
        'deductions',
        'notes',
    ];

    public function get_info(int $employeeId): object
    {
        $row = $this->db->table('salary_structures')->where('employee_id', $employeeId)->get()->getRow();
        if ($row) {
            return $row;
        }

        return (object) [
            'employee_id'  => $employeeId,
            'pay_type'     => 'monthly',
            'basic_salary' => 0.0,
            'hourly_rate'  => 0.0,
            'allowances'   => 0.0,
            'deductions'   => 0.0,
            'notes'        => '',
        ];
    }

    public function save_value(array $data): bool
    {
        $builder = $this->db->table('salary_structures');
        $exists  = $builder->where('employee_id', $data['employee_id'])->get()->getNumRows() > 0;

        if ($exists) {
            $builder->where('employee_id', $data['employee_id']);

            return $builder->update($data);
        }

        return $builder->insert($data);
    }
}
