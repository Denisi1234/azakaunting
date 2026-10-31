<?php

namespace App\Models;

use CodeIgniter\Model;

class Quotation extends Model
{
    protected $table            = 'quotations';
    protected $primaryKey       = 'sale_id';
    protected $useAutoIncrement = false;
    protected $allowedFields    = [
        'sale_id',
        'status',
        'reference_number',
        'valid_until',
        'template_code',
        'public_token',
        'revision_number',
        'internal_notes',
        'customer_response',
        'created_by',
        'sent_at',
        'viewed_at',
        'accepted_at',
        'rejected_at',
        'converted_at',
        'converted_sale_id',
        'cancelled_at',
        'created_at',
        'updated_at',
    ];

    public function exists(int $saleId): bool
    {
        return $this->db->table($this->table)->where('sale_id', $saleId)->countAllResults() === 1;
    }

    public function getInfo(int $saleId): ?object
    {
        return $this->db->table('quotations AS q')
            ->select('q.*, s.quote_number, s.sale_time, s.sale_status, s.sale_type, s.customer_id, s.employee_id, s.comment, CONCAT_WS(" ", customer_p.first_name, customer_p.last_name) AS customer_name, customer_p.email AS customer_email, CONCAT_WS(" ", employee_p.first_name, employee_p.last_name) AS salesperson_name')
            ->join('sales AS s', 's.sale_id = q.sale_id')
            ->join('people AS customer_p', 'customer_p.person_id = s.customer_id', 'LEFT')
            ->join('people AS employee_p', 'employee_p.person_id = s.employee_id', 'LEFT')
            ->where('q.sale_id', $saleId)
            ->get()->getRow();
    }

    public function getByToken(string $token): ?object
    {
        return $this->db->table('quotations AS q')
            ->select('q.*, s.quote_number, s.sale_time, s.sale_status, s.sale_type, s.customer_id, s.employee_id, s.comment, CONCAT_WS(" ", customer_p.first_name, customer_p.last_name) AS customer_name, CONCAT_WS(" ", employee_p.first_name, employee_p.last_name) AS salesperson_name')
            ->join('sales AS s', 's.sale_id = q.sale_id')
            ->join('people AS customer_p', 'customer_p.person_id = s.customer_id', 'LEFT')
            ->join('people AS employee_p', 'employee_p.person_id = s.employee_id', 'LEFT')
            ->where('q.public_token', $token)
            ->get()->getRow();
    }

    public function getList(array $filters = []): array
    {
        $builder = $this->db->table('quotations AS q')
            ->select('q.*, s.quote_number, s.sale_time, s.sale_status, s.sale_type, s.customer_id, s.employee_id, s.comment, CONCAT_WS(" ", customer_p.first_name, customer_p.last_name) AS customer_name')
            ->join('sales AS s', 's.sale_id = q.sale_id')
            ->join('people AS customer_p', 'customer_p.person_id = s.customer_id', 'LEFT');

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $builder->where('q.status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $builder->groupStart()
                ->like('s.quote_number', $filters['search'])
                ->orLike('q.reference_number', $filters['search'])
                ->orLike('customer_p.first_name', $filters['search'])
                ->orLike('customer_p.last_name', $filters['search'])
                ->orLike('customer_p.company_name', $filters['search'])
                ->groupEnd();
        }

        return $builder->orderBy('s.sale_time', 'DESC')->get()->getResult();
    }

    public function getItems(int $saleId): array
    {
        return $this->db->table('sales_items AS si')
            ->select('si.*, i.name AS item_name, i.item_number')
            ->join('items AS i', 'i.item_id = si.item_id', 'LEFT')
            ->where('si.sale_id', $saleId)
            ->orderBy('si.line')
            ->get()->getResult();
    }

    public function getTaxes(int $saleId): array
    {
        return $this->db->table('sales_taxes')
            ->where('sale_id', $saleId)
            ->orderBy('print_sequence')
            ->get()->getResult();
    }

    public function cartMatches(int $saleId, array $cart): bool
    {
        $savedItems = $this->getItems($saleId);
        usort($cart, static fn (array $left, array $right): int => ($left['line'] ?? 0) <=> ($right['line'] ?? 0));
        $savedLines = array_map(static fn ($item): array => [
            (int) $item->item_id,
            number_format((float) $item->quantity_purchased, 4, '.', ''),
            number_format((float) $item->item_unit_price, 4, '.', ''),
            number_format((float) $item->discount, 4, '.', ''),
            (int) $item->discount_type,
        ], $savedItems);
        $currentLines = array_map(static fn (array $item): array => [
            (int) $item['item_id'],
            number_format((float) $item['quantity'], 4, '.', ''),
            number_format((float) $item['price'], 4, '.', ''),
            number_format((float) $item['discount'], 4, '.', ''),
            (int) $item['discount_type'],
        ], $cart);

        return $savedLines === $currentLines;
    }

