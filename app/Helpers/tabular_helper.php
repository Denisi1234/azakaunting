<?php

use App\Models\Attribute;
use App\Models\Employee;
use CodeIgniter\Database\ResultInterface;
use CodeIgniter\HTTP\IncomingRequest;
use Config\OSPOS;

/** Tabular views helper */

/**
 * Basic tabular headers function
 */
function transform_headers_readonly(array $headers): string
{
    $result = [];

    foreach ($headers as $key => $value) {
        $result[] = ['field' => $key, 'title' => $value, 'sortable' => $value !== '', 'switchable' => ! preg_match('(^$|&nbsp)', $value)];
    }

    return json_encode($result);
}

/**
 * Basic tabular headers function
 */
function transform_headers(array $headers, bool $readonly = false, bool $editable = true): string    // TODO: $array needs to be refactored to a new name.  Perhaps $headers?
{
    $result = [];
    if (! $readonly) {
        $headers = array_merge([['checkbox' => 'select', 'sortable' => false]], $headers);
    }

    if ($editable) {
        $headers[] = ['edit' => ''];
    }

    foreach ($headers as $element) {    // TODO: This might be clearer to refactor this to `foreach($headers as $header)`
        reset($element);
        $result[] = [
            'field'      => key($element),
            'title'      => current($element),
            'switchable' => $element['switchable'] ?? ! preg_match('(^$|&nbsp)', current($element)),
            'escape'     => ! preg_match('/(edit|email|messages|item_pic)/', key($element)) && ! (isset($element['escape']) && ! $element['escape']),
            'sortable'   => $element['sortable'] ?? current($element) !== '',
            'checkbox'   => $element['checkbox'] ?? false,
            'class'      => isset($element['checkbox']) || preg_match('(^$|&nbsp)', current($element)) ? 'print_hide' : '',
            'sorter'     => $element['sorter'] ?? '',
        ];
    }

    return json_encode($result);
}

function salesHeaders(): array
{
    return [
        ['sale_id' => lang('Common.id')],
        ['sale_time' => lang('Sales.sale_time')],
        ['customer_name' => lang('Customers.customer')],
        ['amount_due' => lang('Sales.amount_due')],
        ['amount_tendered' => lang('Sales.amount_tendered')],
        ['change_due' => lang('Sales.change_due')],
        ['payment_type' => lang('Sales.payment_type')],
    ];
}

/**
 * Get the header for the sales tabular view
 */
function get_sales_manage_table_headers(): string
{
    $headers = salesHeaders();
    $config  = config(OSPOS::class)->settings;

    if ($config['invoice_enable']) {
        $headers[] = ['invoice_number' => lang('Sales.invoice_number')];
        $headers[] = ['invoice' => '', 'sortable' => false, 'escape' => false];
    }

    $headers[] = ['receipt' => '', 'sortable' => false, 'escape' => false];

    return transform_headers($headers);
}

/**
 * Get the html data row for the sales
 */
function getSaleDataRow(object $sale): array
{
    $uri        = current_url(true);
    $controller = $uri->getSegment(1);

    $row = [
        'sale_id'         => $sale->sale_id,
        'sale_time'       => to_datetime(strtotime($sale->sale_time)),
        'customer_name'   => $sale->customer_name,
        'amount_due'      => to_currency($sale->amount_due),
        'amount_tendered' => to_currency($sale->amount_tendered),
        'change_due'      => to_currency($sale->change_due),
        'payment_type'    => $sale->payment_type,
    ];

    $config = config(OSPOS::class)->settings;

    if ($config['invoice_enable']) {
        $row['invoice_number'] = $sale->invoice_number;
        $row['invoice']        = anchor(
            "{$controller}/invoice/{$sale->sale_id}",
            '<span class="glyphicon glyphicon-list-alt"></span>',
            ['title' => lang('Sales.show_invoice')],
        );
    }

    $row['receipt'] = anchor(
        "{$controller}/receipt/{$sale->sale_id}",
        '<span class="glyphicon glyphicon-usd"></span>',
        ['title' => lang('Sales.show_receipt')],
    );
    $row['edit'] = anchor(
        "{$controller}/edit/{$sale->sale_id}",
        '<span class="glyphicon glyphicon-edit"></span>',
        [
            'class'           => 'modal-dlg print_hide',
            'data-btn-delete' => lang('Common.delete'),
            'data-btn-submit' => lang('Common.submit'),
            'title'           => lang(ucfirst($controller) . '.update'),
        ],
    );

    return $row;
}

/**
 * Get the html data last row for the sales
 */
