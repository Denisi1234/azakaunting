<?php

namespace Tests\Controllers;

use App\Models\Payroll_run;
use App\Models\Salary_structure;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\OSPOS;
use Tests\Support\DatabaseBootstrapTrait;

class PayrollControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use DatabaseBootstrapTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $seedOnce    = true;
    protected $refresh     = false;
    protected $namespace   = null;

    protected Payroll_run $payrollRun;
    protected Salary_structure $salaryStructure;

    protected function setUp(): void
    {
        $this->resetDatabaseOnce();

        parent::setUp();

        config(OSPOS::class)->update_settings();

        $this->payrollRun      = model(Payroll_run::class);
        $this->salaryStructure = model(Salary_structure::class);
    }

    public function testGetIndex_RequiresAuthentication(): void
    {
        $result = $this->get('/payroll');
        $result->assertRedirectTo('login');
    }

    public function testGetIndex_RendersForAdmin(): void
    {
        $result = $this->withSession(['person_id' => 1, 'menu_group' => 'office'])->get('/payroll');
        $result->assertOK();
        $result->assertSee(lang('Payroll.payroll'));
    }

    public function testPostSave_CreatesDraftRunWithEmployees(): void
    {
        $result = $this->withSession(['person_id' => 1, 'menu_group' => 'office'])
            ->post('/payroll/save', [
                'period_start' => '2026-10-01',
                'period_end'   => '2026-10-31',
                'notes'        => 'October 2026 Payroll',
            ]);

        $result->assertRedirect();
        $this->seeInDatabase('payroll_runs', [
            'period_start' => '2026-10-01',
            'period_end'   => '2026-10-31',
            'status'       => 'draft',
        ]);
    }
}
