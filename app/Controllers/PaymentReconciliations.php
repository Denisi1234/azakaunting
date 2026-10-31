<?php

namespace App\Controllers;

use App\Models\PaymentReconciliation;
use App\Models\Stock_location;
use CodeIgniter\HTTP\ResponseInterface;
use DateTime;

class PaymentReconciliations extends Secure_Controller
{
    private PaymentReconciliation $reconciliation;
    private Stock_location $stockLocation;

    public function __construct()
    {
        parent::__construct('reports', 'reports_payments');

        $this->reconciliation = model(PaymentReconciliation::class);
        $this->stockLocation = model(Stock_location::class);
    }

    public function getIndex(): string
    {
        $businessDate = (string) $this->request->getGet('business_date');
        if (! $this->isValidDate($businessDate)) {
            $businessDate = date('Y-m-d');
        }

        $locations = $this->stockLocation->get_allowed_locations('sales');

        return view('payment_reconciliation/index', [
            'business_date' => $businessDate,
            'locations'     => $locations,
            'rows'          => $this->reconciliation->getDailySummary($businessDate, array_map('intval', array_keys($locations))),
            'success'       => $this->session->getFlashdata('success'),
            'error'         => $this->session->getFlashdata('error'),
        ]);
    }

    /**
     * @param int $data_item_id
     * @return false
     */
    public function postSave(int $data_item_id = -1): ResponseInterface
    {
        $businessDate = (string) $this->request->getPost('business_date');
        $locationId = (int) $this->request->getPost('location_id');
        $paymentType = (string) $this->request->getPost('payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $collectedRaw = trim((string) $this->request->getPost('collected_amount'));
        $collectedAmount = parse_decimals($collectedRaw);
        $note = trim((string) $this->request->getPost('note', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $paymentTypes = array_values(array_diff(array_values(get_payment_options()), [lang('Sales.due')]));

        if (
            ! $this->isValidDate($businessDate)
            || ! $this->stockLocation->is_allowed_location($locationId, 'sales')
            || ! in_array($paymentType, $paymentTypes, true)
            || $collectedRaw === ''
            || $collectedAmount === false
            || $collectedAmount < 0
        ) {
            $this->session->setFlashdata('error', lang('Reports.reconciliation_invalid'));

            return redirect()->to('payment_reconciliation?business_date=' . rawurlencode($businessDate));
        }

        $expectedRows = $this->reconciliation->getDailySummary($businessDate, [$locationId]);
        $expectedAmount = null;
        foreach ($expectedRows as $row) {
            if ($row['payment_type'] === $paymentType) {
                $expectedAmount = (float) $row['expected_amount'];
                break;
            }
        }

        if ($expectedAmount === null) {
            $this->session->setFlashdata('error', lang('Reports.reconciliation_no_payments'));

            return redirect()->to('payment_reconciliation?business_date=' . rawurlencode($businessDate));
        }

        $saved = $this->reconciliation->saveCollectedAmount(
            $businessDate,
            $locationId,
            $paymentType,
            $expectedAmount,
            (float) $collectedAmount,
            $note === '' ? null : $note,
            (int) $this->session->get('person_id')
        );

        $this->session->setFlashdata($saved ? 'success' : 'error', lang($saved ? 'Reports.reconciliation_saved' : 'Reports.reconciliation_save_failed'));

        return redirect()->to('payment_reconciliation?business_date=' . rawurlencode($businessDate));
    }

    private function isValidDate(string $date): bool
    {
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);

        return $dateObject !== false && $dateObject->format('Y-m-d') === $date;
    }
}
