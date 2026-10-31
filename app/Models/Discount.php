<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use stdClass;

/**
 * Discount class
 */
class Discount extends Model
{
    protected $table            = 'discounts';
    protected $primaryKey       = 'discount_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'discount_code',
        'discount_name',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_uses',
        'used_count',
        'start_date',
        'end_date',
        'applies_to',
        'is_active',
        'deleted',
    ];

    public function exists(int $discount_id): bool
    {
        $builder = $this->db->table('discounts');
        $builder->where('discount_id', $discount_id);

        return $builder->get()->getNumRows() === 1;
    }

    public function get_info(int $discount_id): object
    {
        $builder = $this->db->table('discounts');
        $builder->where('discount_id', $discount_id);
        $query = $builder->get();

        if ($query->getNumRows() === 1) {
            return $query->getRow();
        }

        $empty_obj = new stdClass();

        foreach ($this->db->getFieldData('discounts') as $field) {
            $field_name = $field->name;
            if (in_array($field->type, ['int', 'tinyint', 'decimal'], true)) {
                $empty_obj->{$field_name} = ($field->primary_key === 1) ? NEW_ENTRY : 0;
            } else {
                $empty_obj->{$field_name} = null;
            }
        }

        return $empty_obj;
    }

    public function get_all(bool $no_deleted = true): ResultInterface
    {
        $builder = $this->db->table('discounts');
        if ($no_deleted) {
            $builder->where('deleted', 0);
        }
        $builder->orderBy('discount_name', 'asc');

        return $builder->get();
    }

    public function find_by_code(string $code): ?object
    {
        $builder = $this->db->table('discounts');
        $builder->where('discount_code', $code);
        $builder->where('deleted', 0);
        $builder->where('is_active', 1);
        $row = $builder->get()->getRow();

        return $row ?: null;
    }

    public function is_valid(object $discount, float $purchase_amount): bool
    {
        if ($discount->is_active !== 1) {
            return false;
        }
        if ($discount->max_uses !== null && $discount->used_count >= $discount->max_uses) {
            return false;
        }
        if ($discount->min_purchase > 0 && $purchase_amount < $discount->min_purchase) {
            return false;
        }
        $today = date('Y-m-d');
        if ($discount->start_date && $today < $discount->start_date) {
            return false;
        }

        return ! ($discount->end_date && $today > $discount->end_date);
    }

    public function calculate_discount(object $discount, float $amount): float
    {
        if ($discount->discount_type === 'percentage') {
            return round($amount * ($discount->discount_value / 100), 2);
        }

        return min($discount->discount_value, $amount);
    }

    public function increment_usage(int $discount_id): bool
    {
        $builder = $this->db->table('discounts');
        $builder->where('discount_id', $discount_id);
        $builder->set('used_count', 'used_count + 1', false);

        return $builder->update();
    }

    public function save_value(array &$discount_data, int $discount_id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('discounts');
        if ($discount_id === NEW_ENTRY || ! $this->exists($discount_id)) {
            if ($builder->insert($discount_data)) {
                $discount_data['discount_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }
        $builder->where('discount_id', $discount_id);

        return $builder->update($discount_data);
    }

    public function delete_list(array $discount_ids): bool
    {
        $builder = $this->db->table('discounts');
        $builder->whereIn('discount_id', $discount_ids);

        return $builder->update(['deleted' => 1]);
    }
}
