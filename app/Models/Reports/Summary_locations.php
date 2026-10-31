<?php

namespace App\Models\Reports;

use CodeIgniter\Database\BaseBuilder;

class Summary_locations extends Summary_report
{
    protected function _get_data_columns(): array
    {
        return [
            ['location_name' => lang('Reports.stock_location')],
            ['sales'         => lang('Reports.sales'), 'sorter' => 'number_sorter'],
            ['quantity'      => lang('Reports.quantity'), 'sorter' => 'number_sorter'],
            ['subtotal'      => lang('Reports.subtotal'), 'sorter' => 'number_sorter'],
            ['tax'           => lang('Reports.tax'), 'sorter' => 'number_sorter'],
            ['total'         => lang('Reports.total'), 'sorter' => 'number_sorter'],
            ['cost'          => lang('Reports.cost'), 'sorter' => 'number_sorter'],
            ['profit'        => lang('Reports.profit'), 'sorter' => 'number_sorter']
        ];
    }

    protected function _select(array $inputs, object &$builder): void
    {
        parent::_select($inputs, $builder);

        $builder->select('MAX(stock_locations.location_name) AS location_name');
        $builder->select('SUM(sales_items.quantity_purchased) AS quantity_purchased');
        $builder->select('COUNT(DISTINCT sales.sale_id) AS sales');
    }

    protected function _from(object &$builder): void
    {
        parent::_from($builder);
        $builder->join('stock_locations AS stock_locations', 'sales_items.item_location = stock_locations.location_id');
    }

    protected function _where(array $inputs, BaseBuilder &$builder): void
    {
        parent::_where($inputs, $builder);

        if (empty($inputs['allowed_location_ids'])) {
            $builder->where('sales_items.item_location', 0);
            return;
        }

        $builder->whereIn('sales_items.item_location', $inputs['allowed_location_ids']);
    }

    protected function _group_order(object &$builder): void
    {
        $builder->groupBy('sales_items.item_location');
        $builder->orderBy('location_name');
    }
}
