<?php

namespace App\Controllers;

use App\Models\Discount;
use CodeIgniter\HTTP\ResponseInterface;

class Discounts extends Secure_Controller
{
    private Discount $discount;

    public function __construct()
    {
        parent::__construct('discounts');

        $this->discount = model(Discount::class);
    }

    public function getIndex(): string
    {
        $data['table_headers'] = get_discounts_manage_table_headers();

        return view('discounts/manage', $data);
    }

    public function getSearch(): ResponseInterface
    {
        $search = $this->request->getGet('search');
        $limit  = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort   = $this->sanitizeSortColumn(discount_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'discount_id');
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $discounts = $this->discount->get_all();
        $totalRows = $discounts->getNumRows();
        $dataRows  = [];

        foreach ($discounts->getResult() as $discount) {
            $dataRows[] = get_discounts_data_row($discount);
        }

        return $this->response->setJSON(['total' => $totalRows, 'rows' => $dataRows]);
    }

    public function getView(int $discountId = NEW_ENTRY): string
    {
        $data = [];

        $data['discounts_info'] = $this->discount->get_info($discountId);

        if ($discountId === NEW_ENTRY) {
            $data['discounts_info']->discount_code  = '';
            $data['discounts_info']->discount_name  = '';
            $data['discounts_info']->discount_type  = 'percentage';
            $data['discounts_info']->discount_value = 0;
            $data['discounts_info']->min_purchase   = 0;
            $data['discounts_info']->max_uses       = null;
            $data['discounts_info']->start_date     = date('Y-m-d');
            $data['discounts_info']->end_date       = '';
            $data['discounts_info']->applies_to     = 'all';
            $data['discounts_info']->is_active      = 1;
        }

        return view('discounts/form', $data);
    }

    public function getRow(int $rowId): ResponseInterface
    {
        $discountInfo = $this->discount->get_info($rowId);
        $dataRow      = get_discounts_data_row($discountInfo);

        return $this->response->setJSON($dataRow);
    }

    public function postSave(int $discountId = NEW_ENTRY): ResponseInterface
    {
        $discountData = [
            'discount_code'  => strtoupper($this->request->getPost('discount_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS)),
            'discount_name'  => $this->request->getPost('discount_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'discount_type'  => $this->request->getPost('discount_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'discount_value' => parse_decimals($this->request->getPost('discount_value')),
            'min_purchase'   => parse_decimals($this->request->getPost('min_purchase')),
            'max_uses'       => $this->request->getPost('max_uses') === '' ? null : $this->request->getPost('max_uses', FILTER_SANITIZE_NUMBER_INT),
            'start_date'     => $this->request->getPost('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'       => $this->request->getPost('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS) === '' ? null : $this->request->getPost('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'applies_to'     => $this->request->getPost('applies_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'is_active'      => $this->request->getPost('is_active') !== null ? 1 : 0,
            'deleted'        => $this->request->getPost('deleted') !== null,
        ];

        if ($this->discount->save_value($discountData, $discountId)) {
            if ($discountId === NEW_ENTRY) {
                return $this->response->setJSON(['success' => true, 'message' => lang('Discounts.successful_adding'), 'id' => $discountData['discount_id']]);
            }

            return $this->response->setJSON(['success' => true, 'message' => lang('Discounts.successful_updating'), 'id' => $discountId]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Discounts.error_adding_updating'), 'id' => NEW_ENTRY]);
    }

    public function postDelete(): ResponseInterface
    {
        $discountsToDelete = $this->request->getPost('ids', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($this->discount->delete_list($discountsToDelete)) {
            return $this->response->setJSON(['success' => true, 'message' => lang('Discounts.successful_deleted') . ' ' . count($discountsToDelete) . ' ' . lang('Discounts.one_or_multiple'), 'ids' => $discountsToDelete]);
        }

        return $this->response->setJSON(['success' => false, 'message' => lang('Discounts.cannot_be_deleted'), 'ids' => $discountsToDelete]);
    }

    public function postValidate(): ResponseInterface
    {
        $code   = $this->request->getPost('code', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $amount = (float) $this->request->getPost('amount');

        $discount = $this->discount->find_by_code($code);

        if ($discount === null) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Discounts.invalid_code')]);
        }

        if (! $this->discount->is_valid($discount, $amount)) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Discounts.expired_or_invalid')]);
        }

        $discountAmount = $this->discount->calculate_discount($discount, $amount);

        return $this->response->setJSON([
            'success'         => true,
            'discount_id'     => $discount->discount_id,
            'discount_name'   => $discount->discount_name,
            'discount_type'   => $discount->discount_type,
            'discount_value'  => $discount->discount_value,
            'discount_amount' => $discountAmount,
            'message'         => lang('Discounts.valid_code'),
        ]);
    }
}
