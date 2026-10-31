<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomerCreditLimits extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('customers', [
            'credit_limit' => [
                'type' => 'DECIMAL',
                'constraint' => '15,4',
                'null' => true,
                'default' => null,
                'after' => 'discount_type',
            ],
            'credit_terms_days' => [
                'type' => 'SMALLINT',
                'constraint' => 5,
                'unsigned' => true,
                'default' => 30,
                'after' => 'credit_limit',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('customers', 'credit_terms_days');
        $this->forge->dropColumn('customers', 'credit_limit');
    }
}
