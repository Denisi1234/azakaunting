<?php

namespace Tests\Models;

use App\Models\Employee;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Tests\Support\DatabaseBootstrapTrait;
use Tests\Support\EmployeeFixtureTrait;

/**
 * @internal
 */
final class EmployeeTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use DatabaseBootstrapTrait;
    use EmployeeFixtureTrait;

    protected $migrate     = true;
    protected $migrateOnce = true;
    protected $seedOnce    = true;
    protected $refresh     = false;
    protected $namespace;

    protected function setUp(): void
    {
        $this->resetDatabaseOnce();

        parent::setUp();
    }

    public function testIsAdminReturnsTrueForPersonId1(): void
    {
        $employeeModel = model(Employee::class);

        $result = $employeeModel->isAdmin(1);

        $this->assertTrue($result);
    }

    public function testIsAdminReturnsTrueForEmployeeWithAllPermissions(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['has_grant'])
            ->getMock();

        $employeeModel->method('has_grant')
            ->willReturn(true);

        $result = $employeeModel->isAdmin(2);

        $this->assertTrue($result);
    }

    public function testIsAdminReturnsFalseWhenMissingPermissions(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['has_grant'])
            ->getMock();

        $employeeModel->method('has_grant')
            ->willReturnCallback(static fn ($permissionId, $personId) => $permissionId !== 'config');

        $result = $employeeModel->isAdmin(3);

        $this->assertFalse($result);
    }

    public function testCanModifyEmployeeReturnsTrueForOwnAccount(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $employeeModel->method('isAdmin')
            ->willReturn(false);

        $result = $employeeModel->canModifyEmployee(1, 1);

        $this->assertTrue($result);
    }

    public function testCanModifyEmployeeReturnsTrueForOwnAdminAccount(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $employeeModel->method('isAdmin')
            ->willReturn(true);

        $result = $employeeModel->canModifyEmployee(1, 1);

        $this->assertTrue($result);
    }

    public function testCanModifyEmployeeReturnsFalseWhenNonAdminModifiesAdmin(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $employeeModel->method('isAdmin')
            ->willReturnCallback(static fn ($personId) => $personId === 1);

        $result = $employeeModel->canModifyEmployee(1, 2);

        $this->assertFalse($result);
    }

    public function testCanModifyEmployeeReturnsTrueWhenAdminModifiesNonAdmin(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $employeeModel->method('isAdmin')
            ->willReturnCallback(static fn ($personId) => $personId === 1);

        $result = $employeeModel->canModifyEmployee(2, 1);

        $this->assertTrue($result);
    }

    public function testCanModifyEmployeeReturnsTrueWhenNonAdminModifiesNonAdmin(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $employeeModel->method('isAdmin')
            ->willReturn(false);

        $result = $employeeModel->canModifyEmployee(2, 3);

        $this->assertTrue($result);
    }

    public function testCanModifyEmployeeReturnsFalseForNonAdminEditingAdmin(): void
    {
        $employeeModel = $this->getMockBuilder(Employee::class)
            ->onlyMethods(['isAdmin'])
            ->getMock();

        $employeeModel->method('isAdmin')
            ->willReturnCallback(static fn ($personId) => $personId === 1);

        $result = $employeeModel->canModifyEmployee(1, 2);

        $this->assertFalse($result);
    }

    public function testHasGrantReturnsTrueForActualGrant(): void
    {
        $employeeModel = model(Employee::class);

        $result = $employeeModel->has_grant('employees', 1);

        $this->assertTrue($result);
    }

    public function testHasGrantReturnsFalseForMissingGrant(): void
    {
        $employeeModel = model(Employee::class);

        $result = $employeeModel->has_grant('nonexistent_permission', 1);

        $this->assertFalse($result);
    }

    public function testExistingEmployeeKeepsOriginalGrantsWhenGrantChangeDisallowed(): void
    {
        $employeeId = $this->createEmployee(
            first_name: 'Grant',
            last_name: 'Tester',
            grants: [
                ['permission_id' => 'customers', 'menu_group' => 'home'],
            ],
        );

        $originalDisallowGrantChange = getenv('DISALLOW_GRANT_CHANGE');
        putenv('DISALLOW_GRANT_CHANGE=true');

        try {
            $employeeModel = model(Employee::class);
            $personData    = ['first_name' => 'Grant', 'last_name' => 'Tester', 'email' => "granttester_upd_{$employeeId}@test.com"];
            $employeeData  = ['username' => "granttester_upd_{$employeeId}", 'language_code' => 'en', 'language' => 'english'];
            $newGrantsData = [['permission_id' => 'sales', 'menu_group' => 'home']];

            $saveEmployeeResult = $employeeModel->save_employee($personData, $employeeData, $newGrantsData, $employeeId);
            $this->assertTrue($saveEmployeeResult);

            $this->assertTrue($employeeModel->has_grant('customers', $employeeId));
            $this->assertFalse($employeeModel->has_grant('sales', $employeeId));
        } finally {
            $originalDisallowGrantChange === false
                ? putenv('DISALLOW_GRANT_CHANGE')
                : putenv("DISALLOW_GRANT_CHANGE={$originalDisallowGrantChange}");
        }
    }

    public function testNewEmployeeCreationWithGrantsRejectedWhenGrantChangeDisallowed(): void
    {
        $originalDisallowGrantChange = getenv('DISALLOW_GRANT_CHANGE');
        putenv('DISALLOW_GRANT_CHANGE=true');

        try {
            $result = $this->createEmployeeExpectingFailure(
                first_name: 'Rejected',
                last_name: 'Tester',
                grants: [
                    ['permission_id' => 'customers', 'menu_group' => 'home'],
                ],
            );

            $this->assertFalse($result);
        } finally {
            $originalDisallowGrantChange === false
                ? putenv('DISALLOW_GRANT_CHANGE')
                : putenv("DISALLOW_GRANT_CHANGE={$originalDisallowGrantChange}");
        }
    }

    public function testExistingEmployeeGrantsUpdateWhenGrantChangeAllowed(): void
    {
        $employeeId = $this->createEmployee(
            first_name: 'Grant',
            last_name: 'Tester',
            grants: [
                ['permission_id' => 'customers', 'menu_group' => 'home'],
            ],
        );

        $employeeModel = model(Employee::class);
        $personData    = ['first_name' => 'Grant', 'last_name' => 'Tester', 'email' => "granttester_upd_{$employeeId}@test.com"];
        $employeeData  = ['username' => "granttester_upd_{$employeeId}", 'language_code' => 'en', 'language' => 'english'];
        $newGrantsData = [['permission_id' => 'sales', 'menu_group' => 'home']];

        $employeeModel->save_employee($personData, $employeeData, $newGrantsData, $employeeId);

        $this->assertFalse($employeeModel->has_grant('customers', $employeeId));
        $this->assertTrue($employeeModel->has_grant('sales', $employeeId));
    }

    public function testNewEmployeeCreationWithGrantsSucceedsWhenGrantChangeAllowed(): void
    {
        $result = $this->createEmployeeExpectingFailure(
            first_name: 'Granted',
            last_name: 'Tester',
            grants: [
                ['permission_id' => 'customers', 'menu_group' => 'home'],
            ],
        );

        $this->assertTrue((bool) $result);
    }

    public function testCheckPassword_AcceptsLegacyMd5Hash(): void
    {
        $username = 'legacy_' . uniqid();
        $personId = $this->createEmployee(
            email: $username . '@test.com',
            username: $username,
        );

        $this->db->table('employees')->where('person_id', $personId)->update([
            'password'     => md5('legacy-pass'),
            'hash_version' => 1,
        ]);

        $employeeModel = model(Employee::class);

        $this->assertTrue($employeeModel->check_password($username, 'legacy-pass'));
        $this->assertFalse($employeeModel->check_password($username, 'wrong-pass'));
    }

    public function testEmployeeNumberExists_RejectsNumberUsedByAnotherEmployee(): void
    {
        $unique         = uniqid();
        $employeeNumber = 'EMP-' . $unique;

        $firstId = $this->createEmployee(
            email: 'first.' . $unique . '@test.com',
            username: 'first_' . $unique,
            employee: ['employee_number' => $employeeNumber],
        );
        $secondId = $this->createEmployee(
            email: 'second.' . $unique . '@test.com',
            username: 'second_' . $unique,
        );

        $employeeModel = model(Employee::class);

        $this->assertTrue($employeeModel->employeeNumberExists($secondId, $employeeNumber));
        $this->assertFalse($employeeModel->employeeNumberExists($firstId, $employeeNumber));
        $this->assertFalse($employeeModel->employeeNumberExists($secondId, ''));
    }
}
