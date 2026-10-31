<?php

namespace App\Services;

use App\Models\Audit_log_model;
use CodeIgniter\HTTP\IncomingRequest;

class AuditLogger
{
    private Audit_log_model $model;

    public function __construct(?Audit_log_model $model = null)
    {
        $this->model = $model ?? new Audit_log_model();
    }

    public function log(
        string $action,
        ?string $entityType = null,
        $entityId = null,
        $before = null,
        $after = null,
        ?int $employeeId = null
    ): void {
        $request = service('request');
        $ip = $request instanceof IncomingRequest ? $request->getIPAddress() : null;

        $this->model->insert([
            'employee_id' => $employeeId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId === null ? null : (string) $entityId,
            'ip_address'  => $ip,
            'before_data' => $before === null ? null : json_encode($before),
            'after_data'  => $after === null ? null : json_encode($after),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
