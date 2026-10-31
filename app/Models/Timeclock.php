<?php

namespace App\Models;

use CodeIgniter\Model;

class Timeclock extends Model
{
    protected $table         = 'timeclocks';
    protected $primaryKey    = 'timeclock_id';
    protected $allowedFields = [
        'person_id',
        'clock_in',
        'clock_out',
        'note',
        'deleted',
    ];
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;

    /**
     * Gets information about a single timeclock entry.
     */
    public function getInfo(int $timeclockId): object
    {
        $builder = $this->db->table('timeclocks');
        $builder->select('timeclocks.*, people.first_name, people.last_name');
        $builder->join('people', 'people.person_id = timeclocks.person_id', 'left');
        $builder->where('timeclocks.timeclock_id', $timeclockId);
        $builder->where('timeclocks.deleted', 0);

        $row = $builder->get()->getRow();

        return $row ?? $this->getEmptyObject();
    }

    /**
     * Gets an empty timeclock object.
     */
    public function getEmptyObject(): object
    {
        return (object) [
            'timeclock_id' => NEW_ENTRY,
            'person_id'    => null,
            'first_name'   => '',
            'last_name'    => '',
            'clock_in'     => '',
            'clock_out'    => null,
            'note'         => '',
        ];
    }

    /**
     * Gets the open timeclock entry for an employee, if any.
     */
    public function getOpenEntry(int $personId): ?object
    {
        $builder = $this->db->table('timeclocks');
        $builder->select('timeclocks.*, people.first_name, people.last_name');
        $builder->join('people', 'people.person_id = timeclocks.person_id', 'left');
        $builder->where('timeclocks.person_id', $personId);
        $builder->where('timeclocks.clock_out IS NULL', null, false);
        $builder->where('timeclocks.deleted', 0);
        $builder->orderBy('timeclocks.clock_in', 'DESC');
        $builder->limit(1);

        return $builder->get()->getRow() ?? null;
    }

    /**
     * Clocks an employee in. Returns false when an entry is already open.
     */
    public function clockIn(int $personId, string $clockIn): bool
    {
        if ($this->getOpenEntry($personId) !== null) {
            return false;
        }

        return $this->db->table('timeclocks')->insert([
            'person_id' => $personId,
            'clock_in'  => $clockIn,
            'clock_out' => null,
            'note'      => null,
            'deleted'   => 0,
        ]);
    }

    /**
     * Clocks an employee out of their open entry.
     */
    public function clockOut(int $personId, string $clockOut): bool
    {
        $openEntry = $this->getOpenEntry($personId);
        if ($openEntry === null) {
            return false;
        }

        return $this->db->table('timeclocks')
            ->where('timeclock_id', $openEntry->timeclock_id)
            ->update(['clock_out' => $clockOut]);
    }

    /**
     * Inserts or updates a timeclock entry.
     */
    public function saveValue(array &$data, int $timeclockId = NEW_ENTRY): bool
    {
        unset($data['timeclock_id']);

        if ($timeclockId === NEW_ENTRY) {
            return $this->db->table('timeclocks')->insert($data);
        }

        return $this->db->table('timeclocks')
            ->where('timeclock_id', $timeclockId)
            ->update($data);
    }

    /**
     * Soft deletes a list of timeclock entries.
     */
    public function deleteList(array $timeclockIds): bool
    {
        if (empty($timeclockIds)) {
            return false;
        }

        return $this->db->table('timeclocks')
            ->whereIn('timeclock_id', $timeclockIds)
            ->update(['deleted' => 1]);
    }

    /**
     * Gets rows for the timeclock manage table.
     */
    public function search(string $search, array $filters, ?int $rows = null, ?int $limitFrom = null, ?string $sort = null, ?string $order = null, bool $countOnly = false)
    {
        $builder = $this->db->table('timeclocks');
        $builder->join('people', 'people.person_id = timeclocks.person_id', 'left');
        $builder->where('timeclocks.deleted', 0);

        if ($search !== '') {
            $builder->groupStart();
            $builder->like('people.first_name', $search);
            $builder->orLike('people.last_name', $search);
            $builder->orLike('timeclocks.note', $search);
            $builder->groupEnd();
        }

        if (! empty($filters['start_date'])) {
            $startDate = (string) $filters['start_date'];
            $builder->where('timeclocks.clock_in >=', str_contains($startDate, ' ') ? $startDate : $startDate . ' 00:00:00');
        }

        if (! empty($filters['end_date'])) {
            $endDate = (string) $filters['end_date'];
            $builder->where('timeclocks.clock_in <=', str_contains($endDate, ' ') ? $endDate : $endDate . ' 23:59:59');
        }

        if (! empty($filters['open'])) {
            $builder->where('timeclocks.clock_out IS NULL', null, false);
        }

        if (! empty($filters['person_id'])) {
            $builder->where('timeclocks.person_id', (int) $filters['person_id']);
        }

        if ($countOnly) {
            return $builder->countAllResults();
        }

        $builder->select('timeclocks.*, people.first_name, people.last_name');

        if ($sort !== null) {
            $builder->orderBy($sort, $order ?? 'asc');
        }

        if ($limitFrom !== null) {
            $builder->limit($rows, $limitFrom);
        } elseif ($rows !== null) {
            $builder->limit($rows);
        }

        return $builder->get();
    }

    /**
     * Gets the number of rows found for the current search.
     */
    public function getFoundRows(string $search, array $filters): int
    {
        return $this->search($search, $filters, null, null, null, null, true);
    }
}
