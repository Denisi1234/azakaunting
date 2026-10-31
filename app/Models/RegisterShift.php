<?php

namespace App\Models;

use CodeIgniter\Model;

class RegisterShift extends Model
{
    protected $table = 'register_shifts';
    protected $primaryKey = 'register_shift_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'employee_id',
        'location_id',
        'opened_at',
        'opening_cash',
        'closed_at',
        'closed_by',
        'closing_cash',
        'expected_cash',
        'cash_variance',
        'opening_note',
        'closing_note',
        'active_key',
    ];

    public function getActive(int $employeeId, int $locationId): ?object
    {
        return $this->asArray()->where('employee_id', $employeeId)
            ->where('location_id', $locationId)
            ->where('active_key IS NOT NULL', null, false)
            ->first();
    }

    public function getShift(int $shiftId): ?object
    {
        return $this->db->table('register_shifts')
            ->select('register_shifts.*, people.first_name, people.last_name, stock_locations.location_name')
            ->join('people', 'people.person_id = register_shifts.employee_id')
            ->join('stock_locations', 'stock_locations.location_id = register_shifts.location_id')
            ->where('register_shift_id', $shiftId)
            ->get()
            ->getRow();
    }

    public function getExpectedCash(int $shiftId): float
    {
        $cashSales = $this->db->table('sales_payments')
            ->selectSum('payment_amount - cash_refund', 'amount', false)
            ->join('sales', 'sales.sale_id = sales_payments.sale_id')
            ->where('sales.register_shift_id', $shiftId)
            ->where('sales_payments.payment_type', lang('Sales.cash'))
            ->get()
            ->getRow();
        $movements = $this->db->table('register_shift_movements')
            ->select("SUM(CASE WHEN movement_type = 'in' THEN amount ELSE -amount END) AS amount", false)
            ->where('register_shift_id', $shiftId)
            ->get()
            ->getRow();
        $shift = $this->find($shiftId);

        return (float) $shift['opening_cash'] + (float) ($cashSales->amount ?? 0) + (float) ($movements->amount ?? 0);
    }

    public function openShift(int $employeeId, int $locationId, float $openingCash, ?string $note): int|false
    {
        $this->db->transBegin();
        $activeKey = $employeeId . ':' . $locationId;
        $active = $this->db->table('register_shifts')
            ->where('active_key', $activeKey)
            ->get()
            ->getRow();

        if ($active !== null) {
            $this->db->transRollback();

            return false;
        }

        $this->insert([
            'employee_id'  => $employeeId,
            'location_id'  => $locationId,
            'opened_at'    => date('Y-m-d H:i:s'),
            'opening_cash' => $openingCash,
            'opening_note' => $note,
            'active_key'   => $activeKey,
        ]);
        $shiftId = (int) $this->getInsertID();
        $this->db->transComplete();

        return $this->db->transStatus() ? $shiftId : false;
    }

    public function closeShift(int $shiftId, int $employeeId, float $closingCash, ?string $note): bool
    {
        $this->db->transBegin();
        $table = $this->db->prefixTable('register_shifts');
        $lockedShift = $this->db->query(
            "SELECT register_shift_id FROM {$table} WHERE register_shift_id = ? AND active_key IS NOT NULL FOR UPDATE",
            [$shiftId]
        )->getRow();

        if ($lockedShift === null) {
            $this->db->transRollback();

            return false;
        }

        $expectedCash = $this->getExpectedCash($shiftId);
        $this->db->table('register_shifts')
            ->where('register_shift_id', $shiftId)
            ->where('active_key IS NOT NULL', null, false)
            ->update([
                'closed_at'     => date('Y-m-d H:i:s'),
                'closed_by'     => $employeeId,
                'closing_cash'  => $closingCash,
                'expected_cash' => $expectedCash,
                'cash_variance' => $closingCash - $expectedCash,
                'closing_note'  => $note,
                'active_key'    => null,
            ]);
        $this->db->transComplete();

        return $this->db->transStatus();
    }

    public function addMovement(int $shiftId, int $employeeId, string $type, float $amount, string $reason): bool
    {
        if (! in_array($type, ['in', 'out'], true) || $amount <= 0 || trim($reason) === '') {
            return false;
        }

        $active = $this->db->table('register_shifts')
            ->where('register_shift_id', $shiftId)
            ->where('active_key IS NOT NULL', null, false)
            ->countAllResults();

        if ($active !== 1) {
            return false;
        }

        return $this->db->table('register_shift_movements')->insert([
            'register_shift_id' => $shiftId,
            'employee_id'       => $employeeId,
            'movement_type'     => $type,
            'amount'            => $amount,
            'reason'            => trim($reason),
            'created_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    public function getMovements(int $shiftId): array
    {
        return $this->db->table('register_shift_movements')
            ->select('register_shift_movements.*, people.first_name, people.last_name')
            ->join('people', 'people.person_id = register_shift_movements.employee_id')
            ->where('register_shift_id', $shiftId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResult();
    }

    public function getHistory(?int $employeeId, array $locationIds, int $limit = 20): array
    {
        if ($locationIds === []) {
            return [];
        }

        $builder = $this->db->table('register_shifts')
            ->select('register_shifts.*, stock_locations.location_name, people.first_name, people.last_name')
            ->join('stock_locations', 'stock_locations.location_id = register_shifts.location_id')
            ->join('people', 'people.person_id = register_shifts.employee_id')
            ->whereIn('register_shifts.location_id', $locationIds)
            ->where('register_shifts.active_key', null)
            ->orderBy('register_shifts.opened_at', 'DESC')
            ->limit($limit);

        if ($employeeId !== null) {
            $builder->where('register_shifts.employee_id', $employeeId);
        }

        return $builder->get()->getResult();
    }
}
