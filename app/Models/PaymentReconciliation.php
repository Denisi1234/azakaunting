<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentReconciliation extends Model
{
    protected $table = 'payment_reconciliations';
    protected $primaryKey = 'reconciliation_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'business_date',
        'location_id',
        'payment_type',
        'expected_amount',
        'collected_amount',
        'note',
        'reconciled_by',
        'reconciled_at',
    ];

    public function getDailySummary(string $businessDate, array $locationIds): array
    {
        if ($locationIds === []) {
            return [];
        }

        $nextDate = date('Y-m-d', strtotime($businessDate . ' +1 day'));
        $paymentTypes = array_values(array_diff(array_values(get_payment_options()), [lang('Sales.due')]));
        $expectedRows = $this->db->table('sales_payments')
            ->select('register_shifts.location_id, sales_payments.payment_type, SUM(sales_payments.payment_amount - sales_payments.cash_refund) AS expected_amount', false)
            ->join('sales', 'sales.sale_id = sales_payments.sale_id')
            ->join('register_shifts', 'register_shifts.register_shift_id = sales.register_shift_id')
            ->where('sales.sale_time >=', $businessDate . ' 00:00:00')
            ->where('sales.sale_time <', $nextDate . ' 00:00:00')
            ->whereIn('register_shifts.location_id', $locationIds)
            ->whereIn('sales_payments.payment_type', $paymentTypes)
            ->groupBy(['register_shifts.location_id', 'sales_payments.payment_type'])
            ->get()
            ->getResultArray();

        $savedRows = $this->db->table('payment_reconciliations')
            ->select('payment_reconciliations.*, people.first_name, people.last_name')
            ->join('people', 'people.person_id = payment_reconciliations.reconciled_by')
            ->where('business_date', $businessDate)
            ->whereIn('location_id', $locationIds)
            ->get()
            ->getResultArray();
        $saved = [];

        foreach ($savedRows as $row) {
            $saved[$row['location_id'] . '|' . $row['payment_type']] = $row;
        }

        $summary = [];
        foreach ($expectedRows as $row) {
            $key = $row['location_id'] . '|' . $row['payment_type'];
            $reconciliation = $saved[$key] ?? null;
            $collected = $reconciliation === null ? null : (float) $reconciliation['collected_amount'];
            $summary[$key] = [
                'location_id'      => (int) $row['location_id'],
                'payment_type'     => $row['payment_type'],
                'expected_amount'  => (float) $row['expected_amount'],
                'collected_amount' => $collected,
                'variance'         => $collected === null ? null : $collected - (float) $row['expected_amount'],
                'note'             => $reconciliation['note'] ?? '',
                'reconciled_at'    => $reconciliation['reconciled_at'] ?? null,
                'reconciled_by'    => isset($reconciliation['first_name']) ? $reconciliation['first_name'] . ' ' . $reconciliation['last_name'] : '',
            ];
        }

        foreach ($savedRows as $row) {
            $summaryKey = $row['location_id'] . '|' . $row['payment_type'];
            if (isset($summary[$summaryKey])) {
                continue;
            }

            $summary[$summaryKey] = [
                'location_id'      => (int) $row['location_id'],
                'payment_type'     => $row['payment_type'],
                'expected_amount'  => 0.0,
                'collected_amount' => (float) $row['collected_amount'],
                'variance'         => (float) $row['collected_amount'],
                'note'             => $row['note'] ?? '',
                'reconciled_at'    => $row['reconciled_at'],
                'reconciled_by'    => $row['first_name'] . ' ' . $row['last_name'],
            ];
        }

        return array_values($summary);
    }

    public function saveCollectedAmount(
        string $businessDate,
        int $locationId,
        string $paymentType,
        float $expectedAmount,
        float $collectedAmount,
        ?string $note,
        int $employeeId
    ): bool {
        $existing = $this->asArray()->where('business_date', $businessDate)
            ->where('location_id', $locationId)
            ->where('payment_type', $paymentType)
            ->first();
        $data = [
            'business_date'    => $businessDate,
            'location_id'      => $locationId,
            'payment_type'     => $paymentType,
            'expected_amount'  => $expectedAmount,
            'collected_amount' => $collectedAmount,
            'note'             => $note,
            'reconciled_by'    => $employeeId,
            'reconciled_at'    => date('Y-m-d H:i:s'),
        ];

        if ($existing !== null) {
            return $this->update($existing['reconciliation_id'], $data);
        }

        return $this->insert($data) !== false;
    }
}
