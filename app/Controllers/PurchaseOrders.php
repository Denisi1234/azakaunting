<?php

namespace App\Controllers;

use App\Libraries\Receiving_lib;
use App\Models\PurchaseOrder;
use App\Models\Stock_location;
use CodeIgniter\HTTP\RedirectResponse;

class PurchaseOrders extends Secure_Controller
{
    private PurchaseOrder $purchaseOrder;
    private Stock_location $stockLocation;
    private Receiving_lib $receivingLib;

    public function __construct()
    {
        parent::__construct('receivings');
        $this->purchaseOrder = model(PurchaseOrder::class);
        $this->stockLocation = model(Stock_location::class);
        $this->receivingLib = new Receiving_lib();
    }

    public function getIndex(): string
    {
        $locationIds = array_map('intval', array_keys($this->stockLocation->get_allowed_locations('receivings')));
        $orders = [];
        foreach ($this->purchaseOrder->getAll($locationIds)->getResultArray() as $order) {
            $order['items'] = $this->purchaseOrder->getItems((int) $order['purchase_order_id']);
            $orders[] = $order;
        }

        return view('receivings/purchase_orders', [
            'orders' => $orders,
            'canApprove' => $this->employee->has_grant('employees', $this->session->get('person_id')),
            'success' => $this->session->getFlashdata('success'),
            'error' => $this->session->getFlashdata('error'),
        ]);
    }

    public function postApprove(int $purchaseOrderId): RedirectResponse
    {
        $employeeId = (int) $this->session->get('person_id');
        $reason = trim((string) $this->request->getPost('reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $order = $this->purchaseOrder->getOrder($purchaseOrderId);

        if (! $this->employee->has_grant('employees', $employeeId)
            || $order === null
            || ! $this->stockLocation->is_allowed_location((int) $order->location_id, 'receivings')
            || ! $this->purchaseOrder->approve($purchaseOrderId, $employeeId, $reason)) {
            $this->session->setFlashdata('error', lang('Receivings.purchase_order_approval_failed'));

            return redirect()->to('purchase_orders');
        }

        $this->session->setFlashdata('success', lang('Receivings.purchase_order_approved'));

        return redirect()->to('purchase_orders');
    }

    public function postReceive(int $purchaseOrderId): RedirectResponse
    {
        $order = $this->purchaseOrder->getOrder($purchaseOrderId);
        if ($order === null
            || ! in_array($order->status, ['ordered', 'partially_received'], true)
            || ! $this->stockLocation->is_allowed_location((int) $order->location_id, 'receivings')) {
            $this->session->setFlashdata('error', lang('Receivings.purchase_order_receipt_invalid'));

            return redirect()->to('purchase_orders');
        }

        $this->receivingLib->clear_all();
        $this->receivingLib->set_supplier((int) $order->supplier_id);
        $this->receivingLib->set_mode('receive');
        $this->receivingLib->set_stock_source((int) $order->location_id);
        $this->receivingLib->setPurchaseOrderId($purchaseOrderId);
        $this->receivingLib->set_reference($order->order_number);
        $this->receivingLib->set_comment(trim((string) $order->comment . ' ' . lang('Receivings.purchase_order_reference') . ': ' . $order->order_number));

        foreach ($this->purchaseOrder->getItems($purchaseOrderId) as $item) {
            $remainingQuantity = (float) $item['ordered_quantity'] - (float) $item['received_quantity'];
            if ($remainingQuantity <= 0) {
                continue;
            }

            $this->receivingLib->add_item(
                (string) $item['item_id'],
                (int) $remainingQuantity,
                (int) $order->location_id,
                0,
                PERCENT,
                (float) $item['unit_cost'],
                null,
                null,
                1
            );
        }

        return redirect()->to('receivings');
    }

    public function postCancel(int $purchaseOrderId): RedirectResponse
    {
        $employeeId = (int) $this->session->get('person_id');
        $reason = trim((string) $this->request->getPost('reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $order = $this->purchaseOrder->getOrder($purchaseOrderId);
        $canCancel = $this->employee->has_grant('employees', $employeeId)
            || ($order !== null && (int) $order->created_by === $employeeId);

        if (! $canCancel
            || $order === null
            || ! $this->stockLocation->is_allowed_location((int) $order->location_id, 'receivings')
            || ! $this->purchaseOrder->cancel($purchaseOrderId, $employeeId, $reason)) {
            $this->session->setFlashdata('error', lang('Receivings.purchase_order_cancel_failed'));

            return redirect()->to('purchase_orders');
        }

        $this->session->setFlashdata('success', lang('Receivings.purchase_order_cancelled'));

        return redirect()->to('purchase_orders');
    }
}
