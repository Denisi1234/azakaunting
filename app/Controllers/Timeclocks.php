<?php

namespace App\Controllers;

use App\Models\Timeclock;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTime;

class Timeclocks extends Secure_Controller
{
    private Timeclock $timeclock;

    public function __construct()
    {
        parent::__construct('timeclocks');

        $this->timeclock = model(Timeclock::class);
    }

    public function getIndex(): string
    {
        $canManage             = $this->canManage();
        $data['table_headers'] = get_timeclocks_manage_table_headers($canManage);
        $data['can_manage']    = $canManage;
        $data['filters']       = ['open' => lang('Timeclocks.open')];
        $data                  = array_merge($data, restoreTableFilters($this->request));

        return view('timeclocks/manage', $data);
    }

    /**
     * Returns timeclock table data rows. This will be called with AJAX.
     */
    public function getSearch(): ResponseInterface
    {
        $search = $this->request->getGet('search');
        $limit  = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $limit  = $limit === null ? null : (int) $limit;
        $offset = $offset === null ? null : (int) $offset;
        $sort   = $this->sanitizeSortColumn(timeclock_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'timeclock_id');
        $order  = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $sort = match ($sort) {
            'employee_name' => 'people.last_name',
            'clock_in'      => 'timeclocks.clock_in',
            'clock_out'     => 'timeclocks.clock_out',
            'note'          => 'timeclocks.note',
            default         => 'timeclocks.timeclock_id',
        };

        $filters = [
            'start_date' => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'   => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'open'       => false,
        ];

        $requestFilterValues = $this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $requestFilters      = array_fill_keys(is_array($requestFilterValues) ? $requestFilterValues : [], true);
        $filters             = array_merge($filters, $requestFilters);

        $canManage = $this->canManage();

        if (! $canManage) {
            $filters['person_id'] = (int) $this->session->get('person_id');
        }

        $timeclocks = $this->timeclock->search($search, $filters, $limit, $offset, $sort, $order);
        $totalRows  = $this->timeclock->getFoundRows($search, $filters);

        $dataRows = [];

        foreach ($timeclocks->getResult() as $timeclock) {
            $dataRows[] = get_timeclock_data_row($timeclock, $canManage);
        }

        return $this->response->setJSON(['total' => $totalRows, 'rows' => $dataRows]);
    }

    /**
     * Gets one row for the timeclock manage table. This is called using AJAX to update one row.
     */
    public function getRow(int $rowId): ResponseInterface
    {
        $timeclockInfo = $this->timeclock->getInfo($rowId);

        $canManage = $this->canManage();

        if (! $canManage && (int) $timeclockInfo->person_id !== (int) $this->session->get('person_id')) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Timeclocks.no_permission')]);
        }

