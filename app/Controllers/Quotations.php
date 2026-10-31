<?php

namespace App\Controllers;

use App\Libraries\Sale_lib;
use App\Models\Employee;
use App\Models\Quotation;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

class Quotations extends Secure_Controller
{
    private Quotation $quotation;
    private BaseConnection $db;
    protected Employee $employee;

    public function __construct()
    {
        parent::__construct('quotations');
        $this->quotation = model(Quotation::class);
        $this->employee  = model(Employee::class);
        $this->db        = Database::connect();
    }

    public function getIndex(): string
    {
        $this->quotation->expireOverdue();
        $status = $this->request->getGet('status', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'all';
        $search = trim((string) $this->request->getGet('search', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $quotes = $this->quotation->getList(['status' => $status, 'search' => $search]);

        foreach ($quotes as $quote) {
            $quote->amount = $this->quotation->getTotal((int) $quote->sale_id);
        }

        $data['dashboard']                = $this->quotation->getDashboard();
        $allQuotes                        = $this->quotation->getList();
        $data['dashboard']['total_value'] = array_reduce(
            $allQuotes,
            fn (float $total, object $quote): float => $total + $this->quotation->getTotal((int) $quote->sale_id),
            0.0,
        );
        $data['quotes'] = $quotes;
        $data['status'] = $status;
        $data['search'] = $search;

        return view('quotations/manage', $data);
    }

    public function getView(int $saleId): ResponseInterface|string
    {
        $quote = $this->quotation->getInfo($saleId);
        if ($quote === null) {
            return redirect()->to('quotations')->with('error', lang('Quotations.not_found'));
        }

        $data['quote']       = $quote;
        $data['items']       = $this->quotation->getItems($saleId);
        $data['taxes']       = $this->quotation->getTaxes($saleId);
        $data['options']     = $this->quotation->getOptions($saleId);
        $data['revisions']   = $this->quotation->getRevisions($saleId);
        $data['attachments'] = $this->quotation->getAttachments($saleId);
        $data['total']       = $this->quotation->getTotal($saleId);

        return view('quotations/view', $data);
    }

    public function postUpdate(int $saleId): RedirectResponse
    {
        $quote = $this->quotation->getInfo($saleId);
        if ($quote === null) {
            return redirect()->to('quotations')->with('error', lang('Quotations.not_found'));
        }

        $validUntil = $this->request->getPost('valid_until', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (! $this->validate([
            'valid_until'      => 'required|valid_date[Y-m-d]',
            'reference_number' => 'permit_empty|max_length[80]',
            'internal_notes'   => 'permit_empty|max_length[5000]',
            'template_code'    => 'required|in_list[modern,classic]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }
        if ($quote->status === 'accepted' || $quote->status === 'converted' || $quote->status === 'cancelled') {
            return redirect()->back()->with('error', lang('Quotations.locked'));
        }
        if ($validUntil < date('Y-m-d')) {
            return redirect()->back()->withInput()->with('error', lang('Quotations.expiry_must_be_future'));
        }

        $saved = $this->quotation->updateMetadata(
            $saleId,
            trim((string) $this->request->getPost('reference_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) ?: null,
            $validUntil,
            trim((string) $this->request->getPost('internal_notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) ?: null,
            $this->request->getPost('template_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            (int) session('person_id'),
        );

        return redirect()->to("quotations/view/{$saleId}")->with(
            $saved ? 'message' : 'error',
            lang($saved ? 'Quotations.saved_revision' : 'Quotations.locked'),
        );
    }

    public function postAddOption(int $saleId): RedirectResponse
    {
        if (! $this->isEditable($saleId)) {
            return redirect()->back()->with('error', lang('Quotations.locked'));
        }
        if (! $this->validate([
            'description' => 'required|max_length[255]',
            'quantity'    => 'required|decimal|greater_than[0]',
            'unit_price'  => 'required|decimal|greater_than_equal_to[0]',
            'notes'       => 'permit_empty|max_length[2000]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $employeeId = (int) session('person_id');
        if (! $this->quotation->prepareRevision($saleId, $employeeId, 'Optional items edited')) {
            return redirect()->back()->with('error', lang('Quotations.locked'));
        }
        $this->db->transStart();
        $this->db->table('quotation_options')->insert([
            'sale_id'     => $saleId,
            'description' => $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'quantity'    => parse_decimals($this->request->getPost('quantity')),
            'unit_price'  => parse_decimals($this->request->getPost('unit_price')),
            'notes'       => $this->request->getPost('notes', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'sort_order'  => (int) $this->db->table('quotation_options')->where('sale_id', $saleId)->countAllResults(),
        ]);
        $this->quotation->recordSupplementalRevision($saleId, $employeeId, 'Optional item added');
        $this->db->transComplete();

        return redirect()->to("quotations/view/{$saleId}")->with(
            $this->db->transStatus() ? 'message' : 'error',
            lang($this->db->transStatus() ? 'Quotations.option_added' : 'Quotations.upload_failed'),
        );
    }

    public function postDeleteOption(int $saleId, int $optionId): RedirectResponse
    {
        if ($this->isEditable($saleId)) {
            $employeeId = (int) session('person_id');
            if (! $this->quotation->prepareRevision($saleId, $employeeId, 'Optional items edited')) {
                return redirect()->back()->with('error', lang('Quotations.locked'));
            }
            $this->db->transStart();
            $this->db->table('quotation_options')->where('sale_id', $saleId)->where('quotation_option_id', $optionId)->delete();
            $this->quotation->recordSupplementalRevision($saleId, $employeeId, 'Optional item removed');
            $this->db->transComplete();
        }

        return redirect()->to("quotations/view/{$saleId}");
    }

    public function postUpload(int $saleId): RedirectResponse
    {
        if (! $this->isEditable($saleId)) {
            return redirect()->back()->with('error', lang('Quotations.locked'));
        }
        $employeeId = (int) session('person_id');
        if (! $this->quotation->prepareRevision($saleId, $employeeId, 'Attachment list edited')) {
            return redirect()->back()->with('error', lang('Quotations.locked'));
        }
        $file         = $this->request->getFile('attachment');
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        if ($file === null || ! $file->isValid() || $file->hasMoved() || $file->getSize() > 10 * 1024 * 1024 || ! in_array($file->getMimeType(), $allowedMimes, true)) {
            return redirect()->back()->with('error', lang('Quotations.invalid_attachment'));
        }

        $directory = WRITEPATH . 'uploads/quotations/' . $saleId;
        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            return redirect()->back()->with('error', lang('Quotations.upload_failed'));
        }
        $storedName   = bin2hex(random_bytes(20)) . '.' . $file->guessExtension();
        $fileSize     = $file->getSize();
        $mimeType     = $file->getMimeType();
        $originalName = $file->getClientName();
        if (! $file->move($directory, $storedName)) {
            return redirect()->back()->with('error', lang('Quotations.upload_failed'));
        }

        $this->db->table('quotation_attachments')->insert([
            'sale_id'     => $saleId,
            'file_name'   => mb_substr($originalName, 0, 255),
            'stored_name' => $storedName,
            'mime_type'   => $mimeType,
            'file_size'   => $fileSize,
            'uploaded_by' => $employeeId,
            'uploaded_at' => date('Y-m-d H:i:s'),
        ]);
        $this->quotation->recordSupplementalRevision($saleId, $employeeId, 'Attachment added');

        return redirect()->to("quotations/view/{$saleId}")->with('message', lang('Quotations.attachment_uploaded'));
    }

    public function getAttachment(int $attachmentId): RedirectResponse|ResponseInterface
    {
        $attachment = $this->db->table('quotation_attachments')->where('quotation_attachment_id', $attachmentId)->get()->getRow();
        if ($attachment === null) {
            return redirect()->to('quotations')->with('error', lang('Quotations.not_found'));
        }
        $path = WRITEPATH . 'uploads/quotations/' . (int) $attachment->sale_id . '/' . basename($attachment->stored_name);
        if (! is_file($path)) {
            return redirect()->to("quotations/view/{$attachment->sale_id}")->with('error', lang('Quotations.attachment_missing'));
        }

        return $this->response->download($path, null)->setFileName(basename($attachment->file_name));
    }

    public function postDuplicate(int $saleId): RedirectResponse
    {
        if ($this->quotation->getInfo($saleId) === null) {
            return redirect()->to('quotations')->with('error', lang('Quotations.not_found'));
        }

        $saleLib = new Sale_lib();
        $saleLib->clear_all();
        $saleLib->copy_entire_sale($saleId);
        $saleLib->clear_quote_number();
        $saleLib->set_sale_type(SALE_TYPE_QUOTE);
        $saleLib->set_mode('sale_quote');
        session()->set('sale_id', NEW_ENTRY);
        $saleLib->set_suspended_id(NEW_ENTRY);
        session()->remove('quotation_reference_number');
        session()->remove('quotation_valid_until');

        return redirect()->to('sales')->with('message', lang('Quotations.duplicate_ready'));
    }

    public function postConvert(int $saleId): RedirectResponse
    {
        $quote = $this->quotation->getInfo($saleId);
        if ($quote === null || $quote->status !== 'accepted') {
            return redirect()->to("quotations/view/{$saleId}")->with('error', lang('Quotations.must_be_accepted'));
        }

        $saleLib = new Sale_lib();
        $saleLib->clear_all();
        $saleLib->copy_entire_sale($saleId);
        $saleLib->clear_quote_number();
        $mode = config('OSPOS')->settings['invoice_enable'] ? 'sale_invoice' : 'sale';
        $saleLib->set_mode($mode);
        session()->set('sale_id', $saleId);
        $saleLib->set_suspended_id($saleId);

        return redirect()->to('sales')->with('message', lang('Quotations.convert_checkout'));
    }

    public function postCancel(int $saleId): RedirectResponse
    {
        return redirect()->to('quotations/view/' . $saleId)->with(
            $this->quotation->cancel($saleId) ? 'message' : 'error',
            lang('Quotations.cancel_result'),
        );
    }

    public function getDownloadPdf(int $saleId): RedirectResponse|ResponseInterface
    {
        $quote = $this->quotation->getInfo($saleId);
        if ($quote === null) {
            return redirect()->to('quotations')->with('error', lang('Quotations.not_found'));
        }

        helper(['dompdf', 'file']);
        $data = $this->getDocumentData($saleId, $quote);
        $html = view('quotations/document', $data);

        return $this->response->download('Quotation-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $quote->quote_number) . '.pdf', create_pdf($html));
    }

    private function getDocumentData(int $saleId, object $quote): array
    {
        $data = [
            'quote'   => $quote,
            'items'   => $this->quotation->getItems($saleId),
            'taxes'   => $this->quotation->getTaxes($saleId),
            'options' => $this->quotation->getOptions($saleId),
            'total'   => $this->quotation->getTotal($saleId),
        ];
        $data['config'] = config('OSPOS')->settings;

        return $data;
    }

    private function isEditable(int $saleId): bool
    {
        $quote = $this->quotation->getInfo($saleId);

        return $quote !== null && ! in_array($quote->status, ['accepted', 'converted', 'cancelled'], true);
    }
}
