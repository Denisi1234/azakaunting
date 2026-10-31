<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Migration_add_delivery_note_number extends Migration
{
    /**
     * Perform a migration step.
     */
    public function up(): void
    {
        $this->forge->addColumn('sales', [
            'delivery_note_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'work_order_number',
            ],
        ]);
    }

    /**
     * Revert a migration step.
     */
    public function down(): void
    {
        $this->forge->dropColumn('sales', 'delivery_note_number');
    }
}
