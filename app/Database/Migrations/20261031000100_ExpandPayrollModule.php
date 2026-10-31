<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandPayrollModule extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'payroll_component_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'component_code'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'component_name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'component_type'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'calculation_method'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'fixed'],
            'default_amount'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'percentage_base'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'taxable'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'statutory'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'active'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'sort_order'           => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
        ]);
        $this->forge->addKey('payroll_component_id', true);
        $this->forge->addUniqueKey('component_code');
        $this->forge->createTable('payroll_components', true);

        $this->forge->addField([
            'employee_payroll_component_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'                   => ['type' => 'INT', 'constraint' => 11],
            'payroll_component_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount'                        => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'rate'                          => ['type' => 'DECIMAL', 'constraint' => '8,4', 'default' => 0],
            'effective_from'                => ['type' => 'DATE'],
            'effective_to'                  => ['type' => 'DATE', 'null' => true],
            'notes'                         => ['type' => 'TEXT', 'null' => true],
            'active'                        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('employee_payroll_component_id', true);
        $this->forge->addKey(['employee_id', 'effective_from']);
        $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('payroll_component_id', 'payroll_components', 'payroll_component_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('employee_payroll_components', true);

        $this->forge->addField([
            'payroll_item_line_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'payroll_item_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'payroll_component_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'component_code'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'description'          => ['type' => 'VARCHAR', 'constraint' => 160],
            'component_type'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'quantity'             => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 1],
            'rate'                 => ['type' => 'DECIMAL', 'constraint' => '15,4', 'default' => 0],
            'amount'               => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'taxable'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('payroll_item_line_id', true);
        $this->forge->addKey('payroll_item_id');
        $this->forge->addForeignKey('payroll_item_id', 'payroll_items', 'payroll_item_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payroll_item_lines', true);

        $this->forge->addField([
            'payroll_profile_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'            => ['type' => 'INT', 'constraint' => 11],
            'tax_status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'resident'],
            'fund_code'              => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'NSSF'],
            'tax_identifier'         => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'social_security_number' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'primary_employment'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'employee_bank_name'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'employee_bank_branch'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'employee_bank_account'  => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        ]);
        $this->forge->addKey('payroll_profile_id', true);
        $this->forge->addUniqueKey('employee_id');
        $this->forge->addForeignKey('employee_id', 'employees', 'person_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payroll_employee_profiles', true);

        $this->forge->addField([
            'payroll_setting_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'country_code'       => ['type' => 'VARCHAR', 'constraint' => 2, 'default' => 'TZ'],
            'jurisdiction'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MAINLAND'],
            'employer_sector'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PRIVATE'],
            'employee_count'     => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'nssf_employee_rate' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 10],
            'nssf_employer_rate' => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 10],
            'sdl_rate'           => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 3.5],
            'sdl_employee_limit' => ['type' => 'INT', 'constraint' => 11, 'default' => 10],
            'wcf_rate'           => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 0],
            'updated_by'         => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('payroll_setting_id', true);
        $this->forge->createTable('payroll_settings', true);

        $this->forge->addField([
            'payroll_tax_band_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'jurisdiction'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'MAINLAND'],
            'tax_status'          => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'resident'],
            'effective_from'      => ['type' => 'DATE'],
            'effective_to'        => ['type' => 'DATE', 'null' => true],
            'income_from'         => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'income_to'           => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'base_tax'            => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'rate'                => ['type' => 'DECIMAL', 'constraint' => '6,3', 'default' => 0],
        ]);
        $this->forge->addKey('payroll_tax_band_id', true);
        $this->forge->addKey(['jurisdiction', 'tax_status', 'effective_from']);
        $this->forge->createTable('payroll_tax_bands', true);

        $this->forge->addColumn('payroll_items', [
            'gross_pay'              => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'taxable_pay'            => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'paye'                   => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'employee_contributions' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'employer_contributions' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'employer_cost'          => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
        ]);

        $this->forge->addColumn('payroll_runs', [
            'approved_by'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'paid_by'           => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'payment_method'    => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'payment_reference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
        ]);

        $components = [
            ['component_code' => 'BASIC', 'component_name' => 'Basic salary', 'component_type' => 'earning', 'calculation_method' => 'fixed', 'taxable' => 1, 'statutory' => 0, 'sort_order' => 10],
            ['component_code' => 'OVERTIME', 'component_name' => 'Overtime', 'component_type' => 'earning', 'calculation_method' => 'fixed', 'taxable' => 1, 'statutory' => 0, 'sort_order' => 20],
            ['component_code' => 'BONUS', 'component_name' => 'Bonus', 'component_type' => 'earning', 'calculation_method' => 'fixed', 'taxable' => 1, 'statutory' => 0, 'sort_order' => 30],
            ['component_code' => 'ALLOWANCE', 'component_name' => 'Allowance', 'component_type' => 'earning', 'calculation_method' => 'fixed', 'taxable' => 1, 'statutory' => 0, 'sort_order' => 40],
            ['component_code' => 'OTHER_DEDUCTION', 'component_name' => 'Other deduction', 'component_type' => 'deduction', 'calculation_method' => 'fixed', 'taxable' => 0, 'statutory' => 0, 'sort_order' => 50],
            ['component_code' => 'NSSF_EE', 'component_name' => 'NSSF employee contribution', 'component_type' => 'deduction', 'calculation_method' => 'statutory', 'taxable' => 0, 'statutory' => 1, 'sort_order' => 60],
            ['component_code' => 'PAYE', 'component_name' => 'PAYE', 'component_type' => 'deduction', 'calculation_method' => 'statutory', 'taxable' => 0, 'statutory' => 1, 'sort_order' => 70],
            ['component_code' => 'NSSF_ER', 'component_name' => 'NSSF employer contribution', 'component_type' => 'employer_contribution', 'calculation_method' => 'statutory', 'taxable' => 0, 'statutory' => 1, 'sort_order' => 80],
            ['component_code' => 'SDL', 'component_name' => 'Skills Development Levy', 'component_type' => 'employer_contribution', 'calculation_method' => 'statutory', 'taxable' => 0, 'statutory' => 1, 'sort_order' => 90],
            ['component_code' => 'WCF', 'component_name' => 'Workers Compensation Fund', 'component_type' => 'employer_contribution', 'calculation_method' => 'statutory', 'taxable' => 0, 'statutory' => 1, 'sort_order' => 100],
        ];

        foreach ($components as $component) {
            $this->db->table('payroll_components')->ignore(true)->insert($component);
        }

        $this->db->table('payroll_settings')->ignore(true)->insert([
            'payroll_setting_id' => 1,
            'country_code'       => 'TZ',
            'jurisdiction'       => 'MAINLAND',
            'employer_sector'    => 'PRIVATE',
            'employee_count'     => 1,
            'nssf_employee_rate' => 10,
            'nssf_employer_rate' => 10,
            'sdl_rate'           => 3.5,
            'sdl_employee_limit' => 10,
            'wcf_rate'           => 0,
        ]);

        $taxBands = [
            ['income_from' => 0, 'income_to' => 270000, 'base_tax' => 0, 'rate' => 0],
            ['income_from' => 270000, 'income_to' => 520000, 'base_tax' => 0, 'rate' => 8],
            ['income_from' => 520000, 'income_to' => 760000, 'base_tax' => 20000, 'rate' => 20],
            ['income_from' => 760000, 'income_to' => 1000000, 'base_tax' => 68000, 'rate' => 25],
            ['income_from' => 1000000, 'income_to' => null, 'base_tax' => 128000, 'rate' => 30],
        ];

        foreach ($taxBands as $taxBand) {
            $this->db->table('payroll_tax_bands')->insert($taxBand + [
                'jurisdiction'   => 'MAINLAND',
                'tax_status'     => 'resident',
                'effective_from' => '2021-07-01',
            ]);
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('payroll_tax_bands', true);
        $this->forge->dropTable('payroll_settings', true);
        $this->forge->dropTable('payroll_employee_profiles', true);
        $this->forge->dropTable('payroll_item_lines', true);
        $this->forge->dropTable('employee_payroll_components', true);
        $this->forge->dropTable('payroll_components', true);
        $this->forge->dropColumn('payroll_items', ['gross_pay', 'taxable_pay', 'paye', 'employee_contributions', 'employer_contributions', 'employer_cost']);
        $this->forge->dropColumn('payroll_runs', ['approved_by', 'paid_by', 'payment_method', 'payment_reference']);
    }
}