function getSaleDataLastRow(ResultInterface $sales): array
{
    $sum_amount_due      = 0;
    $sum_amount_tendered = 0;
    $sum_change_due      = 0;

    foreach ($sales->getResult() as $key => $sale) {
        $sum_amount_due += $sale->amount_due;
        $sum_amount_tendered += $sale->amount_tendered;
        $sum_change_due += $sale->change_due;
    }

    return [
        'sale_id'         => '-',
        'sale_time'       => lang('Sales.total'),
        'amount_due'      => to_currency($sum_amount_due),
        'amount_tendered' => to_currency($sum_amount_tendered),
        'change_due'      => to_currency($sum_change_due),
    ];
}

/**
 * Get the sales payments summary
 */
function getSalesManagePaymentsSummary(array $payments): string
{
    $table = '<div id="report_summary">';
    $total = 0;

    foreach ($payments as $key => $payment) {
        $amount = $payment['payment_amount'];
        $total  = bcadd($total, $amount);
        $table .= '<div class="summary_row">' . $payment['payment_type'] . ': ' . to_currency($amount) . '</div>';
    }

    $table .= '<div class="summary_row">' . lang('Sales.total') . ': ' . to_currency($total) . '</div>';
    $table .= '</div>';

    return $table;
}

function person_headers(): array
{
    return [
        ['people.person_id' => lang('Common.id')],
        ['last_name' => lang('Common.last_name')],
        ['first_name' => lang('Common.first_name')],
        ['email' => lang('Common.email')],
        ['phone_number' => lang('Common.phone_number')],
    ];
}

/**
 * Get the header for the people tabular view
 */
function get_people_manage_table_headers(): string
{
    $headers = person_headers();

    $employee = model(Employee::class);
    $session  = session();

    if ($employee->has_grant('messages', $session->get('person_id'))) {
        $headers[] = ['messages' => '', 'sortable' => false];
    }

    return transform_headers($headers);
}

/**
 * Get the html data row for the person
 */
function get_person_data_row(object $person): array
{
    $controller = get_controller();

    return [
        'people.person_id' => $person->person_id,
        'last_name'        => $person->last_name,
        'first_name'       => $person->first_name,
        'email'            => empty($person->email) ? '' : mailto(esc($person->email), esc($person->email)),
        'phone_number'     => $person->phone_number,
        'messages'         => empty($person->phone_number)
            ? ''
            : anchor(
                "Messages/view/{$person->person_id}",
                '<span class="glyphicon glyphicon-phone"></span>',
                [
                    'class'           => 'modal-dlg',
                    'data-btn-submit' => lang('Common.submit'),
                    'title'           => lang('Messages.sms_send'),
                ],
            ),
        'edit' => anchor(
            "{$controller}/view/{$person->person_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),    // TODO: String interpolation
            ],
        ),
    ];
}

function employee_headers(): array
{
    return array_merge(person_headers(), [
        ['job_title' => lang('Employees.job_title')],
        ['department' => lang('Employees.department')],
    ]);
}

/**
 * Get the header for the employee tabular view
 */
function get_employees_manage_table_headers(bool $includeHrInfo = true): string
{
    $headers = $includeHrInfo ? employee_headers() : person_headers();

    $employee = model(Employee::class);
    $session  = session();

    if ($employee->has_grant('messages', $session->get('person_id'))) {
        $headers[] = ['messages' => '', 'sortable' => false];
    }

    return transform_headers($headers);
}

/**
 * Get the html data row for the employee
 */
function get_employee_data_row(object $employee, bool $includeHrInfo = true): array
{
    $row = get_person_data_row($employee);

    if ($includeHrInfo) {
        $row['job_title']  = $employee->job_title;
        $row['department'] = $employee->department;
    }

    return $row;
}

function timeclock_headers(): array
{
    return [
        ['timeclock_id' => lang('Timeclocks.id')],
        ['employee_name' => lang('Timeclocks.employee')],
        ['clock_in' => lang('Timeclocks.clock_in')],
        ['clock_out' => lang('Timeclocks.clock_out')],
        ['hours' => lang('Timeclocks.total_hours'), 'sortable' => false],
        ['note' => lang('Timeclocks.note')],
    ];
}

/**
 * Get the header for the timeclock tabular view
 */
function get_timeclocks_manage_table_headers(bool $editable = true): string
{
    return transform_headers(timeclock_headers(), ! $editable, $editable);
}

/**
 * Get the html data row for the timeclock entry
 */
