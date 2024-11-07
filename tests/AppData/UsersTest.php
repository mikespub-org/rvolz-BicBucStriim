<?php

if (!defined('REDBEAN_MODEL_PREFIX')) {
    define('REDBEAN_MODEL_PREFIX', '\\BicBucStriim\\Models\\');
}

use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Models\User;
use BicBucStriim\Models\R;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BicBucStriim::class)]
#[CoversClass(User::class)]
class UsersTest extends PHPUnit\Framework\TestCase
{
    public static $schema;
    public static $testschema;
    public static $db2;

    public static $data;
    public static $datadb;

    /** @var ?BicBucStriim */
    public $bbs;

    public function setUp(): void
    {
        self::$schema = dirname(__DIR__, 2) . '/data/schema.sql';
        self::$testschema = dirname(__DIR__, 2) . '/tests/data/schema.sql';
        self::$db2 = dirname(__DIR__, 2) . '/tests/fixtures/data2.db';

        self::$data = dirname(__DIR__, 2) . '/tests/data';
        self::$datadb = dirname(__DIR__, 2) . '/tests/data/data.db';
        if (file_exists(self::$data)) {
            system("rm -rf " . self::$data);
        }
        mkdir(self::$data);
        chmod(self::$data, 0o777);
        copy(self::$db2, self::$datadb);
        copy(self::$schema, self::$testschema);
        $this->bbs = new BicBucStriim(self::$datadb, false);
    }

    public function tearDown(): void
    {
        // Must use nuke() to clear caches etc.
        R::nuke();
        R::close();
        $this->bbs = null;
        system("rm -rf " . self::$data);
    }

    public function testAddUser(): void
    {
        $this->assertEquals(1, count($this->bbs->users()));
        $user = $this->bbs->addUser('testuser', 'testuser');
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
        $this->assertNotEquals('testuser', $user->password);
        $this->assertNull($user->tags);
        $this->assertNull($user->languages);
        $this->assertEquals(0, $user->role);
    }

    public function testAddUserEmptyUser(): void
    {
        $user = $this->bbs->addUser('', '');
        $this->assertNull($user);
    }

    public function testAddUserEmptyUsername(): void
    {
        $user = $this->bbs->addUser('testuser2', '');
        $this->assertNull($user);
    }

    public function testAddUserEmptyPassword(): void
    {
        $user = $this->bbs->addUser('', '');
        $this->assertNull($user);
    }

    public function testGetUser(): void
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $this->assertEquals(3, count($this->bbs->users()));
        $user = $this->bbs->user(3);
        $this->assertNotNull($user);
        $this->assertEquals('testuser2', $user->username);
        $this->assertNotEquals('testuser2', $user->password);
        $this->assertNull($user->tags);
        $this->assertNull($user->languages);
        $this->assertEquals(0, $user->role);
    }

    public function testDeleteUser(): void
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $this->assertEquals(3, count($this->bbs->users()));

        $deleted = $this->bbs->deleteUser(1);
        $this->assertFalse($deleted);

        $deleted = $this->bbs->deleteUser(100);
        $this->assertFalse($deleted);

        $deleted = $this->bbs->deleteUser(3);
        $this->assertTrue($deleted);
        $this->assertEquals(2, count($this->bbs->users()));
        $user = $this->bbs->user(2);
        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
    }

    public function testChangeUser(): void
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $users = $this->bbs->users();
        $password2 = $users[3]->password;

        $changed = $this->bbs->changeUser(3, $password2, 'deu', 'poetry', 'user');
        $this->assertEquals($password2, $changed->password);
        $this->assertEquals('deu', $changed->languages);
        $this->assertEquals('poetry', $changed->tags);

        $changed = $this->bbs->changeUser(3, 'new password', 'deu', 'poetry', 'user');
        $this->assertNotEquals($password2, $changed->password);
        $this->assertEquals('deu', $changed->languages);
        $this->assertEquals('poetry', $changed->tags);

        $changed = $this->bbs->changeUser(3, '', 'deu', 'poetry', 'user');
        $this->assertNull($changed);
    }

    public function testChangeUserRole(): void
    {
        $this->bbs->addUser('testuser', 'testuser');
        $this->bbs->addUser('testuser2', 'testuser2');
        $users = $this->bbs->users();
        $password2 = $users[3]->password;

        $this->assertEquals('0', $users[3]->role);
        $changed = $this->bbs->changeUser(3, $password2, 'deu', 'poetry', 'admin');
        $this->assertEquals('1', $changed->role);
        $changed = $this->bbs->changeUser(3, '', 'deu', 'poetry', 'admin');
        $this->assertNull($changed);
    }
}
