<?php

namespace Tests\Controllers;

use App\Models\Employee;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseBootstrapTrait;
use Tests\Support\EmployeeFixtureTrait;

/**
 * @internal
 */
final class EmployeesControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use DatabaseBootstrapTrait;
    use FeatureTestTrait;
    use EmployeeFixtureTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $refresh     = false;
    protected $namespace;
    protected $priorDisallowGrantChange;

    protected function setUp(): void
    {
        // Reset the test database to a known clean schema on the first test in
        // this class so stale employees from prior runs (whose usernames are
        // unique-key bound and whose grant sets leak into these assertions)
        // cannot contaminate the assertions below.
        $this->resetDatabaseOnce();

        parent::setUp();
        // Reset any stale transaction state left on the shared in-process DB
        // connection by a previous test (e.g. a failed transaction rollback in
        // strict mode sets transStatus=false and poisons save_employee here).
        $this->db->resetTransStatus();
        $this->priorDisallowGrantChange = getenv('DISALLOW_GRANT_CHANGE');
        putenv('DISALLOW_GRANT_CHANGE=false');
    }

    protected function tearDown(): void
    {
        if ($this->priorDisallowGrantChange === false) {
            putenv('DISALLOW_GRANT_CHANGE');
        } else {
            putenv('DISALLOW_GRANT_CHANGE=' . $this->priorDisallowGrantChange);
        }
        parent::tearDown();
    }

    protected function createNonAdminEmployee(): int
    {
        return $this->createEmployee(
            first_name: 'NonAdmin',
            last_name: 'User',
            email: 'nonadmin' . uniqid() . '@test.com',
            username: 'nonadmin_' . uniqid(),
            phone_number: '555-1234',
            grants: [
                ['permission_id' => 'employees', 'menu_group' => 'home'],
                ['permission_id' => 'customers', 'menu_group' => 'home'],
                ['permission_id' => 'sales', 'menu_group' => 'home'],
            ],
        );
    }

    protected function loginAsAdmin(): void
    {
        $this->withSession(['person_id' => 1, 'menu_group' => 'office']);
    }

    protected function loginAsNonAdmin(int $personId): void
    {
        $this->withSession(['person_id' => $personId, 'menu_group' => 'home']);
    }

    public function testNonAdminCannotViewAdminAccount(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsNonAdmin($nonAdminId);

        $response = $this->get('/employees/view/1');

        $response->assertRedirect();
        $this->assertStringContainsString('no_access', $response->getRedirectUrl());
    }

    public function testNonAdminCannotModifyAdminAccount(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsNonAdmin($nonAdminId);

        $response = $this->post('/employees/save/1', [
            'first_name' => 'Hacked',
            'last_name'  => 'Admin',
            'email'      => 'hacked@evil.com',
            'username'   => 'admin',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('admin', strtolower($result['message']));
    }

    public function testNonAdminCannotDeleteAdminAccount(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsNonAdmin($nonAdminId);

        $response = $this->post('/employees/delete', [
            'ids' => ['1'],
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('admin', strtolower($result['message']));
    }

    public function testNonAdminCannotGrantPermissionsTheyDontHave(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsNonAdmin($nonAdminId);

        $targetEmployeeId = $this->createEmployee(email: 'test@test.com', username: 'testuser');

        $response = $this->post('/employees/save/' . $targetEmployeeId, [
            'first_name'      => 'Test',
            'last_name'       => 'Employee',
            'email'           => 'test@test.com',
            'username'        => 'testuser',
            'language'        => 'en:english',
            'grant_config'    => 'config',
            'grant_giftcards' => 'giftcards',
        ]);

        $employeeModel     = model(Employee::class);
        $hasConfigGrant    = $employeeModel->has_grant('config', $targetEmployeeId);
        $hasGiftcardsGrant = $employeeModel->has_grant('giftcards', $targetEmployeeId);

        $this->assertFalse($hasConfigGrant);
        $this->assertFalse($hasGiftcardsGrant);
    }

    public function testAdminCanModifyAnyAccount(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $nonAdminId, [
            'first_name' => 'Modified',
            'last_name'  => 'User',
            'email'      => 'modified@test.com',
            'username'   => 'modified',
            'language'   => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);
    }

    public function testAdminCanDeleteAnyAccount(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/delete', [
            'ids' => [(string) $nonAdminId],
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);
    }

    public function testUserCanModifyOwnAccount(): void
    {
        $nonAdminId = $this->createNonAdminEmployee();
        $this->loginAsNonAdmin($nonAdminId);

        $response = $this->post('/employees/save/' . $nonAdminId, [
            'first_name' => 'Modified',
            'last_name'  => 'OwnAccount',
            'email'      => 'own@test.com',
            'username'   => 'owned',
            'language'   => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);
    }

    public function testPermissionDelegationRule(): void
    {
        $permissionsRequested = ['customers', 'employees', 'sales', 'config'];
        $userPermissions      = ['customers', 'sales'];
        $isAdmin              = false;

        $granted = [];

        foreach ($permissionsRequested as $perm) {
            if ($isAdmin || in_array($perm, $userPermissions, true)) {
                $granted[] = $perm;
            }
        }

        $this->assertSame(['customers', 'sales'], $granted);
    }

    public function testAdminCanGrantAnyPermission(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        putenv('DISALLOW_GRANT_CHANGE=false');

        $permissionsRequested = ['customers', 'employees', 'sales', 'config'];

        $postData = [
            'first_name' => 'NonAdmin',
            'last_name'  => 'User',
            'email'      => 'nonadmin@test.com',
            'username'   => 'grantany' . uniqid(),
            'language'   => 'en:english',
        ];

        foreach ($permissionsRequested as $perm) {
            $postData['grant_' . $perm] = $perm;
        }

        $response = $this->post('/employees/save/' . $employeeId, $postData);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);

        $employeeModel = model(Employee::class);

        foreach ($permissionsRequested as $perm) {
            $this->assertTrue($employeeModel->has_grant($perm, $employeeId));
        }
    }

    public function testGrantChangeRequestFailsWhenGrantChangeDisallowed(): void
    {
        // Target holds customers+sales but NOT employees, so requesting the
        // employees grant is a genuine change that DISALLOW_GRANT_CHANGE=true
        // must reject (leaving the grant set untouched).
        $unique     = uniqid();
        $employeeId = $this->createEmployee(
            email: "disallow{$unique}@test.com",
            username: "disallow{$unique}",
            grants: [
                ['permission_id' => 'customers', 'menu_group' => 'home'],
                ['permission_id' => 'sales', 'menu_group' => 'home'],
            ],
        );
        $this->loginAsAdmin();

        putenv('DISALLOW_GRANT_CHANGE=true');

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name'      => 'NonAdmin',
            'last_name'       => 'User',
            'email'           => "disallow{$unique}@test.com",
            'username'        => "disallow{$unique}",
            'language'        => 'en:english',
            'grant_employees' => 'employees',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertFalse($result['success']);

        $employeeModel = model(Employee::class);
        $this->assertTrue($employeeModel->has_grant('customers', $employeeId));
        $this->assertTrue($employeeModel->has_grant('sales', $employeeId));
        $this->assertFalse($employeeModel->has_grant('employees', $employeeId));
    }

    public function testNewEmployeeCreationWithGrantsFailsWhenGrantChangeDisallowed(): void
    {
        $this->loginAsAdmin();

        putenv('DISALLOW_GRANT_CHANGE=true');

        $response = $this->post('/employees/save', [
            'first_name'      => 'Brand',
            'last_name'       => 'New',
            'email'           => 'brandnew@test.com',
            'username'        => 'brandnew',
            'password'        => 'password123',
            'grant_customers' => 'customers',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertFalse($result['success']);

        $createdEmployee = $this->db->table('employees')->where('username', 'brandnew')->get()->getRow();
        $this->assertNull($createdEmployee);
    }

    public function testGrantChangeRequestSucceedsWhenGrantChangeAllowed(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        putenv('DISALLOW_GRANT_CHANGE=false');

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name'      => 'NonAdmin',
            'last_name'       => 'User',
            'email'           => 'nonadmin@test.com',
            'username'        => 'grantsucc',
            'language'        => 'en:english',
            'grant_employees' => 'employees',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);

        $employeeModel = model(Employee::class);
        $this->assertTrue($employeeModel->has_grant('employees', $employeeId));
        $this->assertFalse($employeeModel->has_grant('customers', $employeeId));
        $this->assertFalse($employeeModel->has_grant('sales', $employeeId));
    }

    public function testNewEmployeeCreationWithGrantsSucceedsWhenGrantChangeAllowed(): void
    {
        $this->loginAsAdmin();

        putenv('DISALLOW_GRANT_CHANGE=false');

        $response = $this->post('/employees/save', [
            'first_name'      => 'Brand',
            'last_name'       => 'New2',
            'email'           => 'brandnew2@test.com',
            'phone_number'    => '555-1234',
            'address_1'       => '',
            'address_2'       => '',
            'city'            => '',
            'state'           => '',
            'zip'             => '',
            'country'         => '',
            'comments'        => '',
            'username'        => 'brandnew2',
            'password'        => 'password123',
            'language'        => 'en:english',
            'grant_customers' => 'customers',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);

        $createdEmployee = $this->db->table('employees')->where('username', 'brandnew2')->get()->getRow();
        $this->assertNotNull($createdEmployee);

        $employeeModel = model(Employee::class);
        $this->assertTrue($employeeModel->has_grant('customers', (int) $createdEmployee->person_id));
    }

    public function testPostSave_RejectsShortUsername(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name' => 'Short',
            'last_name'  => 'Username',
            'email'      => 'short@test.com',
            'username'   => 'abc',
            'language'   => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.username_minlength'), $result['message']);
    }

    public function testPostSave_RejectsShortPassword(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name'      => 'Short',
            'last_name'       => 'Password',
            'email'           => 'shortpass@test.com',
            'username'        => 'shortpass',
            'password'        => 'short',
            'repeat_password' => 'short',
            'language'        => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.password_minlength'), $result['message']);
    }

    public function testPostSave_RejectsNewEmployeeWithoutPassword(): void
    {
        $this->loginAsAdmin();
        $unique = uniqid();

        $response = $this->post('/employees/save', [
            'first_name' => 'No',
            'last_name'  => 'Password',
            'email'      => "nopassword{$unique}@test.com",
            'username'   => "nopassword{$unique}",
            'language'   => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.password_required'), $result['message']);
        $this->assertNull($this->db->table('employees')->where('username', "nopassword{$unique}")->get()->getRow());
    }

    public function testPostSave_RejectsDuplicateUsername(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name' => 'Duplicate',
            'last_name'  => 'Username',
            'email'      => 'duplicate@test.com',
            'username'   => 'admin',
            'language'   => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.username_duplicate'), $result['message']);
    }

    public function testPostSave_RejectsInvalidEmail(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name' => 'Invalid',
            'last_name'  => 'Email',
            'email'      => 'not-an-email',
            'username'   => 'invalidemail',
            'language'   => 'en:english',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Common.email_invalid_format'), $result['message']);
    }

    public function testPostSave_PreservesUnmanageableGrantsForNonAdmin(): void
    {
        $editorId = $this->createNonAdminEmployee();

        $unique   = uniqid();
        $targetId = $this->createEmployee(
            first_name: 'Target',
            last_name: 'Employee',
            email: "target{$unique}@test.com",
            username: "target{$unique}",
            grants: [
                ['permission_id' => 'customers', 'menu_group' => 'home'],
                ['permission_id' => 'config', 'menu_group' => 'office'],
            ],
        );
        $this->loginAsNonAdmin($editorId);

        $response = $this->post('/employees/save/' . $targetId, [
            'first_name'      => 'Target',
            'last_name'       => 'Employee',
            'email'           => "target{$unique}@test.com",
            'username'        => "target{$unique}",
            'language'        => 'en:english',
            'grant_customers' => 'customers',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);

        $employeeModel = model(Employee::class);
        $this->assertTrue($employeeModel->has_grant('customers', $targetId));
        $this->assertTrue($employeeModel->has_grant('config', $targetId));
        $this->assertFalse($employeeModel->has_grant('employees', $targetId));
    }

    public function testPostDelete_AcceptsSingleIdString(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/delete', [
            'ids' => (string) $employeeId,
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertTrue($result['success']);

        $deleted = $this->db->table('employees')->where('person_id', $employeeId)->get()->getRow();
        $this->assertSame(1, (int) $deleted->deleted);
    }

    public function testPostDelete_RejectsEmptySelection(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/employees/delete', [
            'ids' => [],
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.none_selected'), $result['message']);
    }

    public function testPostDelete_RejectsDeletingOwnAccount(): void
    {
        $this->loginAsAdmin();

        $response = $this->post('/employees/delete', [
            'ids' => ['1']
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.cannot_delete_self'), $result['message']);
    }

    public function testPostSave_AcceptsHrFields(): void
    {
        $this->loginAsAdmin();

        $unique   = uniqid();
        $response = $this->post('/employees/save', [
            'first_name'                 => 'Hr',
            'last_name'                  => 'Employee',
            'email'                      => "hr{$unique}@test.com",
            'username'                   => "hr{$unique}",
            'password'                   => 'password123',
            'repeat_password'            => 'password123',
            'language'                   => 'en:english',
            'employee_number'            => "EMP-{$unique}",
            'job_title'                  => 'Cashier',
            'department'                 => 'Front Desk',
            'hire_date'                  => '2024-01-15',
            'employment_type'            => 'full_time',
            'hourly_rate'                => '12.50',
            'emergency_contact_name'     => 'Jane Doe',
            'emergency_contact_phone'    => '555-0100',
            'emergency_contact_relation' => 'Spouse',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);
        $this->assertTrue($result['success']);

        $employee = $this->db->table('employees')->where('username', "hr{$unique}")->get()->getRow();

        $this->assertSame("EMP-{$unique}", $employee->employee_number);
        $this->assertSame('Cashier', $employee->job_title);
        $this->assertSame('Front Desk', $employee->department);
        $this->assertSame('2024-01-15', $employee->hire_date);
        $this->assertSame('full_time', $employee->employment_type);
        $this->assertSame('12.5000', (string) $employee->hourly_rate);
        $this->assertSame('Jane Doe', $employee->emergency_contact_name);
        $this->assertSame('555-0100', $employee->emergency_contact_phone);
        $this->assertSame('Spouse', $employee->emergency_contact_relation);
    }

    public function testPostSave_RejectsInvalidHireDate(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name' => 'Invalid',
            'last_name'  => 'HireDate',
            'email'      => 'invalidhiredate@test.com',
            'username'   => 'invalidhire',
            'language'   => 'en:english',
            'hire_date'  => '2024-13-40',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.hire_date_invalid'), $result['message']);
    }

    public function testPostSave_RejectsNegativeHourlyRate(): void
    {
        $employeeId = $this->createNonAdminEmployee();
        $this->loginAsAdmin();

        $response = $this->post('/employees/save/' . $employeeId, [
            'first_name'  => 'Negative',
            'last_name'   => 'Rate',
            'email'       => 'negativerate@test.com',
            'username'    => 'negativerate',
            'language'    => 'en:english',
            'hourly_rate' => '-5',
        ]);

        $response->assertStatus(200);
        $result = json_decode($response->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.hourly_rate_invalid'), $result['message']);
    }

    public function testPostSave_RejectsDuplicateEmployeeNumber(): void
    {
        $this->loginAsAdmin();

        $unique         = uniqid();
        $employeeNumber = "EMP-DUP-{$unique}";

        $firstResponse = $this->post('/employees/save', [
            'first_name'      => 'First',
            'last_name'       => 'Number',
            'email'           => "first{$unique}@test.com",
            'username'        => "first{$unique}",
            'password'        => 'password123',
            'language'        => 'en:english',
            'employee_number' => $employeeNumber,
        ]);
        $this->assertTrue(json_decode($firstResponse->getJSON(), true)['success']);

        $secondResponse = $this->post('/employees/save', [
            'first_name'      => 'Second',
            'last_name'       => 'Number',
            'email'           => "second{$unique}@test.com",
            'username'        => "second{$unique}",
            'password'        => 'password123',
            'language'        => 'en:english',
            'employee_number' => $employeeNumber,
        ]);

        $secondResponse->assertStatus(200);
        $result = json_decode($secondResponse->getJSON(), true);

        $this->assertFalse($result['success']);
        $this->assertSame(lang('Employees.employee_number_duplicate'), $result['message']);
    }
}
