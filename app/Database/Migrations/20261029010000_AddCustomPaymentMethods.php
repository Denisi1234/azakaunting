<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomPaymentMethods extends Migration
{
    public function up(): void
    {
        $this->db->table('app_config')->ignore(true)->insert([
            'key'   => 'custom_payment_methods',
            'value' => '[]'
        ]);
    }

    public function down(): void
    {
        $this->db->table('app_config')->delete(['key' => 'custom_payment_methods']);
    }
}
