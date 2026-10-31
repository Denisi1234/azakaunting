<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array  $filters
 * @var array  $selected_filters
 * @var array  $config
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    $(document).ready(function() {
        table_support.init({
            resource: '<?= esc($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: <?= $config['lines_per_page'] ?>,
            uniqueId: 'discount_id',
            onLoadSuccess: function(response) {
                if ($("#table tbody tr").length > 1) {
                    $("#table tbody tr:last td:first").html("");
                    $("#table tbody tr:last").css('font-weight', 'bold');
                }
            }
        });
    });
</script>

<div id="title_bar" class="print_hide btn-toolbar">
    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "{$controller_name}/view" ?>" title="<?= lang(ucfirst($controller_name) . '.new') ?>">
        <span class="glyphicon glyphicon-plus">&nbsp;</span><?= lang(ucfirst($controller_name) . '.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide">
            <span class="glyphicon glyphicon-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
