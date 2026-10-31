<?= view('partial/header') ?>

<script type="text/javascript">
$(document).ready(function() {
    <?= view('partial/bootstrap_tables_locale') ?>

    table_support.init({
        resource: '<?= esc($controller_name) ?>',
        headers: <?= $table_headers ?>,
        pageSize: <?= $config['lines_per_page'] ?>,
        uniqueId: 'payroll_run_id'
    });

    window.runApprove = function(id) {
        if (confirm("<?= lang('Payroll.confirm_approve') ?>")) {
            $.post("<?= site_url('payroll/approve/') ?>" + id, {}, function(response) {
                if (response.success) {
                    table_support.refresh();
                } else {
                    alert(response.message);
                }
            }, 'json');
        }
    };

    window.runPay = function(id) {
        if (confirm("<?= lang('Payroll.confirm_pay') ?>")) {
            const paymentMethod = prompt("<?= lang('Payroll.payment_method') ?>", "Bank transfer");
            if (paymentMethod === null || paymentMethod.trim() === '') {
                return;
            }
            const paymentReference = prompt("<?= lang('Payroll.payment_reference') ?>", "") || '';
            $.post("<?= site_url('payroll/pay/') ?>" + id, {payment_method: paymentMethod, payment_reference: paymentReference}, function(response) {
                if (response.success) {
                    table_support.refresh();
                } else {
                    alert(response.message);
                }
            }, 'json');
        }
    };
});
</script>

<div id="title_bar" class="btn-toolbar">
    <a href="<?= site_url('payroll/view') ?>" class="btn btn-info btn-sm pull-right">
        <span class="glyphicon glyphicon-plus">&nbsp;</span><?= lang('Payroll.new_run') ?>
    </a>
    <a href="<?= site_url('salary_structures') ?>" class="btn btn-default btn-sm pull-right" style="margin-right: 5px;">
        <span class="glyphicon glyphicon-user">&nbsp;</span><?= lang('Payroll.salary_structures') ?>
    </a>
    <a href="<?= site_url('payroll/report') ?>" class="btn btn-default btn-sm pull-right" style="margin-right: 5px;">
        <span class="glyphicon glyphicon-stats">&nbsp;</span><?= lang('Payroll.report') ?>
    </a>
    <a href="<?= site_url('payroll/settings') ?>" class="btn btn-default btn-sm pull-right" style="margin-right: 5px;">
        <span class="glyphicon glyphicon-cog">&nbsp;</span><?= lang('Payroll.settings') ?>
    </a>
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
