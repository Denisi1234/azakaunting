<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuotationDetails extends Migration
{
    private function columnExists(string $table, string $column): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$this->db->database, $table, $column]
        )->getRow();

        return (int) $row->cnt > 0;
    }

    public function up(): void
    {
        $prefix = $this->db->getPrefix();
        $sales  = $prefix . 'sales';

        if (! $this->columnExists($sales, 'quote_expiry_date')) {
            $this->forge->addColumn('sales', [
                'quote_expiry_date' => ['type' => 'DATE', 'null' => true, 'default' => null, 'after' => 'quote_number'],
            ]);
        }

        if (! $this->columnExists($sales, 'quote_reference')) {
            $this->forge->addColumn('sales', [
                'quote_reference' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null, 'after' => 'quote_expiry_date'],
            ]);
        }

        if (! $this->columnExists($sales, 'salesperson_id')) {
            $this->forge->addColumn('sales', [
                'salesperson_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true, 'default' => null, 'after' => 'employee_id'],
            ]);
        }

        if (! $this->columnExists($sales, 'quote_payment_details')) {
            $this->forge->addColumn('sales', [
                'quote_payment_details' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            ]);
        }

        if (! $this->columnExists($sales, 'quote_terms_and_conditions')) {
            $this->forge->addColumn('sales', [
                'quote_terms_and_conditions' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            ]);
        }

        if (! $this->columnExists($sales, 'currency_id')) {
            $this->forge->addColumn('sales', [
                'currency_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'default' => null],
            ]);
        }
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        $sales  = $prefix . 'sales';

        foreach (['quote_expiry_date', 'quote_reference', 'salesperson_id', 'quote_payment_details', 'quote_terms_and_conditions'] as $column) {
            if ($this->columnExists($sales, $column)) {
                $this->forge->dropColumn('sales', $column);
            }
        }
    }
}
