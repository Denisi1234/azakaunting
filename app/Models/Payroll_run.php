<?php

namespace App\Models;

use CodeIgniter\Model;

class Payroll_run extends Model
{
    protected $table            = 'payroll_runs';
    protected $primaryKey       = 'payroll_run_id';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'period_start',
        'period_end',
        'status',
        'created_by',
        'created_at',
        'approved_at',
        'paid_at',
        'notes',
    ];

    public function exists(int $id): bool
    {
        return $this->db->table('payroll_runs')->where('payroll_run_id', $id)->get()->getNumRows() === 1;
    }

    public function get_info(int $id): object
    {
        $row = $this->db->table('payroll_runs')->where('payroll_run_id', $id)->get()->getRow();

        return $row ?: (object) [
            'payroll_run_id' => NEW_ENTRY,
            'period_start'   => date('Y-m-01'),
            'period_end'     => date('Y-m-t'),
            'status'         => 'draft',
            'notes'          => '',
        ];
    }

    public function save_value(array &$data, int $id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('payroll_runs');
        if ($id === NEW_ENTRY || ! $this->exists($id)) {
            if ($builder->insert($data)) {
                $data['payroll_run_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder->where('payroll_run_id', $id);

        return $builder->update($data);
    }

    public function delete_list(array $ids): bool
    {
        if (empty($ids)) {
            return true;
        }

        $builder = $this->db->table('payroll_runs');
        $this->db->transStart();
        $builder->whereIn('payroll_run_id', $ids)->delete();
        $this->db->transComplete();

        return (bool) $this->db->transStatus();
    }
}
