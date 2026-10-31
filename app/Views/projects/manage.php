<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $selected_filters
 * @var array $config
 * @var string|null $start_date
 * @var string|null $end_date
 * @var string|null $status
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#filters').on('hidden.bs.select', function(e) {
            table_support.refresh();
        });

        <?= view('partial/daterangepicker') ?>

        $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
            table_support.refresh();
        });

        <?= view('partial/bootstrap_tables_locale') ?>

        <?php if (isset($start_date) && $start_date): ?>
        start_date = "<?= esc($start_date) ?>";
        <?php endif; ?>
        <?php if (isset($end_date) && $end_date): ?>
        end_date = "<?= esc($end_date) ?>";
        <?php endif; ?>

        table_support.init({
            resource: '<?= esc($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'project_id',
            onLoadSuccess: function(response) {
                if ($("#table tbody tr").length > 1) {
                    $("#table tbody tr:last td:first").html("");
                    $("#table tbody tr:last").css('font-weight', 'bold');
                }
            },
            queryParams: function() {
                return $.extend(arguments[0], {
                    "start_date": start_date,
                    "end_date": end_date,
                    "status": $("#status_filter").val(),
                    "filters": $("#filters").val()
                });
            }
        });

    });
</script>
<?= view('partial/table_filter_persistence') ?>

<div id="title_bar" class="print_hide btn-toolbar">
    <a href="<?= base_url('customers') ?>" class="btn btn-default btn-sm pull-right">
        <span class="glyphicon glyphicon-list">&nbsp;</span><?= lang('Module.customers') ?>
    </a>
    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="customers/view" title="<?= lang('Customers.new') ?>">
        <span class="glyphicon glyphicon-plus">&nbsp;</span><?= lang('Customers.new') ?>
    </button>
    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang(ucfirst($controller_name) . '.new') ?>">
        <span class="glyphicon glyphicon-tags">&nbsp;</span><?= lang(ucfirst($controller_name) . '.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide">
            <span class="glyphicon glyphicon-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
        <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']) ?>
        <select id="status_filter" class="form-control input-sm selectpicker show-menu-arrow" data-width="fit">
            <option value=""><?= lang('Common.all_statuses') ?></option>
            <option value="active"><?= lang('Projects.status_active') ?></option>
            <option value="completed"><?= lang('Projects.status_completed') ?></option>
            <option value="cancelled"><?= lang('Projects.status_cancelled') ?></option>
            <option value="on_hold"><?= lang('Projects.status_on_hold') ?></option>
        </select>
        <?= form_multiselect('filters[]', esc($filters), $selected_filters ?? [], [
            'id'                        => 'filters',
            'data-none-selected-text'   => lang('Common.none_selected_text'),
            'class'                     => 'selectpicker show-menu-arrow',
            'data-selected-text-format' => 'count > 1',
            'data-style'                => 'btn-default btn-sm',
            'data-width'                => 'fit'
        ]) ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
