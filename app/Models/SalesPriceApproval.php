<?php

namespace App\Models;

use CodeIgniter\Model;

class SalesPriceApproval extends Model
{
    protected $table = 'sales_price_approvals';
    protected $primaryKey = 'approval_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [
        'requester_id',
        'approver_id',
        'location_id',
        'sale_id',
        'session_key',
        'line_key',
        'item_id',
        'item_name',
        'old_price',
        'requested_price',
        'old_total',
        'requested_total',
        'old_discount',
        'requested_discount',
        'old_discount_type',
        'requested_discount_type',
        'reason',
        'status',
        'decision_reason',
        'created_at',
        'decided_at',
        'applied_at',
    ];

    public function getPending(array $locationIds): array
    {
        if ($locationIds === []) {
            return [];
        }

        return $this->db->table('sales_price_approvals')
            ->select('sales_price_approvals.*, requester.first_name AS requester_first_name, requester.last_name AS requester_last_name, stock_locations.location_name')
            ->join('people AS requester', 'requester.person_id = sales_price_approvals.requester_id')
            ->join('stock_locations', 'stock_locations.location_id = sales_price_approvals.location_id')
            ->where('sales_price_approvals.status', 'pending')
            ->whereIn('sales_price_approvals.location_id', $locationIds)
            ->orderBy('sales_price_approvals.created_at', 'ASC')
            ->get()
            ->getResult();
    }

    public function getRecentDecisions(array $locationIds, int $limit = 25): array
    {
        if ($locationIds === []) {
            return [];
        }

        return $this->db->table('sales_price_approvals')
            ->select('sales_price_approvals.*, requester.first_name AS requester_first_name, requester.last_name AS requester_last_name, approver.first_name AS approver_first_name, approver.last_name AS approver_last_name, stock_locations.location_name')
            ->join('people AS requester', 'requester.person_id = sales_price_approvals.requester_id')
            ->join('people AS approver', 'approver.person_id = sales_price_approvals.approver_id', 'LEFT')
            ->join('stock_locations', 'stock_locations.location_id = sales_price_approvals.location_id')
            ->where('sales_price_approvals.status !=', 'pending')
            ->whereIn('sales_price_approvals.location_id', $locationIds)
            ->orderBy('sales_price_approvals.decided_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();
    }

    public function getApproval(int $approvalId): ?array
    {
        return $this->asArray()->find($approvalId);
    }

    public function requestPriceChange(array $request): int|false
    {
        $existing = $this->asArray()
            ->where('requester_id', $request['requester_id'])
            ->where('session_key', $request['session_key'])
            ->where('line_key', $request['line_key'])
            ->where('item_id', $request['item_id'])
            ->where('requested_price', $request['requested_price'])
            ->where('requested_total', $request['requested_total'])
            ->where('requested_discount', $request['requested_discount'])
            ->where('requested_discount_type', $request['requested_discount_type'])
            ->where('status', 'pending')
            ->first();

        if ($existing !== null) {
            return (int) $existing['approval_id'];
        }

        $request['status'] = 'pending';
        $request['created_at'] = date('Y-m-d H:i:s');

        return $this->insert($request) === false ? false : (int) $this->getInsertID();
    }

    public function decide(int $approvalId, int $approverId, string $decision, string $reason): bool
    {
        if (! in_array($decision, ['approved', 'rejected'], true) || trim($reason) === '') {
            return false;
        }

        return $this->where('approval_id', $approvalId)
            ->where('status', 'pending')
            ->where('requester_id !=', $approverId)
            ->update([
                'approver_id'    => $approverId,
                'status'         => $decision,
                'decision_reason' => trim($reason),
                'decided_at'     => date('Y-m-d H:i:s'),
            ]);
    }

    public function applyApprovedChange(
        int $requesterId,
        string $sessionKey,
        string $lineKey,
        int $itemId,
        string $oldPrice,
        string $requestedPrice,
        ?string $oldTotal,
        ?string $requestedTotal,
        string $oldDiscount,
        string $requestedDiscount,
        int $oldDiscountType,
        int $requestedDiscountType
    ): bool {
        $precision = totals_decimals();
        $candidates = $this->asArray()
            ->where('requester_id', $requesterId)
            ->where('session_key', $sessionKey)
            ->where('line_key', $lineKey)
            ->where('item_id', $itemId)
            ->where('status', 'approved')
            ->where('decided_at >=', date('Y-m-d H:i:s', time() - 900))
            ->orderBy('decided_at', 'ASC')
            ->findAll();

        foreach ($candidates as $candidate) {
            if (
                bccomp((string) $candidate['old_price'], $oldPrice, $precision) !== 0
                || bccomp((string) $candidate['requested_price'], $requestedPrice, $precision) !== 0
                || ! $this->totalsMatch($candidate['old_total'], $oldTotal, $precision)
                || ! $this->totalsMatch($candidate['requested_total'], $requestedTotal, $precision)
                || bccomp((string) $candidate['old_discount'], $oldDiscount, $precision) !== 0
                || bccomp((string) $candidate['requested_discount'], $requestedDiscount, $precision) !== 0
                || (int) $candidate['old_discount_type'] !== $oldDiscountType
                || (int) $candidate['requested_discount_type'] !== $requestedDiscountType
            ) {
                continue;
            }

            $this->db->table('sales_price_approvals')
                ->where('approval_id', $candidate['approval_id'])
                ->where('status', 'approved')
                ->update(['status' => 'applied', 'applied_at' => date('Y-m-d H:i:s')]);

            return $this->db->affectedRows() === 1;
        }

        return false;
    }

    public function linkAppliedChangesToSale(string $sessionKey, int $employeeId, int $saleId, array $lineKeys): void
    {
        if ($lineKeys === []) {
            return;
        }

        $this->db->table('sales_price_approvals')
            ->where('requester_id', $employeeId)
            ->where('session_key', $sessionKey)
            ->whereIn('line_key', $lineKeys)
            ->where('status', 'applied')
            ->where('sale_id', null)
            ->update(['sale_id' => $saleId]);
    }

    private function totalsMatch(?string $candidateValue, ?string $requestedValue, int $precision): bool
    {
        if ($candidateValue === null || $requestedValue === null) {
            return $candidateValue === $requestedValue;
        }

        return bccomp($candidateValue, $requestedValue, $precision) === 0;
    }
}