        return $this->response->setJSON(get_timeclock_data_row($timeclockInfo, $canManage));
    }

    /**
     * Shows the timeclock entry form.
     */
    public function getView(int $timeclockId = NEW_ENTRY): RedirectResponse|string
    {
        if (! $this->canManage()) {
            return redirect()->to('timeclocks');
        }

        $data['employees'] = [];

        foreach ($this->employee->get_all()->getResult() as $employee) {
            $data['employees'][$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
        }

        $timeclockInfo = $this->timeclock->getInfo($timeclockId);

        if ($timeclockInfo->timeclock_id === NEW_ENTRY) {
            $timeclockInfo->person_id = $this->employee->get_logged_in_employee_info()->person_id;
            $timeclockInfo->clock_in  = date('Y-m-d\TH:i');
            $timeclockInfo->clock_out = '';
        } else {
            $timeclockInfo->clock_in  = $this->toLocalDatetime($timeclockInfo->clock_in);
            $timeclockInfo->clock_out = empty($timeclockInfo->clock_out) ? '' : $this->toLocalDatetime($timeclockInfo->clock_out);
        }

        $data['timeclock_info'] = $timeclockInfo;
        $data['can_manage']     = true;

        return view('timeclocks/form', $data);
    }

    /**
     * Saves a timeclock entry.
     */
    public function postSave(int $timeclockId = NEW_ENTRY): ResponseInterface
    {
        if (! $this->canManage()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Timeclocks.no_permission'),
                'id'      => $timeclockId,
            ]);
        }

        $rules = [
            'person_id' => [
                'label' => lang('Timeclocks.employee'),
                'rules' => 'required|integer',
            ],
            'clock_in' => [
                'label' => lang('Timeclocks.clock_in'),
                'rules' => 'required',
            ],
            'clock_out' => [
                'label' => lang('Timeclocks.clock_out'),
                'rules' => 'permit_empty',
            ],
            'note' => [
                'label' => lang('Timeclocks.note'),
                'rules' => 'permit_empty|max_length[255]',
            ],
        ];

        $messages = [
            'person_id' => [
                'required' => lang('Timeclocks.employee_required'),
                'integer'  => lang('Timeclocks.employee_invalid'),
            ],
            'clock_in' => [
                'required' => lang('Timeclocks.clock_in_required'),
            ],
            'note' => [
                'max_length' => lang('Timeclocks.note_maxlength'),
            ],
        ];

        if ($error = $this->validateFields($rules, $messages, $timeclockId)) {
            return $error;
        }

        $personId = (int) $this->request->getPost('person_id', FILTER_SANITIZE_NUMBER_INT);

        if (! $this->employee->exists($personId)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Timeclocks.employee_invalid'),
                'id'      => $timeclockId,
            ]);
        }

        $clockIn = $this->parseDatetime((string) $this->request->getPost('clock_in', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        if ($clockIn === null) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Timeclocks.datetime_invalid'),
                'id'      => $timeclockId,
            ]);
        }

        $clockOutInput = trim((string) $this->request->getPost('clock_out', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $clockOut      = null;
        if ($clockOutInput !== '') {
            $clockOut = $this->parseDatetime($clockOutInput);
            if ($clockOut === null) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => lang('Timeclocks.datetime_invalid'),
                    'id'      => $timeclockId,
                ]);
            }

            if ($clockOut <= $clockIn) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => lang('Timeclocks.clock_out_before_clock_in'),
                    'id'      => $timeclockId,
                ]);
            }
        }

        if ($clockOut === null) {
            $openEntry = $this->timeclock->getOpenEntry($personId);

            if ($openEntry !== null && (int) $openEntry->timeclock_id !== $timeclockId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => lang('Timeclocks.already_clocked_in'),
                    'id'      => $timeclockId,
                ]);
            }
        }

        $timeclockData = [
            'person_id' => $personId,
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
            'note'      => $this->request->getPost('note', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        ];

        if ($this->timeclock->saveValue($timeclockData, $timeclockId)) {
            $message = $timeclockId === NEW_ENTRY ? lang('Timeclocks.successful_adding') : lang('Timeclocks.successful_updating');

            return $this->response->setJSON(['success' => true, 'message' => $message, 'id' => $timeclockId]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => lang('Timeclocks.error_adding_updating'),
            'id'      => $timeclockId,
        ]);
    }

    /**
     * Deletes a list of timeclock entries.
     */
    public function postDelete(): ResponseInterface
    {
        if (! $this->canManage()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Timeclocks.no_permission'),
            ]);
        }

        $ids = $this->request->getPost('ids');
        $ids = is_array($ids) ? $ids : [$ids];
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));

        if (empty($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => lang('Timeclocks.none_selected'),
            ]);
        }

        if ($this->timeclock->deleteList($ids)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => lang('Timeclocks.successful_deleted') . ' ' . count($ids) . ' ' . lang('Timeclocks.one_or_multiple'),
                'ids'     => $ids,
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => lang('Timeclocks.cannot_be_deleted'),
            'ids'     => $ids,
        ]);
    }

    /**
     * Clocks the logged in employee in.
     */
    public function postClockIn(): ResponseInterface
    {
        $personId = $this->employee->get_logged_in_employee_info()->person_id;

        if (! $this->timeclock->clockIn($personId, date('Y-m-d H:i:s'))) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Timeclocks.already_clocked_in')]);
        }

        return $this->response->setJSON(['success' => true, 'message' => lang('Timeclocks.clock_in_success')]);
    }

    /**
     * Clocks the logged in employee out.
     */
    public function postClockOut(): ResponseInterface
    {
        $personId = $this->employee->get_logged_in_employee_info()->person_id;

        if (! $this->timeclock->clockOut($personId, date('Y-m-d H:i:s'))) {
            return $this->response->setJSON(['success' => false, 'message' => lang('Timeclocks.not_clocked_in')]);
        }

        return $this->response->setJSON(['success' => true, 'message' => lang('Timeclocks.clock_out_success')]);
    }

    private function canManage(): bool
    {
        return $this->employee->has_grant('timeclocks_manage', $this->session->get('person_id'));
    }

    private function parseDatetime(string $value): ?string
    {
        $value = trim(str_replace('T', ' ', $value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);

        return $dateTime !== false && $dateTime->format('Y-m-d H:i:s') === $value ? $value : null;
    }

    private function toLocalDatetime(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp === false ? '' : date('Y-m-d\TH:i', $timestamp);
    }
}
