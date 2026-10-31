<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTimeclockModule extends Migration
{
    /**
     * Perform a migration step.
     */
    public function up(): void
    {
        $this->forge->addField([
            'timeclock_id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'person_id' => [
                'type'       => 'INT',
                'constraint' => 10,
            ],
            'clock_in' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'clock_out' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'note' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'deleted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
        $this->forge->addKey('timeclock_id', true);
        $this->forge->addKey(['person_id', 'clock_in']);
        $this->forge->addForeignKey('person_id', 'employees', 'person_id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('timeclocks', true);

        $modules = $this->db->table('modules');
        if ($modules->where('module_id', 'timeclocks')->countAllResults() === 0) {
            $modules->insert([
                'module_id'     => 'timeclocks',
                'name_lang_key' => 'module_timeclocks',
                'desc_lang_key' => 'module_timeclocks_desc',
                'sort'          => 85,
            ]);
        }

        $permissions = $this->db->table('permissions');

        foreach (['timeclocks', 'timeclocks_manage'] as $permissionId) {
            if ($permissions->where('permission_id', $permissionId)->countAllResults() === 0) {
                $permissions->insert([
                    'permission_id' => $permissionId,
                    'module_id'     => 'timeclocks',
                ]);
            }
        }

        $grants = $this->db->table('grants');

        foreach (['timeclocks', 'timeclocks_manage'] as $permissionId) {
            if ($grants->where('permission_id', $permissionId)->where('person_id', 1)->countAllResults() === 0) {
                $grants->insert([
                    'permission_id' => $permissionId,
                    'person_id'     => 1,
                    'menu_group'    => 'office',
                ]);
            }
        }
    }

    /**
     * Revert a migration step.
     */
    public function down(): void
    {
        $permissionIds = ['timeclocks', 'timeclocks_manage'];

        $this->db->table('grants')->whereIn('permission_id', $permissionIds)->delete();
        $this->db->table('permissions')->whereIn('permission_id', $permissionIds)->delete();
        $this->db->table('modules')->where('module_id', 'timeclocks')->delete();
        $this->forge->dropTable('timeclocks', true);
    }
}
