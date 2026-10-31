<?php

namespace Tests\Controllers;

use App\Models\Timeclock;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseBootstrapTrait;
use Tests\Support\EmployeeFixtureTrait;

/**
 * @internal
 */
final class TimeclocksControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use DatabaseBootstrapTrait;
    use FeatureTestTrait;
    use EmployeeFixtureTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace;

    protected function setUp(): void
    {
        $this->resetDatabaseOnce();

        parent::setUp();
        $this->db->resetTransStatus();
        $this->db->table('timeclocks')->emptyTable();
    }

    protected function loginAsAdmin(): void
    {
        $this->withSession(['person_id' => 1, 'menu_group' => 'office']);
    }

    protected function createRecordingEmployee(): int
    {
        return $this->createEmployee(
            first_name: 'Clock',
            last_name: 'Worker',
            email: 'clock.' . uniqid() . '@test.com',
            username: 'clock_' . uniqid(),
            grants: [
                ['permission_id' => 'timeclocks', 'menu_group' => 'office'],
            ],
        );
    }

    public function testPostClockIn_CreatesOpenEntry(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/timeclocks/clockIn');

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertTrue($result['success']);

        $entry = $this->db->table('timeclocks')
            ->where('person_id', 1)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        $this->assertNotNull($entry);
        $this->assertNull($entry->clock_out);
    }

    public function testPostClockIn_RejectsWhenAlreadyClockedIn(): void
    {
        $this->loginAsAdmin();

        model(Timeclock::class)->clockIn(1, date('Y-m-d H:i:s'));

        $response = $this->post('/timeclocks/clockIn');

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Timeclocks.already_clocked_in'), $result['message']);
    }

    public function testPostClockOut_ClosesOpenEntry(): void
    {
        $this->loginAsAdmin();

        model(Timeclock::class)->clockIn(1, date('Y-m-d H:i:s', strtotime('-2 hours')));

        $response = $this->post('/timeclocks/clockOut');

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertTrue($result['success']);

        $entry = $this->db->table('timeclocks')
            ->where('person_id', 1)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        $this->assertNotNull($entry->clock_out);
    }

    public function testPostClockOut_RejectsWhenNotClockedIn(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/timeclocks/clockOut');

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Timeclocks.not_clocked_in'), $result['message']);
    }

    public function testPostSave_RejectsClockOutBeforeClockIn(): void
    {
        $employeeId = $this->createRecordingEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/timeclocks/save', [
            'person_id' => $employeeId,
            'clock_in'  => '2024-01-15T10:00',
            'clock_out' => '2024-01-15T09:00',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Timeclocks.clock_out_before_clock_in'), $result['message']);
    }

    public function testPostSave_RejectsInvalidEmployee(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/timeclocks/save', [
            'person_id' => 999999,
            'clock_in'  => '2024-01-15T10:00',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Timeclocks.employee_invalid'), $result['message']);
    }

    public function testPostSave_RejectsSecondOpenEntryForEmployee(): void
    {
        $employeeId = $this->createRecordingEmployee();
        $this->loginAsAdmin();

        $firstResponse = $this->post('/timeclocks/save', [
            'person_id' => $employeeId,
            'clock_in'  => date('Y-m-d\TH:i'),
        ]);

        $this->assertTrue(json_decode($firstResponse->getJSON(), true)['success']);

        $secondResponse = $this->post('/timeclocks/save', [
            'person_id' => $employeeId,
            'clock_in'  => date('Y-m-d\TH:i'),
        ]);

        $secondResponse->assertStatus(200);
        $result = json_decode($secondResponse->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Timeclocks.already_clocked_in'), $result['message']);
    }

    public function testPostDelete_RejectsEmptySelection(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/timeclocks/delete', [
            'ids' => [],
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Timeclocks.none_selected'), $result['message']);
    }
}
