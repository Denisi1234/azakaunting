<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'purchase_order_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [];

    public function createDraft(array $cart, int $supplierId, int $locationId, int $employeeId, ?string $comment, ?string $expectedDate): int
    {
        if ($cart === [] || ! model(Supplier::class)->exists($supplierId)) {
            return -1;
        }

        $items = [];
        foreach ($cart as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0) * (float) ($item['receiving_quantity'] ?? 1);
            $lineTotal = (float) ($item['total'] ?? 0);
            $unitCost = $quantity > 0 ? $lineTotal / $quantity : 0.0;

            if ($itemId < 1 || ! is_finite($quantity) || $quantity <= 0 || ! is_finite($lineTotal)
                || ! is_finite($unitCost) || $unitCost < 0 || $quantity > 1.0e14 || $lineTotal > 1.0e14) {
                return -1;
            }

            if (! isset($items[$itemId])) {
                $items[$itemId] = ['ordered_quantity' => 0.0, 'total_cost' => 0.0];
            }

            $items[$itemId]['ordered_quantity'] += $quantity;
            $items[$itemId]['total_cost'] += $lineTotal;
        }

        $this->db->transStart();
        $this->db->table('purchase_orders')->insert([
            'supplier_id' => $supplierId,
            'location_id' => $locationId,
            'created_by' => $employeeId,
            'status' => 'draft',
            'order_date' => date('Y-m-d'),
            'expected_date' => $expectedDate,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $purchaseOrderId = (int) $this->db->insertID();
        $this->db->table('purchase_orders')->where('purchase_order_id', $purchaseOrderId)
            ->update(['order_number' => 'PO-' . str_pad((string) $purchaseOrderId, 8, '0', STR_PAD_LEFT)]);

        foreach ($items as $itemId => $item) {
            $unitCost = $item['total_cost'] / $item['ordered_quantity'];
            $this->db->table('purchase_order_items')->insert([
                'purchase_order_id' => $purchaseOrderId,
                'item_id' => $itemId,
                'ordered_quantity' => $item['ordered_quantity'],
                'received_quantity' => 0,
                'unit_cost' => $unitCost,
            ]);
        }

        $this->db->transComplete();

        return $this->db->transStatus() ? $purchaseOrderId : -1;
    }

    public function getAll(array $locationIds): ResultInterface
    {
        $builder = $this->db->table('purchase_orders AS purchase_orders')
            ->select('purchase_orders.*, suppliers.company_name, supplier_people.first_name AS supplier_first_name, supplier_people.last_name AS supplier_last_name, creator.first_name AS creator_first_name, creator.last_name AS creator_last_name, approver.first_name AS approver_first_name, approver.last_name AS approver_last_name, canceller.first_name AS canceller_first_name, canceller.last_name AS canceller_last_name, stock_locations.location_name')
            ->join('suppliers', 'suppliers.person_id = purchase_orders.supplier_id', 'left')
            ->join('people AS supplier_people', 'supplier_people.person_id = purchase_orders.supplier_id', 'left')
            ->join('people AS creator', 'creator.person_id = purchase_orders.created_by')
            ->join('people AS approver', 'approver.person_id = purchase_orders.approved_by', 'left')
            ->join('people AS canceller', 'canceller.person_id = purchase_orders.cancelled_by', 'left')
            ->join('stock_locations', 'stock_locations.location_id = purchase_orders.location_id')
            ->orderBy('purchase_orders.purchase_order_id', 'desc');

        if ($locationIds === []) {
            $builder->where('purchase_orders.location_id', 0);
        } else {
            $builder->whereIn('purchase_orders.location_id', $locationIds);
        }

        return $builder->get();
    }

    public function getOrder(int $purchaseOrderId): ?object
    {
        return $this->db->table('purchase_orders')->where('purchase_order_id', $purchaseOrderId)->get()->getRow();
    }

    public function getItems(int $purchaseOrderId): array
    {
        return $this->db->table('purchase_order_items AS purchase_order_items')
            ->select('purchase_order_items.*, items.name, items.item_number')
            ->join('items', 'items.item_id = purchase_order_items.item_id')
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderBy('items.name')
            ->get()->getResultArray();
    }

    public function requiresDiscrepancyReason(int $purchaseOrderId, array $cart): bool
    {
        $receivedByItem = [];
        foreach ($cart as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $receivedByItem[$itemId] = ($receivedByItem[$itemId] ?? 0.0)
                + (float) ($item['quantity'] ?? 0) * (float) ($item['receiving_quantity'] ?? 1);
        }

        foreach ($this->getItems($purchaseOrderId) as $item) {
            $remaining = (float) $item['ordered_quantity'] - (float) $item['received_quantity'];
            if ($remaining > 0 && ($receivedByItem[(int) $item['item_id']] ?? 0.0) < $remaining) {
                return true;
            }
        }

        return false;
    }

    public function approve(int $purchaseOrderId, int $approverId, string $reason): bool
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 255) {
            return false;
        }

        $builder = $this->db->table('purchase_orders');
        $builder->where('purchase_order_id', $purchaseOrderId);
        $builder->where('status', 'draft');
        $builder->where('created_by !=', $approverId);

        $updated = $builder->update([
            'status' => 'ordered',
            'approved_by' => $approverId,
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_reason' => $reason,
        ]);

        return $updated && $this->db->affectedRows() === 1;
    }

    public function cancel(int $purchaseOrderId, int $employeeId, string $reason): bool
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 255) {
            return false;
        }

        $receivedItems = $this->db->table('purchase_order_items')
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('received_quantity >', 0)
            ->countAllResults();

        if ($receivedItems > 0) {
            return false;
        }

        $builder = $this->db->table('purchase_orders');
        $builder->where('purchase_order_id', $purchaseOrderId);
        $builder->whereIn('status', ['draft', 'ordered']);

        $updated = $builder->update([
            'status' => 'cancelled',
            'cancelled_by' => $employeeId,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason,
        ]);

        return $updated && $this->db->affectedRows() === 1;
    }

    public function validateReceipt(int $purchaseOrderId, int $supplierId, int $locationId, array $cart, string $receiptToken): bool
    {
        $lockSuffix = $this->db->DBDriver === 'MySQLi' ? ' FOR UPDATE' : '';
        $order = $this->db->query(
            'SELECT * FROM ' . $this->db->prefixTable('purchase_orders') . ' WHERE purchase_order_id = ?' . $lockSuffix,
            [$purchaseOrderId]
        )->getRow();

        if ($order === null || ! in_array($order->status, ['ordered', 'partially_received'], true)
            || (int) $order->supplier_id !== $supplierId || (int) $order->location_id !== $locationId) {
            return false;
        }

        if ($this->db->table('receivings')->where('purchase_order_id', $purchaseOrderId)
            ->where('purchase_order_receipt_token', $receiptToken)->countAllResults() > 0) {
            return false;
        }

        $orderedItems = [];
        foreach ($this->getItems($purchaseOrderId) as $item) {
            $orderedItems[(int) $item['item_id']] = $item;
        }

        $receivedByItem = [];
        foreach ($cart as $item) {
            $itemId = (int) ($item['item_id'] ?? 0);
            $receivedQuantity = (float) ($item['quantity'] ?? 0) * (float) ($item['receiving_quantity'] ?? 1);

            if (! isset($orderedItems[$itemId]) || ! is_finite($receivedQuantity) || $receivedQuantity <= 0
                || (int) ($item['item_location'] ?? 0) !== $locationId) {
                return false;
            }

            $receivedByItem[$itemId] = ($receivedByItem[$itemId] ?? 0.0) + $receivedQuantity;
        }

        foreach ($receivedByItem as $itemId => $receivedQuantity) {
            $orderItem = $orderedItems[$itemId];
            if ((float) $orderItem['received_quantity'] + $receivedQuantity > (float) $orderItem['ordered_quantity']) {
                return false;
            }
        }

        return true;
    }

    public function recordReceipt(int $purchaseOrderId, array $cart): bool
    {
        $receivedByItem = [];
        foreach ($cart as $item) {
            $itemId = (int) $item['item_id'];
            $receivedByItem[$itemId] = ($receivedByItem[$itemId] ?? 0.0)
                + (float) $item['quantity'] * (float) ($item['receiving_quantity'] ?? 1);
        }

        foreach ($receivedByItem as $itemId => $receivedQuantity) {
            $this->db->table('purchase_order_items')
                ->where('purchase_order_id', $purchaseOrderId)
                ->where('item_id', $itemId)
                ->set('received_quantity', 'received_quantity + ' . $this->db->escape($receivedQuantity), false)
                ->update();
        }

        $remaining = $this->db->table('purchase_order_items')
            ->where('purchase_order_id', $purchaseOrderId)
            ->where('received_quantity < ordered_quantity')
            ->countAllResults();
        $status = $remaining === 0 ? 'received' : 'partially_received';

        return $this->db->table('purchase_orders')
            ->where('purchase_order_id', $purchaseOrderId)
            ->update([
                'status' => $status,
                'completed_at' => $remaining === 0 ? date('Y-m-d H:i:s') : null,
            ]);
    }
}
