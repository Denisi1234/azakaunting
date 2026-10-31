<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $selected_filters
 * @var array $config
 * @var bool $can_manage
 * @var string|null $start_date
 * @var string|null $end_date
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
            uniqueId: 'timeclock_id',
            queryParams: function() {
                return $.extend(arguments[0], {
                    "end_date": end_date,
                    "filters": $("#filters").val(),
                    "start_date": start_date
                });
            }
        });

        $('#clock_in_button').on('click', function() {
            $.post('<?= esc("$controller_name/clockIn") ?>', function(response) {
                alert(response.message);
                table_support.refresh();
            }, 'json');
        });

        $('#clock_out_button').on('click', function() {
            $.post('<?= esc("$controller_name/clockOut") ?>', function(response) {
                alert(response.message);
                table_support.refresh();
            }, 'json');
        });
    });
</script>
<?= view('partial/table_filter_persistence') ?>

<?= view('partial/print_receipt', ['print_after_sale' => false, 'selected_printer' => 'takings_printer']) ?>

<div id="title_bar" class="print_hide btn-toolbar">
    <?php if ($can_manage): ?>
        <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang('Timeclocks.new') ?>">
            <span class="glyphicon glyphicon-time">&nbsp;</span><?= lang('Timeclocks.new') ?>
        </button>
    <?php endif; ?>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <?php if ($can_manage): ?>
            <button id="delete" class="btn btn-default btn-sm print_hide">
                <span class="glyphicon glyphicon-trash">&nbsp;</span><?= lang('Common.delete') ?>
            </button>
        <?php endif; ?>
        <button id="clock_in_button" class="btn btn-success btn-sm print_hide">
            <span class="glyphicon glyphicon-log-in">&nbsp;</span><?= lang('Timeclocks.clock_in') ?>
        </button>
        <button id="clock_out_button" class="btn btn-warning btn-sm print_hide">
            <span class="glyphicon glyphicon-log-out">&nbsp;</span><?= lang('Timeclocks.clock_out') ?>
        </button>
        <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']) ?>
        <?= form_multiselect('filters[]', $filters, $selected_filters ?? [], [
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