function get_timeclock_data_row(object $timeclock, bool $editable = true): array
{
    $controller = get_controller();
    $clockIn    = strtotime($timeclock->clock_in);
    $clockOut   = empty($timeclock->clock_out) ? null : strtotime($timeclock->clock_out);
    $hours      = $clockOut === null ? '' : round(($clockOut - $clockIn) / 3600, 2);

    $row = [
        'timeclock_id'  => $timeclock->timeclock_id,
        'employee_name' => $timeclock->first_name . ' ' . $timeclock->last_name,
        'clock_in'      => to_datetime($clockIn),
        'clock_out'     => $clockOut === null ? '' : to_datetime($clockOut),
        'hours'         => $hours,
        'note'          => $timeclock->note ?? '',
    ];

    if ($editable) {
        $row['edit'] = anchor(
            "{$controller}/view/{$timeclock->timeclock_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang('Timeclocks.update'),
            ],
        );
    }

    return $row;
}

function customer_headers(): array
{
    return [
        ['people.person_id' => lang('Common.id')],
        ['last_name' => lang('Common.last_name')],
        ['first_name' => lang('Common.first_name')],
        ['email' => lang('Common.email')],
        ['phone_number' => lang('Common.phone_number')],
        ['total' => lang('Common.total_spent'), 'sortable' => false],
    ];
}

/**
 * Get the header for the customer tabular view
 */
function get_customer_manage_table_headers(): string
{
    $headers = customer_headers();

    $employee = model(Employee::class);
    $session  = session();

    if ($employee->has_grant('messages', $session->get('person_id'))) {
        $headers[] = ['messages' => '', 'sortable' => false];
    }

    return transform_headers($headers);
}

/**
 * Get the html data row for the customer
 */
