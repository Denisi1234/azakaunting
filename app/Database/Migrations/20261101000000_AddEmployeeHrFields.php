<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmployeeHrFields extends Migration
{
    /**
     * Perform a migration step.
     */
    public function up(): void
    {
        $table = $this->db->prefixTable('employees');

        $this->forge->addColumn('employees', [
            'employee_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'default'    => null,
                'after'      => 'language_code',
            ],
            'job_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'employee_number',
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'job_title',
            ],
            'hire_date' => [
                'type'    => 'DATE',
                'null'    => true,
                'default' => null,
                'after'   => 'department',
            ],
            'employment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'default'    => null,
                'after'      => 'hire_date',
            ],
            'hourly_rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,4',
                'null'       => true,
                'default'    => null,
                'after'      => 'employment_type',
            ],
            'emergency_contact_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'hourly_rate',
            ],
            'emergency_contact_phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'emergency_contact_name',
            ],
            'emergency_contact_relation' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'emergency_contact_phone',
            ],
        ]);

        $this->db->query("ALTER TABLE {$table} ADD UNIQUE KEY `employees_employee_number_unique` (`employee_number`)");
    }

    /**
     * Revert a migration step.
     */
    public function down(): void
    {
        $table = $this->db->prefixTable('employees');

        $this->db->query("ALTER TABLE {$table} DROP INDEX `employees_employee_number_unique`");

        $this->forge->dropColumn('employees', [
            'employee_number',
            'job_title',
            'department',
            'hire_date',
            'employment_type',
            'hourly_rate',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relation',
        ]);
    }
}
