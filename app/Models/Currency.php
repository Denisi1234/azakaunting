<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use stdClass;

/**
 * Currency class
 */
class Currency extends Model
{
    protected $table            = 'currencies';
    protected $primaryKey       = 'currency_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'currency_code',
        'currency_name',
        'currency_symbol',
        'exchange_rate',
        'is_default',
        'deleted',
    ];

    /**
     * Determines if a given Currency_id is a Currency
     */
    public function exists(int $currency_id): bool
    {
        $builder = $this->db->table('currencies');
        $builder->where('currency_id', $currency_id);

        return $builder->get()->getNumRows() === 1;
    }

    /**
     * Gets information about a particular currency
     */
    public function get_info(int $currency_id): object
    {
        $builder = $this->db->table('currencies');
        $builder->where('currency_id', $currency_id);

        $query = $builder->get();

        if ($query->getNumRows() === 1) {
            return $query->getRow();
        }

        $empty_obj = new stdClass();

        foreach ($this->db->getFieldData('currencies') as $field) {
            $field_name = $field->name;
            if (in_array($field->type, ['int', 'tinyint', 'decimal'], true)) {
                $empty_obj->{$field_name} = ($field->primary_key === 1) ? NEW_ENTRY : 0;
            } else {
                $empty_obj->{$field_name} = null;
            }
        }

        return $empty_obj;
    }

    /**
     * Returns all the currencies
     */
    public function get_all(bool $no_deleted = true): ResultInterface
    {
        $builder = $this->db->table('currencies');

        if ($no_deleted) {
            $builder->where('deleted', 0);
        }

        $builder->orderBy('is_default', 'desc');
        $builder->orderBy('currency_code', 'asc');

        return $builder->get();
    }

    /**
     * Gets the default currency
     */
    public function get_default(): object
    {
        $builder = $this->db->table('currencies');
        $builder->where('is_default', 1);
        $builder->where('deleted', 0);
        $builder->limit(1);

        $row = $builder->get()->getRow();

        if ($row) {
            return $row;
        }

        // Fallback to first available
        $builder = $this->db->table('currencies');
        $builder->where('deleted', 0);
        $builder->limit(1);

        return $builder->get()->getRow() ?? $this->get_info(NEW_ENTRY);
    }

    /**
     * Inserts or updates a currency
     */
    public function save_value(array &$currency_data, int $currency_id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('currencies');

        // If setting as default, clear other defaults
        if (! empty($currency_data['is_default'])) {
            $this->db->table('currencies')->update(['is_default' => 0]);
        }

        if ($currency_id === NEW_ENTRY || ! $this->exists($currency_id)) {
            if ($builder->insert($currency_data)) {
                $currency_data['currency_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder->where('currency_id', $currency_id);

        return $builder->update($currency_data);
    }

    /**
     * Deletes a list of currencies (soft delete)
     */
    public function delete_list(array $currency_ids): bool
    {
        $builder = $this->db->table('currencies');
        $builder->whereIn('currency_id', $currency_ids);

        return $builder->update(['deleted' => 1]);
    }
}
