<?php
/**
 * @var object $person_info
 */
?>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.employee_number'), 'employee_number', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'employee_number',
            'id'    => 'employee_number',
            'class' => 'form-control input-sm',
            'value' => $person_info->employee_number
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.job_title'), 'job_title', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'job_title',
            'id'    => 'job_title',
            'class' => 'form-control input-sm',
            'value' => $person_info->job_title
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.department'), 'department', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'department',
            'id'    => 'department',
            'class' => 'form-control input-sm',
            'value' => $person_info->department
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.hire_date'), 'hire_date', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'hire_date',
            'id'    => 'hire_date',
            'type'  => 'date',
            'class' => 'form-control input-sm',
            'value' => $person_info->hire_date
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.employment_type'), 'employment_type', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_dropdown(
            'employment_type',
            [
                ''          => '',
                'full_time' => lang('Employees.employment_full_time'),
                'part_time' => lang('Employees.employment_part_time'),
                'contract'  => lang('Employees.employment_contract'),
                'intern'    => lang('Employees.employment_intern'),
                'temporary' => lang('Employees.employment_temporary')
            ],
            $person_info->employment_type,
            ['class' => 'form-control input-sm', 'id' => 'employment_type']
        ) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.hourly_rate'), 'hourly_rate', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'hourly_rate',
            'id'    => 'hourly_rate',
            'class' => 'form-control input-sm',
            'value' => $person_info->hourly_rate
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.emergency_contact_name'), 'emergency_contact_name', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'emergency_contact_name',
            'id'    => 'emergency_contact_name',
            'class' => 'form-control input-sm',
            'value' => $person_info->emergency_contact_name
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.emergency_contact_phone'), 'emergency_contact_phone', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'emergency_contact_phone',
            'id'    => 'emergency_contact_phone',
            'class' => 'form-control input-sm',
            'value' => $person_info->emergency_contact_phone
        ]) ?>
    </div>
</div>

<div class="form-group form-group-sm">
    <?= form_label(lang('Employees.emergency_contact_relation'), 'emergency_contact_relation', ['class' => 'control-label col-xs-3']) ?>
    <div class="col-xs-8">
        <?= form_input([
            'name'  => 'emergency_contact_relation',
            'id'    => 'emergency_contact_relation',
            'class' => 'form-control input-sm',
            'value' => $person_info->emergency_contact_relation
        ]) ?>
    </div>
</div>