    public function getOptions(int $saleId): array
    {
        return $this->db->table('quotation_options')->where('sale_id', $saleId)->orderBy('sort_order')->get()->getResult();
    }

    public function getRevisions(int $saleId): array
    {
        return $this->db->table('quotation_revisions')->where('sale_id', $saleId)->orderBy('revision_number', 'DESC')->get()->getResult();
    }

    public function getAttachments(int $saleId): array
    {
        return $this->db->table('quotation_attachments')->where('sale_id', $saleId)->orderBy('uploaded_at', 'DESC')->get()->getResult();
    }

    public function registerAfterSaleSave(int $saleId, ?string $referenceNumber, string $validUntil, int $employeeId): void
    {
        $now   = date('Y-m-d H:i:s');
        $quote = $this->find($saleId);
        if ($quote === null) {
            $this->db->table('quotations')->insert([
                'sale_id'          => $saleId,
                'status'           => 'draft',
                'reference_number' => $referenceNumber,
                'valid_until'      => $validUntil,
                'public_token'     => bin2hex(random_bytes(32)),
                'revision_number'  => 1,
                'created_by'       => $employeeId,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
            $this->saveSnapshot($saleId, 1, 'Created', $employeeId);

            return;
        }

        $revisionNumber = (int) $quote->revision_number + 1;
        $this->db->table('quotations')->where('sale_id', $saleId)->update([
            'status'            => 'draft',
            'reference_number'  => $referenceNumber,
            'valid_until'       => $validUntil,
            'public_token'      => bin2hex(random_bytes(32)),
            'revision_number'   => $revisionNumber,
            'customer_response' => null,
            'sent_at'           => null,
            'viewed_at'         => null,
            'updated_at'        => $now,
        ]);
        $this->saveSnapshot($saleId, $revisionNumber, 'Revised quotation', $employeeId);
    }

    public function prepareRevision(int $saleId, int $employeeId, string $note): bool
    {
        $quote = $this->find($saleId);
        if ($quote === null || in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)) {
            return false;
        }

        $exists = $this->db->table('quotation_revisions')
            ->where('sale_id', $saleId)
            ->where('revision_number', $quote->revision_number)
            ->countAllResults() > 0;
        if (! $exists) {
            $this->saveSnapshot($saleId, (int) $quote->revision_number, $note, $employeeId);
        }

        return true;
    }

    public function recordSupplementalRevision(int $saleId, int $employeeId, string $note): bool
    {
        $quote = $this->find($saleId);
        if ($quote === null || in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)) {
            return false;
        }

        $revisionNumber = (int) $quote->revision_number + 1;
        $now            = date('Y-m-d H:i:s');
        $this->db->table($this->table)->where('sale_id', $saleId)->update([
            'status'            => 'draft',
            'public_token'      => bin2hex(random_bytes(32)),
            'revision_number'   => $revisionNumber,
            'customer_response' => null,
            'sent_at'           => null,
            'viewed_at'         => null,
            'updated_at'        => $now,
        ]);
        $this->saveSnapshot($saleId, $revisionNumber, $note, $employeeId);

        return true;
    }

    public function updateMetadata(int $saleId, ?string $referenceNumber, string $validUntil, ?string $internalNotes, string $templateCode, int $employeeId): bool
    {
        if (! $this->prepareRevision($saleId, $employeeId, 'Metadata edited')) {
            return false;
        }

        $quote          = $this->find($saleId);
        $revisionNumber = (int) $quote->revision_number + 1;
        $now            = date('Y-m-d H:i:s');
        $this->db->table('quotations')->where('sale_id', $saleId)->update([
            'status'            => 'draft',
            'reference_number'  => $referenceNumber,
            'valid_until'       => $validUntil,
            'internal_notes'    => $internalNotes,
            'template_code'     => $templateCode,
            'public_token'      => bin2hex(random_bytes(32)),
            'revision_number'   => $revisionNumber,
            'customer_response' => null,
            'sent_at'           => null,
            'viewed_at'         => null,
            'updated_at'        => $now,
        ]);
        $this->saveSnapshot($saleId, $revisionNumber, 'Metadata revised', $employeeId);

        return true;
    }

    public function markSent(int $saleId): bool
    {
        $quote = $this->find($saleId);
        if ($quote === null || in_array($quote->status, ['accepted', 'converted', 'cancelled', 'expired'], true)) {
            return false;
        }

        $now    = date('Y-m-d H:i:s');
        $status = $quote->status === 'viewed' ? 'viewed' : 'sent';
        $this->db->table('quotations')->where('sale_id', $saleId)->update([
            'status'     => $status,
            'sent_at'    => $now,
            'updated_at' => $now,
        ]);

        return true;
    }

