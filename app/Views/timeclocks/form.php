<?php
/**
 * @var object $timeclock_info
 * @var array $employees
 * @var array $config
 */
?>

<div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
<ul id="error_message_box" class="error_message_box"></ul>

<?= form_open('timeclocks/save/' . $timeclock_info->timeclock_id, ['id' => 'timeclocks_edit_form', 'class' => 'form-horizontal']) ?>
    <fieldset id="timeclock_info">
        <div class="form-group form-group-sm">
            <?= form_label(lang('Timeclocks.employee'), 'person_id', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_dropdown('person_id', $employees, $timeclock_info->person_id, 'id="person_id" class="form-control input-sm"') ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Timeclocks.clock_in'), 'clock_in', ['class' => 'control-label col-xs-3 text-danger required']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'clock_in',
                    'id'    => 'clock_in',
                    'class' => 'form-control input-sm',
                    'value' => $timeclock_info->clock_in
                ], '', '', 'datetime-local') ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Timeclocks.clock_out'), 'clock_out', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_input([
                    'name'  => 'clock_out',
                    'id'    => 'clock_out',
                    'class' => 'form-control input-sm',
                    'value' => $timeclock_info->clock_out
                ], '', '', 'datetime-local') ?>
            </div>
        </div>

        <div class="form-group form-group-sm">
            <?= form_label(lang('Timeclocks.note'), 'note', ['class' => 'control-label col-xs-3']) ?>
            <div class="col-xs-6">
                <?= form_textarea([
                    'name'  => 'note',
                    'id'    => 'note',
                    'class' => 'form-control input-sm',
                    'value' => $timeclock_info->note,
                    'rows'  => 3
                ]) ?>
            </div>
        </div>
    </fieldset>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {
        const submit_form = function() {
            $(this).ajaxSubmit({
                success: function(response) {
                    dialog_support.hide();
                    table_support.handle_submit('<?= esc('timeclocks') ?>', response);
                },
                dataType: 'json'
            });
        };

        $('#timeclocks_edit_form').validate($.extend({
            submitHandler: function(form) {
                submit_form.call(form);
            },
            rules: {
                person_id: {
                    required: true
                },
                clock_in: {
                    required: true
                },
                note: {
                    maxlength: 255
                }
            },
            messages: {
                person_id: {
                    required: <?= json_encode(lang('Timeclocks.employee_required')) ?>
                },
                clock_in: {
                    required: <?= json_encode(lang('Timeclocks.clock_in_required')) ?>
                },
                note: {
                    maxlength: <?= json_encode(lang('Timeclocks.note_maxlength')) ?>
                }
            }
        }, form_support.error));
    });
</script>