function get_customer_data_row(object $person, object $stats): array
{
    $controller = get_controller();

    return [
        'people.person_id' => $person->person_id,
        'last_name'        => $person->last_name,
        'first_name'       => $person->first_name,
        'email'            => empty($person->email) ? '' : mailto(esc($person->email), esc($person->email)),
        'phone_number'     => $person->phone_number,
        'total'            => to_currency($stats->total),
        'messages'         => empty($person->phone_number)
            ? ''
            : anchor(
                "Messages/view/{$person->person_id}",
                '<span class="glyphicon glyphicon-phone"></span>',
                [
                    'class'           => 'modal-dlg',
                    'data-btn-submit' => lang('Common.submit'),
                    'title'           => lang('Messages.sms_send'),
                ],
            ),
        'edit' => anchor(
            "{$controller}/view/{$person->person_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function supplier_headers(): array
{
    return [
        ['people.person_id' => lang('Common.id')],
        ['company_name' => lang('Suppliers.company_name')],
        ['agency_name' => lang('Suppliers.agency_name')],
        ['category' => lang('Suppliers.category')],
        ['last_name' => lang('Common.last_name')],
        ['first_name' => lang('Common.first_name')],
        ['email' => lang('Common.email')],
        ['phone_number' => lang('Common.phone_number')],
    ];
}

/**
 * Get the header for the suppliers tabular view
 */
function get_suppliers_manage_table_headers(): string
{
    $headers = supplier_headers();

    $employee = model(Employee::class);
    $session  = session();

    if ($employee->has_grant('messages', $session->get('person_id'))) {
        $headers[] = ['messages' => ''];
    }

    return transform_headers($headers);
}

/**
 * Get the html data row for the supplier
 */
function get_supplier_data_row(object $supplier): array
{
    $controller = get_controller();

    return [
        'people.person_id' => $supplier->person_id,
        'company_name'     => html_entity_decode($supplier->company_name),
        'agency_name'      => $supplier->agency_name,
        'category'         => $supplier->category,
        'last_name'        => $supplier->last_name,
        'first_name'       => $supplier->first_name,
        'email'            => empty($supplier->email) ? '' : mailto(esc($supplier->email), esc($supplier->email)),
        'phone_number'     => $supplier->phone_number,
        'messages'         => empty($supplier->phone_number)
            ? ''
            : anchor(
                "Messages/view/{$supplier->person_id}",
                '<span class="glyphicon glyphicon-phone"></span>',
                [
                    'class'           => 'modal-dlg',
                    'data-btn-submit' => lang('Common.submit'),
                    'title'           => lang('Messages.sms_send'),
                ],
            ),
        'edit' => anchor(
            "{$controller}/view/{$supplier->person_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function item_headers(): array
{
    return [
        ['items.item_id' => lang('Common.id')],
        ['item_number' => lang('Items.item_number')],
        ['name' => lang('Items.name')],
        ['category' => lang('Items.category')],
        ['company_name' => lang('Suppliers.company_name')],
        ['cost_price' => lang('Items.cost_price')],
        ['unit_price' => lang('Items.unit_price')],
        ['quantity' => lang('Items.quantity')],
    ];
}

/**
 * Get all sortable column keys for items table, including dynamic attribute columns.
 *
 * @param array|null $definitionIds Attribute definition IDs to append as sortable columns.
 *                                  If null, resolved via a query against SHOW_IN_ITEMS.
 *
 * @return array Array of column headers in the format sanitizeSortColumn() expects
 */
function itemSortColumns(?array $definitionIds = null): array
{
    if ($definitionIds === null) {
        $attribute     = model(Attribute::class);
        $definitionIds = array_keys($attribute->getDefinitionsByFlags($attribute::SHOW_IN_ITEMS));
    }

    $headers = item_headers();

    foreach ($definitionIds as $definitionId) {
        $headers[] = [(string) $definitionId => ''];
    }

    return $headers;
}

/**
 * Get the header for the items tabular view
 */
function getItemsManageTableHeaders(): string
{
    $attribute            = model(Attribute::class);
    $config               = config(OSPOS::class)->settings;
    $definitionsWithTypes = $attribute->getDefinitionsByFlags($attribute::SHOW_IN_ITEMS, true);

    $headers = item_headers();

    if ($config['use_destination_based_tax']) {
        $headers[] = ['tax_percents' => lang('Items.tax_category'), 'sortable' => false];
    } else {
        $headers[] = ['tax_percents' => lang('Items.tax_percents'), 'sortable' => false];
    }

    $headers[] = ['item_pic' => lang('Items.image'), 'sortable' => false];

    foreach ($definitionsWithTypes as $definitionId => $definitionInfo) {
        $headers[] = [$definitionId => $definitionInfo['name'], 'sortable' => true];
    }

    $headers[] = ['inventory' => '', 'escape' => false];
    $headers[] = ['stock' => '', 'escape' => false];

    return transform_headers($headers);
}

/**
 * Get the html data row for the item
 *
 * @param array $definitionNames     Attribute definitions with types, keyed by definition_id (see Attribute::getDefinitionsByFlags(..., true))
 * @param array $taxPercentsByItemId Pre-computed tax percent strings, keyed by item_id (see Items::buildTaxPercentsByItem())
 */
function getItemDataRow(object $item, array $definitionNames, array $taxPercentsByItemId): array
{
    $config = config(OSPOS::class)->settings;

    $taxPercents = $taxPercentsByItemId[$item->item_id] ?? '-';

    $controller = get_controller();

    $image = null;
    if (! empty($item->pic_filename)) {
        $ext = pathinfo($item->pic_filename, PATHINFO_EXTENSION);

        $images = $ext === ''
            ? glob("./uploads/item_pics/{$item->pic_filename}.*")
            : glob("./uploads/item_pics/{$item->pic_filename}");

        if (count($images) > 0) {
            $imagePath = ltrim($images[0], './');
            $image .= '<a class="rollover" href="' . base_url(implode('/', array_map('rawurlencode', explode('/', $imagePath)))) . '"><img alt="Image thumbnail" src="' . site_url('items/PicThumb/' . rawurlencode(pathinfo($images[0], PATHINFO_BASENAME))) . '"></a>';
        }
    }

    if ($config['multi_pack_enabled']) {
        $item->name .= NAME_SEPARATOR . $item->pack_name;
    }

    $columns = [
        'items.item_id' => $item->item_id,
        'item_number'   => $item->item_number,
        'name'          => $item->name,
        'category'      => $item->category,
        'company_name'  => $item->company_name,    // TODO: This isn't in the items table. Should this be here?
        'cost_price'    => to_currency($item->cost_price),
        'unit_price'    => to_currency($item->unit_price),
        'quantity'      => to_quantity_decimals($item->quantity),
        'tax_percents'  => ! $taxPercents ? '-' : $taxPercents,
        'item_pic'      => $image,
    ];

    $icons = [
        'inventory' => anchor(
            "{$controller}/inventory/{$item->item_id}",
            '<span class="glyphicon glyphicon-pushpin"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.count'),
            ],
        ),
        'stock' => anchor(
            "{$controller}/countDetails/{$item->item_id}",
            '<span class="glyphicon glyphicon-list-alt"></span>',
            [
                'class' => 'modal-dlg',
                'title' => lang(ucfirst($controller) . '.details_count'),
            ],
        ),
        'edit' => anchor(
            "{$controller}/view/{$item->item_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];

    return $columns + expand_attribute_values($definitionNames, (array) $item) + $icons;
}

function giftcard_headers(): array
{
    return [
        ['giftcard_id' => lang('Common.id')],
        ['last_name' => lang('Common.last_name')],
        ['first_name' => lang('Common.first_name')],
        ['giftcard_number' => lang('Giftcards.giftcard_number')],
        ['value' => lang('Giftcards.card_value')],
    ];
}

/**
 * Get the header for the giftcard tabular view
 */
function get_giftcards_manage_table_headers(): string
{
    return transform_headers(giftcard_headers());
}

/**
 * Get the html data row for the giftcard
 */
function get_giftcard_data_row(object $giftcard): array
{
    $controller = get_controller();

    return [
        'giftcard_id'     => $giftcard->giftcard_id,
        'last_name'       => $giftcard->last_name,
        'first_name'      => $giftcard->first_name,
        'giftcard_number' => $giftcard->giftcard_number,
        'value'           => to_currency($giftcard->value),
        'edit'            => anchor(
            "{$controller}/view/{$giftcard->giftcard_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function item_kit_headers(): array
{
    return [
        ['item_kit_id' => lang('Item_kits.kit')],
        ['item_kit_number' => lang('Item_kits.item_kit_number')],
        ['name' => lang('Item_kits.name')],
        ['description' => lang('Item_kits.description')],
        ['total_cost_price' => lang('Items.cost_price'), 'sortable' => false],
        ['total_unit_price' => lang('Items.unit_price'), 'sortable' => false],
    ];
}

/**
 * Get the header for the item kits tabular view
 */
function get_item_kits_manage_table_headers(): string
{
    return transform_headers(item_kit_headers());
}

/**
 * Get the html data row for the item kit
 */
function get_item_kit_data_row(object $item_kit): array
{
    $controller = get_controller();

    return [
        'item_kit_id'      => $item_kit->item_kit_id,
        'item_kit_number'  => $item_kit->item_kit_number,
        'name'             => $item_kit->name,
        'description'      => $item_kit->description,
        'total_cost_price' => to_currency($item_kit->total_cost_price),
        'total_unit_price' => to_currency($item_kit->total_unit_price),
        'edit'             => anchor(
            "{$controller}/view/{$item_kit->item_kit_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function parse_attribute_values(array $columns, array $row): array
{
    $attribute_values = [];

    foreach ($columns as $column) {
        if (array_key_exists($column, $row)) {
            $attribute_value  = explode('|', $row[$column]);
            $attribute_values = array_merge($attribute_values, $attribute_value);
        }
    }

    return $attribute_values;
}

/**
 * @param array $definition_names Array of definition_id => ['name' => name, 'type' => type] or definition_id => name
 */
function expand_attribute_values(array $definition_names, array $row): array
{
    $values = parse_attribute_values(['attribute_values', 'attribute_dtvalues', 'attribute_dvalues'], $row);

    $indexed_values = [];

    foreach ($values as $attribute_value) {
        $exploded_value = explode('_', $attribute_value);
        if (count($exploded_value) > 1) {
            $indexed_values[$exploded_value[0]] = $exploded_value[1];
        }
    }

    $attribute_values = [];

    foreach ($definition_names as $definition_id => $definitionInfo) {
        if (isset($indexed_values[$definition_id])) {
            $raw_value = $indexed_values[$definition_id];

            // Format DECIMAL attributes according to locale
            if (is_array($definitionInfo) && isset($definitionInfo['type']) && $definitionInfo['type'] === DECIMAL) {
                $attribute_values["{$definition_id}"] = to_decimals($raw_value);
            } else {
                $attribute_values["{$definition_id}"] = $raw_value;
            }
        } else {
            $attribute_values["{$definition_id}"] = '';
        }
    }

    return $attribute_values;
}

function attribute_definition_headers(): array
{
    return [
        ['definition_id' => lang('Attributes.definition_id')],
        ['definition_name' => lang('Attributes.definition_name')],
        ['definition_type' => lang('Attributes.definition_type')],
        ['definition_flags' => lang('Attributes.definition_flags')],
        ['definition_group' => lang('Attributes.definition_group')],
    ];
}

function get_attribute_definition_manage_table_headers(): string
{
    return transform_headers(attribute_definition_headers());
}

function get_attribute_definition_data_row(object $attribute_row): array
{
    $attribute  = model(Attribute::class);
    $controller = get_controller();

    if (count($attribute->getDefinitionFlags()) === 0) {
        $definition_flags = lang('Common.none_selected_text');
    } elseif ($attribute->definition_type === GROUP) {
        $definition_flags = '-';
    } else {
        $definition_flags = implode(', ', $attribute_row->definition_flags);
    }

    return [
        'definition_id'    => $attribute_row->definition_id,
        'definition_name'  => $attribute_row->definition_name,
        'definition_type'  => $attribute_row->definition_type,
        'definition_group' => $attribute_row->definition_group,
        'definition_flags' => $definition_flags,
        'edit'             => anchor(
            "{$controller}/view/{$attribute_row->definition_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function expense_category_headers(): array
{
    return [
        ['expense_category_id' => lang('Expenses_categories.category_id')],
        ['category_name' => lang('Expenses_categories.name')],
        ['category_description' => lang('Expenses_categories.description')],
    ];
}

/**
 * Get the header for the expense categories tabular view
 */
function get_expense_category_manage_table_headers(): string
{
    return transform_headers(expense_category_headers());
}

/**
 * Gets the html data row for the expense category
 */
function get_expense_category_data_row(object $expense_category): array
{
    $controller = get_controller();

    return [
        'expense_category_id'  => $expense_category->expense_category_id,
        'category_name'        => $expense_category->category_name,
        'category_description' => $expense_category->category_description,
        'edit'                 => anchor(
            "{$controller}/view/{$expense_category->expense_category_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function expense_headers(): array
{
    return [
        ['expense_id' => lang('Expenses.expense_id')],
        ['date' => lang('Expenses.date')],
        ['supplier_name' => lang('Expenses.supplier_name')],
        ['supplier_tax_code' => lang('Expenses.supplier_tax_code')],
        ['amount' => lang('Expenses.amount')],
        ['tax_amount' => lang('Expenses.tax_amount')],
        ['payment_type' => lang('Expenses.payment')],
        ['category_name' => lang('Expenses_categories.name')],
        ['description' => lang('Expenses.description')],
        ['created_by' => lang('Expenses.employee')],
    ];
}

/**
 * Get the header for the expenses tabular view
 */
function get_expenses_manage_table_headers(): string
{
    return transform_headers(expense_headers());
}

/**
 * Gets the html data row for the expenses
 */
function get_expenses_data_row(object $expense): array
{
    $controller = get_controller();

    return [
        'expense_id'        => $expense->expense_id,
        'date'              => to_datetime(strtotime($expense->date)),
        'supplier_name'     => $expense->supplier_name,
        'supplier_tax_code' => $expense->supplier_tax_code,
        'amount'            => to_currency($expense->amount),
        'tax_amount'        => to_currency($expense->tax_amount),
        'payment_type'      => $expense->payment_type,
        'category_name'     => $expense->category_name,
        'description'       => $expense->description,
        'created_by'        => $expense->first_name . ' ' . $expense->last_name,
        'edit'              => anchor(
            "{$controller}/view/{$expense->expense_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

/**
 * Get the html data last row for the expenses
 */
function get_expenses_data_last_row(object $expense): array
{
    $table_data_rows        = '';    // TODO: This variable is never used
    $sum_amount_expense     = 0;
    $sum_tax_amount_expense = 0;

    foreach ($expense->getResult() as $key => $expense) {
        $sum_amount_expense += $expense->amount;
        $sum_tax_amount_expense += $expense->tax_amount;
    }

    return [
        'expense_id' => '-',
        'date'       => lang('Sales.total'),
        'amount'     => to_currency($sum_amount_expense),
        'tax_amount' => to_currency($sum_tax_amount_expense),
    ];
}

/**
 * Get the expense payments summary
 */
function get_expenses_manage_payments_summary(array $payments, ResultInterface $expenses): string    // TODO: $expenses is passed but never used.
{
    $table = '<div id="report_summary">';

    foreach ($payments as $key => $payment) {
        $amount = $payment['amount'];
        $table .= '<div class="summary_row">' . $payment['payment_type'] . ': ' . to_currency($amount) . '</div>';
    }

    $table .= '</div>';

    return $table;
}

/**
 * Get the headers for the projects tabular view
 */
function project_headers(): array
{
    return [
        ['project_id' => lang('Projects.project_id')],
        ['project_number' => lang('Projects.project_number')],
        ['project_name' => lang('Projects.project_name')],
        ['customer_company_name' => lang('Projects.customer')],
        ['manager_name' => lang('Projects.manager')],
        ['status' => lang('Projects.status')],
        ['start_date' => lang('Projects.start_date')],
        ['end_date' => lang('Projects.end_date')],
        ['budget_amount' => lang('Projects.budget_amount')],
        ['total_sales' => lang('Projects.sales_total')],
        ['total_expenses' => lang('Projects.expenses_total')],
        ['profit' => lang('Projects.profit')],
        ['description' => lang('Projects.description')],
    ];
}

/**
 * Get the header for the projects tabular view
 */
function get_projects_manage_table_headers(): string
{
    return transform_headers(project_headers());
}

/**
 * Gets the html data row for the projects
 */
function get_projects_data_row(object $project): array
{
    $controller = get_controller();

    $customerName  = $project->customer_company_name ?? ($project->customer_first_name . ' ' . $project->customer_last_name);
    $managerName   = $project->manager_first_name . ' ' . $project->manager_last_name;
    $totalSales    = (float) ($project->total_sales ?? 0);
    $totalExpenses = (float) ($project->total_expenses ?? 0);
    $profit        = $totalSales - $totalExpenses;

    return [
        'project_id'            => $project->project_id,
        'project_number'        => $project->project_number,
        'project_name'          => $project->project_name,
        'customer_company_name' => $customerName,
        'manager_name'          => $managerName,
        'status'                => ucfirst($project->status),
        'start_date'            => to_datetime(strtotime($project->start_date)),
        'end_date'              => ! empty($project->end_date) ? to_datetime(strtotime($project->end_date)) : '',
        'budget_amount'         => to_currency($project->budget_amount),
        'total_sales'           => to_currency($totalSales),
        'total_expenses'        => to_currency($totalExpenses),
        'profit'                => to_currency($profit),
        'description'           => $project->description,
        'edit'                  => anchor(
            "{$controller}/view/{$project->project_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

/**
 * Get the html data last row for the projects
 */
function get_projects_data_last_row(object $project): array
{
    $sum_budget   = 0;
    $sum_sales    = 0;
    $sum_expenses = 0;

    foreach ($project->getResult() as $key => $project) {
        $sum_budget += (float) $project->budget_amount;
        $sum_sales += (float) ($project->total_sales ?? 0);
        $sum_expenses += (float) ($project->total_expenses ?? 0);
    }

    return [
        'project_id'     => '-',
        'project_number' => lang('Sales.total'),
        'budget_amount'  => to_currency($sum_budget),
        'total_sales'    => to_currency($sum_sales),
        'total_expenses' => to_currency($sum_expenses),
        'profit'         => to_currency($sum_sales - $sum_expenses),
    ];
}

function currency_headers(): array
{
    return [
        ['currency_id' => lang('Currencies.currency_id')],
        ['currency_code' => lang('Currencies.currency_code')],
        ['currency_name' => lang('Currencies.currency_name')],
        ['currency_symbol' => lang('Currencies.currency_symbol')],
        ['exchange_rate' => lang('Currencies.exchange_rate')],
        ['is_default' => lang('Currencies.is_default')],
    ];
}

/**
 * Get the header for the currencies tabular view
 */
function get_currencies_manage_table_headers(): string
{
    return transform_headers(currency_headers());
}

/**
 * Gets the html data row for the currencies
 */
function get_currencies_data_row(object $currency): array
{
    $controller = get_controller();

    return [
        'currency_id'     => $currency->currency_id,
        'currency_code'   => $currency->currency_code,
        'currency_name'   => $currency->currency_name,
        'currency_symbol' => $currency->currency_symbol,
        'exchange_rate'   => $currency->exchange_rate,
        'is_default'      => $currency->is_default === 1 ? '<span class="glyphicon glyphicon-ok"></span>' : '<span class="glyphicon glyphicon-remove"></span>',
        'edit'            => anchor(
            "{$controller}/view/{$currency->currency_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function discount_headers(): array
{
    return [
        ['discount_id' => lang('Discounts.discount_id')],
        ['discount_code' => lang('Discounts.discount_code')],
        ['discount_name' => lang('Discounts.discount_name')],
        ['discount_type' => lang('Discounts.discount_type')],
        ['discount_value' => lang('Discounts.discount_value')],
        ['min_purchase' => lang('Discounts.min_purchase')],
        ['max_uses' => lang('Discounts.max_uses')],
        ['used_count' => lang('Discounts.used_count')],
        ['start_date' => lang('Discounts.start_date')],
        ['end_date' => lang('Discounts.end_date')],
        ['applies_to' => lang('Discounts.applies_to')],
        ['is_active' => lang('Discounts.is_active')],
    ];
}

/**
 * Get the header for the discounts tabular view
 */
function get_discounts_manage_table_headers(): string
{
    return transform_headers(discount_headers());
}

/**
 * Gets the html data row for the discounts
 */
function get_discounts_data_row(object $discount): array
{
    $controller = get_controller();

    return [
        'discount_id'    => $discount->discount_id,
        'discount_code'  => $discount->discount_code,
        'discount_name'  => $discount->discount_name,
        'discount_type'  => ucfirst($discount->discount_type),
        'discount_value' => $discount->discount_type === 'percentage' ? $discount->discount_value . '%' : to_currency($discount->discount_value),
        'min_purchase'   => to_currency($discount->min_purchase),
        'max_uses'       => $discount->max_uses ?? '∞',
        'used_count'     => $discount->used_count,
        'start_date'     => to_datetime(strtotime($discount->start_date)),
        'end_date'       => ! empty($discount->end_date) ? to_datetime(strtotime($discount->end_date)) : '—',
        'applies_to'     => ucfirst($discount->applies_to),
        'is_active'      => $discount->is_active === 1 ? '<span class="glyphicon glyphicon-ok"></span>' : '<span class="glyphicon glyphicon-remove"></span>',
        'edit'           => anchor(
            "{$controller}/view/{$discount->discount_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function cashup_headers(): array
{
    return [
        ['cashup_id' => lang('Cashups.id')],
        ['open_date' => lang('Cashups.opened_date')],
        ['open_employee_id' => lang('Cashups.open_employee')],
        ['open_amount_cash' => lang('Cashups.open_amount_cash')],
        ['transfer_amount_cash' => lang('Cashups.transfer_amount_cash')],
        ['close_date' => lang('Cashups.closed_date')],
        ['close_employee_id' => lang('Cashups.close_employee')],
        ['closed_amount_cash' => lang('Cashups.closed_amount_cash')],
        ['note' => lang('Cashups.note')],
        ['closed_amount_due' => lang('Cashups.closed_amount_due')],
        ['closed_amount_card' => lang('Cashups.closed_amount_card') . ' / ' . lang('Sales.wallet')],
        ['closed_amount_check' => lang('Cashups.closed_amount_check')],
        ['closed_amount_total' => lang('Cashups.closed_amount_total')],
    ];
}

/**
 * Get the header for the cashup tabular view
 */
function get_cashups_manage_table_headers(): string
{
    $headers = cashup_headers();

    return transform_headers($headers);
}

/**
 * Gets the html data row for the cashups
 */
function get_cash_up_data_row(object $cash_up): array
{
    $controller = get_controller();

    return [
        'cashup_id'            => $cash_up->cashup_id,
        'open_date'            => to_datetime(strtotime($cash_up->open_date)),
        'open_employee_id'     => $cash_up->open_first_name . ' ' . $cash_up->open_last_name,
        'open_amount_cash'     => to_currency($cash_up->open_amount_cash),
        'transfer_amount_cash' => to_currency($cash_up->transfer_amount_cash),
        'close_date'           => to_datetime(strtotime($cash_up->close_date)),
        'close_employee_id'    => $cash_up->close_first_name . ' ' . $cash_up->close_last_name,
        'closed_amount_cash'   => to_currency($cash_up->closed_amount_cash),
        'note'                 => $cash_up->note ? '<span class="glyphicon glyphicon-ok"></span>' : '<span class="glyphicon glyphicon-remove"></span>',
        'closed_amount_due'    => to_currency($cash_up->closed_amount_due),
        'closed_amount_card'   => to_currency($cash_up->closed_amount_card),
        'closed_amount_check'  => to_currency($cash_up->closed_amount_check),
        'closed_amount_total'  => to_currency($cash_up->closed_amount_total),
        'edit'                 => anchor(
            "{$controller}/view/{$cash_up->cashup_id}",
            '<span class="glyphicon glyphicon-edit"></span>',
            [
                'class'           => 'modal-dlg',
                'data-btn-submit' => lang('Common.submit'),
                'title'           => lang(ucfirst($controller) . '.update'),
            ],
        ),
    ];
}

function payroll_headers(): array
{
    return [
        ['payroll_run_id' => 'ID'],
        ['period' => lang('Payroll.period')],
        ['employees_count' => lang('Payroll.total_employees'), 'sortable' => false],
        ['total_net' => lang('Payroll.total_net_pay'), 'sortable' => false],
        ['status' => lang('Payroll.status')],
        ['notes' => lang('Payroll.notes'), 'sortable' => false],
    ];
}

function get_payroll_manage_table_headers(): string
{
    return transform_headers(payroll_headers(), true, true);
}

/**
 * Returns the right-most part of the controller name
 */
function get_controller(): string
{
    $router                = service('router');
    $controller_name       = strtolower($router->controllerName());
    $controller_name_parts = explode('\\', $controller_name);

    return end($controller_name_parts);
}

/**
 * Restores filter values from the URL query string.
 *
 * @param IncomingRequest $request The request object
 *
 * @return array Array with 'start_date', 'end_date', and 'selected_filters' keys
 */
function restoreTableFilters(IncomingRequest $request): array
{
    $startDate  = $request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $endDate    = $request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $urlFilters = $request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    return array_filter([
        'start_date'       => $startDate ?: null,
        'end_date'         => $endDate ?: null,
        'selected_filters' => $urlFilters ?? [],
    ], static fn ($value) => $value !== null && $value !== []);
}
