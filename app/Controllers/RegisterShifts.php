<?php

namespace App\Controllers;

use App\Models\RegisterShift;
use App\Models\Stock_location;
use CodeIgniter\HTTP\ResponseInterface;

class RegisterShifts extends Secure_Controller
{
    private RegisterShift $registerShift;
    private Stock_location $stockLocation;

    public function __construct()
    {
        parent::__construct('sales');

        $this->registerShift = model(RegisterShift::class);
        $this->stockLocation = model(Stock_location::class);
    }

    public function getIndex(): string
    {
        $employeeId = (int) $this->session->get('person_id');
        $locations = $this->stockLocation->get_allowed_locations('sales');
        $canReviewAll = $this->employee->has_grant('reports_sales', $employeeId);
        $locationId = (int) ($this->session->get('sales_location') ?? $this->stockLocation->getDefaultLocationId('sales'));
        $activeShift = $locationId > 0 ? $this->registerShift->getActive($employeeId, $locationId) : null;
        $data = [
            'locations' => $locations,
            'location_id' => $locationId,
            'shift' => $activeShift === null ? null : $this->registerShift->getShift((int) $activeShift['register_shift_id']),
            'movements' => $activeShift === null ? [] : $this->registerShift->getMovements((int) $activeShift['register_shift_id']),
            'expected_cash' => $activeShift === null ? 0 : $this->registerShift->getExpectedCash((int) $activeShift['register_shift_id']),
            'history' => $this->registerShift->getHistory(
                $canReviewAll ? null : $employeeId,
                array_map('intval', array_keys($locations))
            ),
            'can_review_all' => $canReviewAll,
            'error' => $this->session->getFlashdata('error'),
            'success' => $this->session->getFlashdata('success'),
        ];

        return view('register_shifts/index', $data);
    }

    public function postOpen(): ResponseInterface
    {
        $employeeId = (int) $this->session->get('person_id');
        $locationId = (int) $this->request->getPost('location_id');
        $openingCashRaw = trim((string) $this->request->getPost('opening_cash'));
        $openingCash = parse_decimals($openingCashRaw);
        $note = trim((string) $this->request->getPost('opening_note', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if (! $this->stockLocation->is_allowed_location($locationId, 'sales') || $openingCashRaw === '' || $openingCash === false || $openingCash < 0) {
            $this->session->setFlashdata('error', lang('Sales.shift_open_invalid'));

            return redirect()->to('register_shifts');
        }

        if ($this->registerShift->openShift($employeeId, $locationId, $openingCash, $note === '' ? null : $note) === false) {
            $this->session->setFlashdata('error', lang('Sales.shift_already_open'));

            return redirect()->to('register_shifts');
        }

        $this->session->set('sales_location', $locationId);
        $this->session->setFlashdata('success', lang('Sales.shift_opened'));

        return redirect()->to('register_shifts');
    }

    public function postClose(): ResponseInterface
    {
        $employeeId = (int) $this->session->get('person_id');
        $locationId = (int) ($this->session->get('sales_location') ?? $this->stockLocation->getDefaultLocationId('sales'));
        $activeShift = $this->registerShift->getActive($employeeId, $locationId);
        $closingCashRaw = trim((string) $this->request->getPost('closing_cash'));
        $closingCash = parse_decimals($closingCashRaw);
        $note = trim((string) $this->request->getPost('closing_note', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if ($activeShift === null || $closingCashRaw === '' || $closingCash === false || $closingCash < 0) {
            $this->session->setFlashdata('error', lang('Sales.shift_close_invalid'));

            return redirect()->to('register_shifts');
        }

        if (! $this->registerShift->closeShift((int) $activeShift['register_shift_id'], $employeeId, $closingCash, $note === '' ? null : $note)) {
            $this->session->setFlashdata('error', lang('Sales.shift_close_invalid'));

            return redirect()->to('register_shifts');
        }
        $this->session->setFlashdata('success', lang('Sales.shift_closed'));

        return redirect()->to('register_shifts');
    }

    public function postMovement(): ResponseInterface
    {
        $employeeId = (int) $this->session->get('person_id');
        $locationId = (int) ($this->session->get('sales_location') ?? $this->stockLocation->getDefaultLocationId('sales'));
        $activeShift = $this->registerShift->getActive($employeeId, $locationId);
        $amount = parse_decimals(trim((string) $this->request->getPost('amount')));
        $type = (string) $this->request->getPost('movement_type');
        $reason = trim((string) $this->request->getPost('reason', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

        if (
            $activeShift === null
            || $amount === false
            || ! $this->registerShift->addMovement((int) $activeShift['register_shift_id'], $employeeId, $type, $amount, $reason)
        ) {
            $this->session->setFlashdata('error', lang('Sales.shift_movement_invalid'));

            return redirect()->to('register_shifts');
        }

        $this->session->setFlashdata('success', lang('Sales.shift_movement_saved'));

        return redirect()->to('register_shifts');
    }
}
