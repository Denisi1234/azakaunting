<?php

namespace Tests\Controllers;

use App\Models\Project;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\OSPOS;
use Tests\Support\DatabaseBootstrapTrait;

class ProjectsControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use DatabaseBootstrapTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $seedOnce    = true;
    protected $refresh     = false;
    protected $namespace   = null;

    protected Project $project;

    protected function setUp(): void
    {
        $this->resetDatabaseOnce();

        parent::setUp();

        config(OSPOS::class)->update_settings();

        $this->project = model(Project::class);
    }

    public function testGetIndex_RequiresAuthentication(): void
    {
        $result = $this->get('/projects');
        $result->assertRedirectTo('login');
    }

    public function testGetIndex_RendersForAdmin(): void
    {
        $result = $this->withSession(['person_id' => 1, 'menu_group' => 'office'])->get('/projects');
        $result->assertOK();
        $result->assertSee(lang('Projects.projects') ?: 'Projects');
    }

    public function testPostSave_CreatesNewProject(): void
    {
        $result = $this->withSession(['person_id' => 1, 'menu_group' => 'office'])
            ->post('/projects/save', [
                'project_number' => 'PRJ-1001',
                'project_name'   => 'POS Hardware Rollout',
                'status'         => 'active',
                'budget_amount'  => 5000,
                'description'    => 'Enterprise POS deployment',
            ]);

        $result->assertRedirect();
        $this->seeInDatabase('projects', [
            'project_number' => 'PRJ-1001',
            'project_name'   => 'POS Hardware Rollout',
        ]);
    }
}
