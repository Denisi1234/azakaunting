<?php

namespace App\Models;

use CodeIgniter\Model;

class Audit_log_model extends Model
{
    protected $table         = 'audit_log';
    protected $primaryKey    = 'audit_id';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'employee_id', 'action', 'entity_type', 'entity_id',
        'ip_address', 'before_data', 'after_data', 'created_at',
    ];
}
