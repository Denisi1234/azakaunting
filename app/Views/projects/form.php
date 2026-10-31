<?php
/**
 * @var object $projects_info
 * @var array $customers
 * @var array $employees
 * @var array $statuses
 * @var array $linked_sales
 * @var array $linked_expenses
 * @var float $linked_sales_total
 * @var float $linked_expenses_total
 * @var string $controller_name
 * @var array $config
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("projects/save/$projects_info->project_id", ['id' => 'projects_edit_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="project_basic_info">

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.info'), 'projects_info', ['class' => 'control-label col-xs-3']) ?>
            <?= form_label(!empty($projects_info->project_id) ? lang('Projects.project_id') . " $projects_info->project_id" : '', 'projects_info_id', ['class' => 'control-label col-xs-8', 'style' => 'text-align: left']) ?>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.project_number'), 'project_number', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'project_number',
                    'id'    => 'project_number',
                    'class' => 'form-control input-sm',
                    'value' => $projects_info->project_number
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.project_name'), 'project_name', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'project_name',
                    'id'    => 'project_name',
                    'class' => 'form-control input-sm',
                    'value' => $projects_info->project_name
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.customer'), 'customer_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('customer_id', $customers, $projects_info->customer_id, ['class' => 'form-control', 'id' => 'customer_id']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.manager'), 'employee_id', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?php $canAssignEmployee = $this->employee->has_grant('employees', $this->employee->get_logged_in_employee_info()->person_id); ?>
                <?php if ($canAssignEmployee): ?>
                    <?= form_dropdown('employee_id', $employees, $projects_info->employee_id, 'id="employee_id" class="form-control"') ?>
                <?php else: ?>
                    <?= form_hidden('employee_id', $projects_info->employee_id) ?>
                    <?= form_input(['name' => 'employee_name', 'value' => $employees[$projects_info->employee_id] ?? '', 'class' => 'form-control', 'readonly' => 'readonly']) ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.status'), 'status', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('status', $statuses, $projects_info->status, ['class' => 'form-control', 'id' => 'status']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.start_date'), 'start_date', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                    <?= form_input([
                        'name'  => 'start_date',
                        'class' => 'form-control input-sm datetime',
                        'value' => to_datetime(strtotime($projects_info->start_date))
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.end_date'), 'end_date', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                    <?= form_input([
                        'name'  => 'end_date',
                        'class' => 'form-control input-sm datetime',
                        'value' => !empty($projects_info->end_date) ? to_datetime(strtotime($projects_info->end_date)) : ''
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.budget_amount'), 'budget_amount', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group input-group-sm">
                    <?php if (!is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                    <?= form_input([
                        'name'  => 'budget_amount',
                        'id'    => 'budget_amount',
                        'class' => 'form-control input-sm',
                        'value' => to_currency_no_money($projects_info->budget_amount)
                    ]) ?>
                    <?php if (is_right_side_currency_symbol()): ?>
                        <span class="input-group-addon input-sm"><b><?= esc($config['currency_symbol']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Projects.description'), 'description', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_textarea([
                    'name'  => 'description',
                    'id'    => 'description',
                    'class' => 'form-control input-sm',
                    'value' => $projects_info->description
                ]) ?>
            </div>
        </div>

        <?php if (!empty($projects_info->project_id)) { ?>
            <div class="form-group form-group-sm">
                <?= form_label(lang('Projects.is_deleted') . ':', 'deleted', ['class' => 'control-label col-xs-3']) ?>
                <div class="col-xs-5">
                    <?= form_checkbox([
                        'name'    => 'deleted',
                        'id'      => 'deleted',
                        'value'   => 1,
                        'checked' => $projects_info->deleted == 1
                    ]) ?>
                </div>
            </div>
        <?php } ?>

    </fieldset>
<?= form_close() ?>

<?php if (!empty($projects_info->project_id)): ?>
    <div id="linked_transactions">
        <h4><?= lang('Projects.linked_transactions') ?></h4>
        <div class="row">
            <div class="col-md-6">
                <h5><?= lang('Projects.sales_total') ?>: <?= to_currency($linked_sales_total) ?></h5>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th><?= lang('Sales.id') ?></th>
                            <th><?= lang('Sales.date') ?></th>
                            <th><?= lang('Sales.total') ?></th>
                            <th><?= lang('Common.action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linked_sales as $sale): ?>
                            <tr>
                                <td><?= esc($sale->sale_id) ?></td>
                                <td><?= to_datetime(strtotime($sale->sale_time)) ?></td>
                                <td><?= to_currency($sale->total) ?></td>
                                <td>
                                    <button class="btn btn-danger btn-xs unlink-sale" data-sale-id="<?= esc($sale->sale_id) ?>">
                                        <span class="glyphicon glyphicon-unlink"></span><?= lang('Projects.unlink_sale') ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <h5><?= lang('Projects.expenses_total') ?>: <?= to_currency($linked_expenses_total) ?></h5>
                <table class="table table-bordered table-striped table-sm">
                    <thead>
                        <tr>
                            <th><?= lang('Expenses.expense_id') ?></th>
                            <th><?= lang('Expenses.date') ?></th>
                            <th><?= lang('Expenses.amount') ?></th>
                            <th><?= lang('Common.action') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linked_expenses as $expense): ?>
                            <tr>
                                <td><?= esc($expense->expense_id) ?></td>
                                <td><?= to_datetime(strtotime($expense->date)) ?></td>
                                <td><?= to_currency($expense->amount + $expense->tax_amount) ?></td>
                                <td>
                                    <button class="btn btn-danger btn-xs unlink-expense" data-expense-id="<?= esc($expense->expense_id) ?>">
                                        <span class="glyphicon glyphicon-unlink"></span><?= lang('Projects.unlink_expense') ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script type="text/javascript">
    // Validation and submit handling
    $(document).ready(function() {
        <?= view('partial/datepicker_locale') ?>

        $('#projects_edit_form').validate($.extend({
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    success: function(response) {
                        dialog_support.hide();
                        table_support.handle_submit("<?= esc($controller_name) ?>", response);
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: '#error_message_box',

            ignore: '',

            rules: {
                project_number: 'required',
                project_name: 'required',
                start_date: {
                    required: true
                },
                budget_amount: {
                    remote: "<?= "$controller_name/checkNumeric" ?>"
                }
            },

            messages: {
                project_number: "<?= lang('Projects.project_number_required') ?>",
                project_name: "<?= lang('Projects.name_required') ?>",
                start_date: {
                    required: "<?= lang('Projects.date_required') ?>"
                },
                budget_amount: {
                    remote: "<?= lang('Projects.amount_required') ?>"
                }
            }
        }, form_support.error));

        // Unlink sale
        $('.unlink-sale').click(function() {
            var saleId = $(this).data('sale-id');
            $.post('<?= base_url('projects/unlinkSale/') ?>' + saleId, function(response) {
                if (response.success) {
                    table_support.handle_submit("<?= esc($controller_name) ?>", response);
                }
            }, 'json');
        });

        // Unlink expense
        $('.unlink-expense').click(function() {
            var expenseId = $(this).data('expense-id');
            $.post('<?= base_url('projects/unlinkExpense/') ?>' + expenseId, function(response) {
                if (response.success) {
                    table_support.handle_submit("<?= esc($controller_name) ?>", response);
                }
            }, 'json');
        });
    });
</script>
