<?php

namespace App\Controllers;

use App\Models\Quotation;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class Quotation_portal extends BaseController
{
    private Quotation $quotation;
    private BaseConnection $db;

    public function __construct()
    {
        $this->quotation = model(Quotation::class);
        $this->db        = Database::connect();
    }

    public function getView(string $token): ResponseInterface|string
    {
        $quote = $this->quotation->getByToken($token);
        if ($quote === null) {
            return view('quotations/public_unavailable');
        }
        if ($quote->valid_until < date('Y-m-d') && in_array($quote->status, ['draft', 'sent', 'viewed', 'revision_requested'], true)) {
            $this->quotation->expireOverdue();
            $quote = $this->quotation->getByToken($token);
        } else {
            $this->quotation->markViewed((int) $quote->sale_id);
            $quote = $this->quotation->getByToken($token);
        }

        return view('quotations/public_view', [
            'quote'       => $quote,
            'items'       => $this->quotation->getItems((int) $quote->sale_id),
            'taxes'       => $this->quotation->getTaxes((int) $quote->sale_id),
            'options'     => $this->quotation->getOptions((int) $quote->sale_id),
            'attachments' => $this->quotation->getAttachments((int) $quote->sale_id),
            'total'       => $this->quotation->getTotal((int) $quote->sale_id),
            'config'      => config('OSPOS')->settings,
        ]);
    }

    public function postRespond(string $token): RedirectResponse
    {
        $quote = $this->quotation->getByToken($token);
        if ($quote === null) {
            return redirect()->to('quotation/portal/' . rawurlencode($token));
        }

        $action  = (string) $this->request->getPost('action', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $comment = trim((string) $this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if (! in_array($action, ['accept', 'reject', 'request_revision'], true)) {
            return redirect()->back()->with('error', lang('Quotations.invalid_action'));
        }
        if ($action !== 'accept' && $comment === '') {
            return redirect()->back()->withInput()->with('error', lang('Quotations.comment_required'));
        }

        $responded = $this->quotation->respond((int) $quote->sale_id, $action, $comment ?: null);

        return redirect()->to('quotation/portal/' . rawurlencode($token))->with(
            $responded ? 'message' : 'error',
            lang($responded ? 'Quotations.response_received' : 'Quotations.response_unavailable'),
        );
    }

    public function getPdf(string $token): ResponseInterface|string
    {
        $quote = $this->quotation->getByToken($token);
        if ($quote === null) {
            return view('quotations/public_unavailable');
        }

        helper(['dompdf', 'file']);
        $data = [
            'quote'   => $quote,
            'items'   => $this->quotation->getItems((int) $quote->sale_id),
            'taxes'   => $this->quotation->getTaxes((int) $quote->sale_id),
            'options' => $this->quotation->getOptions((int) $quote->sale_id),
            'total'   => $this->quotation->getTotal((int) $quote->sale_id),
            'config'  => config('OSPOS')->settings,
        ];

        return $this->response->download('Quotation-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $quote->quote_number) . '.pdf', create_pdf(view('quotations/document', $data)));
    }

    public function getAttachment(string $token, int $attachmentId): ResponseInterface|string
    {
        $quote      = $this->quotation->getByToken($token);
        $attachment = $this->db->table('quotation_attachments')->where('quotation_attachment_id', $attachmentId)->get()->getRow();
        if ($quote === null || $attachment === null || (int) $attachment->sale_id !== (int) $quote->sale_id) {
            return view('quotations/public_unavailable');
        }
        $path = WRITEPATH . 'uploads/quotations/' . (int) $quote->sale_id . '/' . basename($attachment->stored_name);
        if (! is_file($path)) {
            return view('quotations/public_unavailable');
        }

        return $this->response->download($path, null)->setFileName(basename($attachment->file_name));
    }
}
