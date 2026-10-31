<?php

namespace App\Models;

use CodeIgniter\Database\ResultInterface;
use CodeIgniter\Model;
use Config\OSPOS;
use stdClass;

/**
 * Project class
 */
class Project extends Model
{
    protected $table            = 'projects';
    protected $primaryKey       = 'project_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'project_number',
        'project_name',
        'customer_id',
        'employee_id',
        'description',
        'status',
        'start_date',
        'end_date',
        'budget_amount',
        'deleted',
    ];

    /**
     * Determines if a given Project_id is a Project
     */
    public function exists(int $project_id): bool
    {
        $builder = $this->db->table('projects');
        $builder->where('project_id', $project_id);

        return $builder->get()->getNumRows() === 1;    // TODO: ===
    }

    /**
     * Gets rows
     */
    public function get_found_rows(string $search, array $filters): int
    {
        return $this->search($search, $filters, 0, 0, 'project_id', 'asc', true);
    }

    /**
     * Searches projects
     */
    public function search(string $search, array $filters, ?int $rows = 0, ?int $limit_from = 0, ?string $sort = 'project_id', ?string $order = 'asc', ?bool $count_only = false): false|ResultInterface|string
    {
        if ($rows === null) {
            $rows = 0;
        }
        if ($limit_from === null) {
            $limit_from = 0;
        }
        if ($sort === null) {
            $sort = 'project_id';
        }
        if ($order === null) {
            $order = 'asc';
        }
        if ($count_only === null) {
            $count_only = false;
        }

        $config  = config(OSPOS::class)->settings;
        $builder = $this->db->table('projects AS projects');

        if ($count_only) {
            $builder->select('COUNT(DISTINCT projects.project_id) as count');
        } else {
            $builder->select('
                projects.project_id,
                projects.project_number,
                projects.project_name,
                projects.customer_id,
                projects.employee_id,
                projects.description,
                projects.status,
                projects.start_date,
                projects.end_date,
                projects.budget_amount,
                projects.deleted,
                MAX(people.first_name) AS customer_first_name,
                MAX(people.last_name) AS customer_last_name,
                MAX(people.company_name) AS customer_company_name,
                MAX(employees.first_name) AS manager_first_name,
                MAX(employees.last_name) AS manager_last_name,
                (SELECT COALESCE(SUM(s.total), 0) FROM " . $this->db->prefixTable('sales') . " s WHERE s.project_id = projects.project_id) AS total_sales,
                (SELECT COALESCE(SUM(e.amount), 0) FROM " . $this->db->prefixTable('expenses') . " e WHERE e.project_id = projects.project_id) AS total_expenses
            ');
        }

        $builder->join('people AS customers', 'customers.person_id = projects.customer_id', 'LEFT');
        $builder->join('people AS people', 'people.person_id = projects.customer_id', 'LEFT');
        $builder->join('people AS employees', 'employees.person_id = projects.employee_id', 'LEFT');

        $builder->groupStart();
        $builder->like('projects.project_number', $search);
        $builder->orLike('projects.project_name', $search);
        $builder->orLike('projects.description', $search);
        $builder->orLike('projects.status', $search);
        $builder->orLike('CONCAT(people.first_name, " ", people.last_name)', $search);
        $builder->orLike('CONCAT(employees.first_name, " ", employees.last_name)', $search);
        $builder->groupEnd();

        $builder->where('projects.deleted', $filters['is_deleted']);

        if (! empty($filters['status'])) {
            $builder->where('projects.status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $builder->where('projects.customer_id', $filters['customer_id']);
        }

        if (! empty($filters['employee_id'])) {
            $builder->where('projects.employee_id', $filters['employee_id']);
        }

        if (empty($config['date_or_time_format'])) {
            $builder->where('DATE_FORMAT(projects.start_date, "%Y-%m-%d") BETWEEN ' . $this->db->escape($filters['start_date']) . ' AND ' . $this->db->escape($filters['end_date']));
        } else {
            $builder->where('projects.start_date BETWEEN ' . $this->db->escape(rawurldecode($filters['start_date'])) . ' AND ' . $this->db->escape(rawurldecode($filters['end_date'])));
        }

        if ($count_only) {
            return $builder->get()->getRow()->count;
        }

        $builder->groupBy('projects.project_id');
        $builder->orderBy($sort, $order);

        if ($rows > 0) {
            $builder->limit($rows, $limit_from);
        }

        return $builder->get();
    }

    /**
     * Gets information about a particular project
     */
    public function get_info(int $project_id): object
    {
        $builder = $this->db->table('projects AS projects');
        $builder->select('
            projects.project_id,
            projects.project_number,
            projects.project_name,
            projects.customer_id,
            projects.employee_id,
            projects.description,
            projects.status,
            projects.start_date,
            projects.end_date,
            projects.budget_amount,
            projects.deleted,
            customers.first_name AS customer_first_name,
            customers.last_name AS customer_last_name,
            customers.company_name AS customer_company_name,
            employees.first_name AS manager_first_name,
            employees.last_name AS manager_last_name,
            (SELECT COALESCE(SUM(s.total), 0) FROM " . $this->db->prefixTable('sales') . " s WHERE s.project_id = projects.project_id) AS total_sales,
            (SELECT COALESCE(SUM(e.amount), 0) FROM " . $this->db->prefixTable('expenses') . " e WHERE e.project_id = projects.project_id) AS total_expenses
        ');
        $builder->join('people AS customers', 'customers.person_id = projects.customer_id', 'LEFT');
        $builder->join('people AS employees', 'employees.person_id = projects.employee_id', 'LEFT');
        $builder->where('project_id', $project_id);

        $query = $builder->get();

        if ($query->getNumRows() === 1) {    // TODO: ===
            return $query->getRow();
        }

        $empty_obj = new stdClass();

        foreach ($this->db->getFieldData('projects') as $field) {
            $field_name = $field->name;
            if (in_array($field->type, ['int', 'tinyint', 'decimal'], true)) {
                $empty_obj->{$field_name} = ($field->primary_key === 1) ? NEW_ENTRY : 0;
            } else {
                $empty_obj->{$field_name} = null;
            }
        }
        $empty_obj->customer_first_name   = null;
        $empty_obj->customer_last_name    = null;
        $empty_obj->customer_company_name = null;
        $empty_obj->manager_first_name    = null;
        $empty_obj->manager_last_name     = null;
        $empty_obj->total_sales           = 0;
        $empty_obj->total_expenses        = 0;

        return $empty_obj;
    }

    /**
     * Inserts or updates a project
     */
    public function save_value(array &$project_data, int $project_id = NEW_ENTRY): bool
    {
        $builder = $this->db->table('projects');

        if ($project_id === NEW_ENTRY || ! $this->exists($project_id)) {
            if ($builder->insert($project_data)) {
                $project_data['project_id'] = $this->db->insertID();

                return true;
            }

            return false;
        }

        $builder->where('project_id', $project_id);

        return $builder->update($project_data);
    }

    /**
     * Deletes a list of projects (soft delete)
     */
    public function delete_list(array $project_ids): bool
    {
        $builder = $this->db->table('projects');

        $this->db->transStart();
        $builder->whereIn('project_id', $project_ids);
        $success = $builder->update(['deleted' => 1]);
        $this->db->transComplete();

        $success &= $this->db->transStatus();

        return $success;
    }

    /**
     * Gets sales linked to a project
     */
    public function get_linked_sales(int $project_id): ResultInterface
    {
        $builder = $this->db->table('sales');
        $builder->select('sale_id, invoice_number, sale_time, total, payment_type');
        $builder->where('project_id', $project_id);
        $builder->orderBy('sale_time', 'desc');

        return $builder->get();
    }

    /**
     * Gets expenses linked to a project
     */
    public function get_linked_expenses(int $project_id): ResultInterface
    {
        $builder = $this->db->table('expenses');
        $builder->select('expense_id, date, amount, tax_amount, description, payment_type');
        $builder->where('project_id', $project_id);
        $builder->orderBy('date', 'desc');

        return $builder->get();
    }

    /**
     * Links a sale to a project
     */
    public function link_sale(int $project_id, int $sale_id): bool
    {
        $builder = $this->db->table('sales');
        $builder->where('sale_id', $sale_id);

        return $builder->update(['project_id' => $project_id]);
    }

    /**
     * Unlinks a sale from a project
     */
    public function unlink_sale(int $sale_id): bool
    {
        $builder = $this->db->table('sales');
        $builder->where('sale_id', $sale_id);

        return $builder->update(['project_id' => null]);
    }

    /**
     * Links an expense to a project
     */
    public function link_expense(int $project_id, int $expense_id): bool
    {
        $builder = $this->db->table('expenses');
        $builder->where('expense_id', $expense_id);

        return $builder->update(['project_id' => $project_id]);
    }

    /**
     * Unlinks an expense from a project
     */
    public function unlink_expense(int $expense_id): bool
    {
        $builder = $this->db->table('expenses');
        $builder->where('expense_id', $expense_id);

        return $builder->update(['project_id' => null]);
    }
}
