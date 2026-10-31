<?php

namespace App\Controllers;

use App\Models\StockTransfer;
use App\Models\Stock_location;
use CodeIgniter\HTTP\RedirectResponse;

class StockTransfers extends Secure_Controller
{
    private StockTransfer $stockTransfer;
    private Stock_location $stockLocation;

    public function __construct()
    {
        parent::__construct('receivings');
        $this->stockTransfer = model(StockTransfer::class);
        $this->stockLocation = model(Stock_location::class);
    }

    public function getIndex(): string
    {
        $locationIds = array_map('intval', array_keys($this->stockLocation->get_allowed_locations('receivings')));
        $transfers = [];
        foreach ($this->stockTransfer->getAll($locationIds)->getResultArray() as $transfer) {
            $transfer['items'] = $this->stockTransfer->getItems((int) $transfer['transfer_id']);
            $transfer['canApprove'] = $this->employee->has_grant('employees', $this->session->get('person_id'))
                && (int) $transfer['created_by'] !== (int) $this->session->get('person_id')
                && $this->stockLocation->is_allowed_location((int) $transfer['source_location_id'], 'receivings')
                && $this->stockLocation->is_allowed_location((int) $transfer['destination_location_id'], 'receivings');
            $transfer['canDispatch'] = $this->stockLocation->is_allowed_location((int) $transfer['source_location_id'], 'receivings');
            $transfer['canReceive'] = $this->stockLocation->is_allowed_location((int) $transfer['destination_location_id'], 'receivings');
            $transfers[] = $transfer;
        }

        return view('receivings/stock_transfers', [
            'transfers' => $transfers,
            'success' => $this->session->getFlashdata('success'),
            'error' => $this->session->getFlashdata('error'),
        ]);
    }

    public function postApprove(int $transferId): RedirectResponse
    {
        $transfer = $this->stockTransfer->getTransfer($transferId);
        $employeeId = (int) $this->session->get('person_id');
        if (! $this->employee->has_grant('employees', $employeeId) || $transfer === null
            || ! $this->stockLocation->is_allowed_location((int) $transfer->source_location_id, 'receivings')
            || ! $this->stockLocation->is_allowed_location((int) $transfer->destination_location_id, 'receivings')
            || ! $this->stockTransfer->approve($transferId, $employeeId)) {
            return $this->failure();
        }
        $this->session->setFlashdata('success', lang('Receivings.transfer_approved'));
        return redirect()->to('stock_transfers');
    }

    public function postDispatch(int $transferId): RedirectResponse
    {
        $transfer = $this->stockTransfer->getTransfer($transferId);
        if ($transfer === null || ! $this->stockLocation->is_allowed_location((int) $transfer->source_location_id, 'receivings')
            || ! $this->stockTransfer->dispatch($transferId, (int) $this->session->get('person_id'))) {
            return $this->failure();
        }
        $this->session->setFlashdata('success', lang('Receivings.transfer_dispatched'));
        return redirect()->to('stock_transfers');
    }

    public function postReceive(int $transferId): RedirectResponse
    {
        $transfer = $this->stockTransfer->getTransfer($transferId);
        $quantities = $this->request->getPost('received_quantity');
        $finalize = $this->request->getPost('finalize') === '1';
        $discrepancyInput = $this->request->getPost('discrepancy_note', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $discrepancy = is_string($discrepancyInput) ? trim($discrepancyInput) : null;
        if ($transfer === null || ! is_array($quantities)
            || ! $this->stockLocation->is_allowed_location((int) $transfer->destination_location_id, 'receivings')
            || ! $this->stockTransfer->receive($transferId, (int) $this->session->get('person_id'), $quantities, $finalize, $discrepancy)) {
            return $this->failure();
        }
        $this->session->setFlashdata('success', lang('Receivings.transfer_received'));
        return redirect()->to('stock_transfers');
    }

    public function postCancel(int $transferId): RedirectResponse
    {
        $transfer = $this->stockTransfer->getTransfer($transferId);
        $reasonInput = $this->request->getPost('reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $reason = is_string($reasonInput) ? trim($reasonInput) : '';
        if ($transfer === null || ((int) $transfer->created_by !== (int) $this->session->get('person_id')
                && ! $this->employee->has_grant('employees', $this->session->get('person_id')))
            || ! $this->stockLocation->is_allowed_location((int) $transfer->source_location_id, 'receivings')
            || ! $this->stockTransfer->cancel($transferId, (int) $this->session->get('person_id'), $reason)) {
            return $this->failure();
        }
        $this->session->setFlashdata('success', lang('Receivings.transfer_cancelled'));
        return redirect()->to('stock_transfers');
    }

    private function failure(): RedirectResponse
    {
        $this->session->setFlashdata('error', lang('Receivings.transfer_action_failed'));
        return redirect()->to('stock_transfers');
    }
}