    public function markViewed(int $saleId): void
    {
        $quote = $this->find($saleId);
        if ($quote !== null && in_array($quote->status, ['sent', 'draft'], true) && $quote->valid_until >= date('Y-m-d')) {
            $this->db->table('quotations')->where('sale_id', $saleId)->update([
                'status'     => 'viewed',
                'viewed_at'  => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function respond(int $saleId, string $action, ?string $comment): bool
    {
        $quote = $this->find($saleId);
        if ($quote === null || ! in_array($quote->status, ['sent', 'viewed'], true) || $quote->valid_until < date('Y-m-d')) {
            return false;
        }

        $status = match ($action) {
            'accept'           => 'accepted',
            'reject'           => 'rejected',
            'request_revision' => 'revision_requested',
            default            => null,
        };
        if ($status === null) {
            return false;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->table('quotations')->where('sale_id', $saleId)->update([
            'status'            => $status,
            'customer_response' => $comment,
            'accepted_at'       => $action === 'accept' ? $now : null,
            'rejected_at'       => $action === 'reject' ? $now : null,
            'updated_at'        => $now,
        ]);

        return true;
    }

    public function markConverted(int $saleId, int $convertedSaleId): void
    {
        $this->db->table('quotations')->where('sale_id', $saleId)->update([
            'status'            => 'converted',
            'converted_sale_id' => $convertedSaleId,
            'converted_at'      => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }

    public function cancel(int $saleId): bool
    {
        $quote = $this->find($saleId);
        if ($quote === null || in_array($quote->status, ['accepted', 'converted', 'cancelled'], true)) {
            return false;
        }

        $this->db->table('quotations')->where('sale_id', $saleId)->update([
            'status'       => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('sales')->where('sale_id', $saleId)->update(['sale_status' => CANCELED]);

        return true;
    }

    public function expireOverdue(): void
    {
        $this->db->table('quotations')
            ->whereIn('status', ['draft', 'sent', 'viewed', 'revision_requested'])
            ->where('valid_until <', date('Y-m-d'))
            ->update(['status' => 'expired', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function getDashboard(): array
    {
        $statuses = ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'converted', 'cancelled', 'revision_requested'];
        $counts   = array_fill_keys($statuses, 0);

        foreach ($this->db->table('quotations')->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResult() as $row) {
            $counts[$row->status] = (int) $row->total;
        }

        $total                     = array_sum($counts) - $counts['cancelled'];
        $decided                   = $counts['accepted'] + $counts['rejected'] + $counts['converted'];
        $counts['conversion_rate'] = $total > 0 ? round(($counts['converted'] / $total) * 100, 1) : 0.0;
        $counts['acceptance_rate'] = $decided > 0 ? round(($counts['accepted'] / $decided) * 100, 1) : 0.0;
        $counts['monthly']         = $this->db->table('quotations AS q')
            ->select("SUBSTR(s.sale_time, 1, 7) AS month, COUNT(*) AS quotes, SUM(CASE WHEN q.status = 'converted' THEN 1 ELSE 0 END) AS converted")
            ->join('sales AS s', 's.sale_id = q.sale_id')
            ->where('s.sale_time >=', date('Y-m-01', strtotime('-11 months')))
            ->groupBy('month')->orderBy('month')->get()->getResult();
        $counts['total_value'] = 0.0;

        return $counts;
    }

    public function getTotal(int $saleId): float
    {
        $sale = model(Sale::class);
        $row  = $sale->getInfo($saleId)->getRow();

        return (float) ($row->amount_due ?? 0);
    }

    public function getRevisionSnapshot(int $revisionId): ?array
    {
        $row = $this->db->table('quotation_revisions')->where('quotation_revision_id', $revisionId)->get()->getRow();
        if ($row === null) {
            return null;
        }

        return json_decode($row->snapshot, true, 512, JSON_THROW_ON_ERROR);
    }

    private function find(int $saleId): ?object
    {
        return $this->db->table($this->table)->where('sale_id', $saleId)->get()->getRow();
    }

    private function saveSnapshot(int $saleId, int $revisionNumber, string $note, int $employeeId): void
    {
        $header   = $this->db->table('sales')->where('sale_id', $saleId)->get()->getRowArray();
        $items    = $this->db->table('sales_items')->where('sale_id', $saleId)->orderBy('line')->get()->getResultArray();
        $taxes    = $this->db->table('sales_items_taxes')->where('sale_id', $saleId)->get()->getResultArray();
        $options  = $this->db->table('quotation_options')->where('sale_id', $saleId)->orderBy('sort_order')->get()->getResultArray();
        $quote    = $this->find($saleId);
        $snapshot = [
            'header'  => $header,
            'items'   => $items,
            'taxes'   => $taxes,
            'options' => $options,
            'quote'   => $quote ? [
                'reference_number'  => $quote->reference_number,
                'valid_until'       => $quote->valid_until,
                'template_code'     => $quote->template_code,
                'internal_notes'    => $quote->internal_notes,
                'customer_response' => $quote->customer_response,
            ] : [],
        ];

        $this->db->table('quotation_revisions')->ignore(true)->insert([
            'sale_id'         => $saleId,
            'revision_number' => $revisionNumber,
            'snapshot'        => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'revision_note'   => $note,
            'created_by'      => $employeeId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }
}
