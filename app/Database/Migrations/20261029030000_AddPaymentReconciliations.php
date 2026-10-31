<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPaymentReconciliations extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'reconciliation_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'business_date' => ['type' => 'DATE'],
            'location_id' => ['type' => 'INT', 'constraint' => 10],
            'payment_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'expected_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'collected_amount' => ['type' => 'DECIMAL', 'constraint' => '15,2'],
            'note' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
            'reconciled_by' => ['type' => 'INT', 'constraint' => 10],
            'reconciled_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addKey('reconciliation_id', true);
        $this->forge->addUniqueKey(['business_date', 'location_id', 'payment_type'], 'payment_reconciliation_day_location_method');
        $this->forge->addKey(['location_id', 'business_date']);
        $this->forge->addForeignKey('location_id', 'stock_locations', 'location_id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('reconciled_by', 'employees', 'person_id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('payment_reconciliations', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('payment_reconciliations', true);
    }
}
