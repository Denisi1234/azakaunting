<?php
/**
 * @var object $discounts_info
 * @var string $controller_name
 * @var array  $config
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open("discounts/save/{$discounts_info->discount_id}", ['id' => 'discounts_edit_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="discount_basic_info">

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.discount_code'), 'discount_code', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'discount_code',
                    'id'    => 'discount_code',
                    'class' => 'form-control input-sm',
                    'value' => $discounts_info->discount_code,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.discount_name'), 'discount_name', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'discount_name',
                    'id'    => 'discount_name',
                    'class' => 'form-control input-sm',
                    'value' => $discounts_info->discount_name,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.discount_type'), 'discount_type', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('discount_type', [
                    'percentage' => lang('Discounts.percentage'),
                    'fixed'      => lang('Discounts.fixed'),
                ], $discounts_info->discount_type, ['class' => 'form-control', 'id' => 'discount_type']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.discount_value'), 'discount_value', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'discount_value',
                    'id'    => 'discount_value',
                    'class' => 'form-control input-sm',
                    'value' => $discounts_info->discount_value,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.min_purchase'), 'min_purchase', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'min_purchase',
                    'id'    => 'min_purchase',
                    'class' => 'form-control input-sm',
                    'value' => $discounts_info->min_purchase,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.max_uses'), 'max_uses', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'max_uses',
                    'id'    => 'max_uses',
                    'class' => 'form-control input-sm',
                    'value' => $discounts_info->max_uses,
                ]) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.start_date'), 'start_date', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                    <?= form_input([
                        'name'  => 'start_date',
                        'class' => 'form-control input-sm datetime',
                        'value' => to_datetime(strtotime($discounts_info->start_date)),
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.end_date'), 'end_date', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon input-sm"><span class="glyphicon glyphicon-calendar"></span></span>
                    <?= form_input([
                        'name'  => 'end_date',
                        'class' => 'form-control input-sm datetime',
                        'value' => ! empty($discounts_info->end_date) ? to_datetime(strtotime($discounts_info->end_date)) : '',
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.applies_to'), 'applies_to', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('applies_to', [
                    'all'        => lang('Discounts.all'),
                    'items'      => lang('Discounts.items'),
                    'categories' => lang('Discounts.categories'),
                ], $discounts_info->applies_to, ['class' => 'form-control', 'id' => 'applies_to']) ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Discounts.is_active'), 'is_active', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-5">
                <?= form_checkbox([
                    'name'    => 'is_active',
                    'id'      => 'is_active',
                    'value'   => 1,
                    'checked' => $discounts_info->is_active === 1,
                ]) ?>
            </div>
        </div>

        <?php if (! empty($discounts_info->discount_id) && $discounts_info->discount_id !== NEW_ENTRY) { ?>
            <div class="form-group form-group-sm">
                <?= form_label(lang('Discounts.is_deleted') . ':', 'deleted', ['class' => 'control-label col-xs-3']) ?>
                <div class="col-xs-5">
                    <?= form_checkbox([
                        'name'    => 'deleted',
                        'id'      => 'deleted',
                        'value'   => 1,
                        'checked' => $discounts_info->deleted === 1,
                    ]) ?>
                </div>
            </div>
        <?php } ?>

    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        <?= view('partial/datepicker_locale') ?>

        $('#discounts_edit_form').validate($.extend({
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
                discount_code: 'required',
                discount_name: 'required',
                discount_value: 'required'
            },
            messages: {
                discount_code: "<?= lang('Discounts.discount_code_required') ?>",
                discount_name: "<?= lang('Discounts.discount_name_required') ?>",
                discount_value: "<?= lang('Discounts.discount_value_required') ?>"
            }
        }, form_support.error));
    });
</script>
