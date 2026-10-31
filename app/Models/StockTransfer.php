<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;

class StockTransfer extends Model
{
    protected $table = 'stock_transfers';
    protected $primaryKey = 'transfer_id';
    protected $useAutoIncrement = true;
    protected $allowedFields = [];

    public function createRequest(array $cart, int $sourceLocationId, int $destinationLocationId, int $employeeId, ?string $comment): int
    {
        if ($cart === [] || $sourceLocationId < 1 || $destinationLocationId < 1 || $sourceLocationId === $destinationLocationId) {
            return -1;
        }

        $items = [];
        foreach ($cart as $cartItem) {
            $itemId = (int) ($cartItem['item_id'] ?? 0);
            $quantity = (float) ($cartItem['quantity'] ?? 0);
            if ($itemId < 1 || ! is_finite($quantity) || $quantity <= 0 || $quantity > 1.0e14) {
                return -1;
            }
            $items[$itemId] = ($items[$itemId] ?? 0.0) + $quantity;
        }

        $this->db->transStart();
        $this->db->table('stock_transfers')->insert([
            'source_location_id' => $sourceLocationId,
            'destination_location_id' => $destinationLocationId,
            'created_by' => $employeeId,
            'status' => 'requested',
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $transferId = (int) $this->db->insertID();
        $this->db->table('stock_transfers')->where('transfer_id', $transferId)
            ->update(['transfer_number' => 'TR-' . str_pad((string) $transferId, 8, '0', STR_PAD_LEFT)]);
        foreach ($items as $itemId => $quantity) {
            $this->db->table('stock_transfer_items')->insert([
                'transfer_id' => $transferId,
                'item_id' => $itemId,
                'requested_quantity' => $quantity,
                'dispatched_quantity' => 0,
                'received_quantity' => 0,
            ]);
        }
        $this->db->transComplete();

        return $this->db->transStatus() ? $transferId : -1;
    }

    public function getAll(array $locationIds): ResultInterface
    {
        $builder = $this->db->table('stock_transfers AS transfers')
            ->select('transfers.*, source.location_name AS source_name, destination.location_name AS destination_name, creator.first_name AS creator_first_name, creator.last_name AS creator_last_name, approver.first_name AS approver_first_name, approver.last_name AS approver_last_name, dispatcher.first_name AS dispatcher_first_name, dispatcher.last_name AS dispatcher_last_name, receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name, canceller.first_name AS canceller_first_name, canceller.last_name AS canceller_last_name')
            ->join('stock_locations AS source', 'source.location_id = transfers.source_location_id')
            ->join('stock_locations AS destination', 'destination.location_id = transfers.destination_location_id')
            ->join('people AS creator', 'creator.person_id = transfers.created_by')
            ->join('people AS approver', 'approver.person_id = transfers.approved_by', 'left')
            ->join('people AS dispatcher', 'dispatcher.person_id = transfers.dispatched_by', 'left')
            ->join('people AS receiver', 'receiver.person_id = transfers.received_by', 'left')
            ->join('people AS canceller', 'canceller.person_id = transfers.cancelled_by', 'left')
            ->orderBy('transfers.transfer_id', 'desc');

        if ($locationIds === []) {
            $builder->where('transfers.transfer_id', 0);
        } else {
            $builder->groupStart()->whereIn('transfers.source_location_id', $locationIds)
                ->orWhereIn('transfers.destination_location_id', $locationIds)->groupEnd();
        }

        return $builder->get();
    }

    public function getTransfer(int $transferId): ?object
    {
        return $this->db->table('stock_transfers')->where('transfer_id', $transferId)->get()->getRow();
    }

    public function getItems(int $transferId): array
    {
        return $this->db->table('stock_transfer_items AS transfer_items')
            ->select('transfer_items.*, items.name, items.item_number')
            ->join('items', 'items.item_id = transfer_items.item_id')
            ->where('transfer_items.transfer_id', $transferId)
            ->orderBy('items.name')
            ->get()->getResultArray();
    }

    public function approve(int $transferId, int $employeeId): bool
    {
        $this->db->transStart();
        $transfer = $this->db->query('SELECT * FROM ' . $this->db->prefixTable('stock_transfers') . ' WHERE transfer_id = ? FOR UPDATE', [$transferId])->getRow();
        if ($transfer === null || $transfer->status !== 'requested' || (int) $transfer->created_by === $employeeId) {
            $this->db->transRollback();
            return false;
        }
        $this->db->table('stock_transfers')->where('transfer_id', $transferId)->update([
            'status' => 'approved', 'approved_by' => $employeeId, 'approved_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function dispatch(int $transferId, int $employeeId): bool
    {
        $this->db->transStart();
        $transfer = $this->db->query('SELECT * FROM ' . $this->db->prefixTable('stock_transfers') . ' WHERE transfer_id = ? FOR UPDATE', [$transferId])->getRow();
        if ($transfer === null || $transfer->status !== 'approved') {
            $this->db->transRollback();
            return false;
        }

        $items = $this->db->table('stock_transfer_items')->where('transfer_id', $transferId)->get()->getResultArray();
        foreach ($items as $item) {
            $quantityRow = $this->db->query('SELECT quantity FROM ' . $this->db->prefixTable('item_quantities') . ' WHERE item_id = ? AND location_id = ? FOR UPDATE', [$item['item_id'], $transfer->source_location_id])->getRow();
            if ($quantityRow === null || (float) $quantityRow->quantity < (float) $item['requested_quantity']) {
                $this->db->transRollback();
                return false;
            }
        }

        $itemQuantity = model(Item_quantity::class);
        $inventory = model(Inventory::class);
        foreach ($items as $item) {
            $quantity = (float) $item['requested_quantity'];
            $itemQuantity->changeQuantity((int) $item['item_id'], (int) $transfer->source_location_id, -$quantity);
            $inventory->insert([
                'trans_date' => date('Y-m-d H:i:s'), 'trans_items' => $item['item_id'], 'trans_user' => $employeeId,
                'trans_location' => $transfer->source_location_id, 'trans_comment' => 'Transfer ' . $transfer->transfer_number . ' dispatch',
                'trans_inventory' => -$quantity,
            ], false);
            $this->db->table('stock_transfer_items')->where('transfer_item_id', $item['transfer_item_id'])->update(['dispatched_quantity' => $quantity]);
        }
        $this->db->table('stock_transfers')->where('transfer_id', $transferId)->update([
            'status' => 'in_transit', 'dispatched_by' => $employeeId, 'dispatched_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function receive(int $transferId, int $employeeId, array $receivedQuantities, bool $finalize, ?string $discrepancy): bool
    {
        $this->db->transStart();
        $transfer = $this->db->query('SELECT * FROM ' . $this->db->prefixTable('stock_transfers') . ' WHERE transfer_id = ? FOR UPDATE', [$transferId])->getRow();
        if ($transfer === null || ! in_array($transfer->status, ['in_transit', 'partially_received'], true)) {
            $this->db->transRollback();
            return false;
        }
        $items = $this->db->table('stock_transfer_items')->where('transfer_id', $transferId)->get()->getResultArray();
        $itemQuantity = model(Item_quantity::class);
        $inventory = model(Inventory::class);
        $hasOutstanding = false;
        foreach ($items as $item) {
            $itemId = (int) $item['item_id'];
            $quantity = $receivedQuantities[$itemId] ?? 0;
            if (! is_numeric($quantity) || ! is_finite((float) $quantity) || (float) $quantity < 0) {
                $this->db->transRollback();
                return false;
            }
            $remaining = (float) $item['dispatched_quantity'] - (float) $item['received_quantity'];
            if ((float) $quantity > $remaining) {
                $this->db->transRollback();
                return false;
            }
            if ((float) $quantity > 0) {
                $itemQuantity->changeQuantity($itemId, (int) $transfer->destination_location_id, (float) $quantity);
                $inventory->insert([
                    'trans_date' => date('Y-m-d H:i:s'), 'trans_items' => $itemId, 'trans_user' => $employeeId,
                    'trans_location' => $transfer->destination_location_id, 'trans_comment' => 'Transfer ' . $transfer->transfer_number . ' receipt',
                    'trans_inventory' => (float) $quantity,
                ], false);
                $receivedTotal = (float) $item['received_quantity'] + (float) $quantity;
                $this->db->table('stock_transfer_items')->where('transfer_item_id', $item['transfer_item_id'])->update(['received_quantity' => $receivedTotal]);
                $remaining -= (float) $quantity;
            }
            $hasOutstanding = $hasOutstanding || $remaining > 0.00001;
        }
        if ($finalize && $hasOutstanding && (strlen(trim((string) $discrepancy)) < 5 || strlen(trim((string) $discrepancy)) > 255)) {
            $this->db->transRollback();
            return false;
        }
        if ($finalize) {
            $this->db->table('stock_transfers')->where('transfer_id', $transferId)->update([
                'status' => 'received', 'received_by' => $employeeId, 'received_at' => date('Y-m-d H:i:s'),
                'discrepancy_note' => $hasOutstanding ? trim((string) $discrepancy) : null,
            ]);
        } else {
            $this->db->table('stock_transfers')->where('transfer_id', $transferId)->update([
                'status' => 'partially_received', 'received_by' => $employeeId, 'received_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function cancel(int $transferId, int $employeeId, string $reason): bool
    {
        $reason = trim($reason);
        if (strlen($reason) < 5 || strlen($reason) > 255) {
            return false;
        }
        $this->db->transStart();
        $transfer = $this->db->query('SELECT * FROM ' . $this->db->prefixTable('stock_transfers') . ' WHERE transfer_id = ? FOR UPDATE', [$transferId])->getRow();
        if ($transfer === null || ! in_array($transfer->status, ['requested', 'approved'], true)) {
            $this->db->transRollback();
            return false;
        }
        $this->db->table('stock_transfers')->where('transfer_id', $transferId)->update([
            'status' => 'cancelled', 'cancelled_by' => $employeeId, 'cancelled_at' => date('Y-m-d H:i:s'),
            'cancellation_reason' => $reason,
        ]);
        $this->db->transComplete();
        return $this->db->transStatus();
    }
}
