<?php

use BicBucStriim\Actions\AdminActions;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AdminActions::class)]
class AdminActionsTest extends PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetUsers(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/users/');

        $expected = '<div data-role="page" data-title="BicBucStriim :: Users" id="padmin_users" data-ajax="true">';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetUser(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/users/1/');

        $expected = '<div data-role="page" data-title="BicBucStriim :: Users" id="padmin_user" data-ajax="false">';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetUserInvalidId(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/users/abc/');

        $expected = 400;
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAddUserValid(): void
    {
        $app = TestHelper::getApp();
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        $users = $bbs->users();
        foreach ($users as $user) {
            if ($user['username'] === 'testuser') {
                $bbs->deleteUser($user['id']);
                break;
            }
        }

        $request = TestHelper::getAuthRequest('POST', '/admin/users/', ['username' => 'testuser', 'password' => 'testpassword']);

        $expected = 'Changes applied';
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString($expected, $result['msg']);

        $bbs->deleteUser($result['user']['id']);
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAddUserInvalid(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('POST', '/admin/users/', ['username' => '', 'password' => 'testpassword']);

        $expected = 'Error while applying changes';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAddUserException(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('POST', '/admin/users/', ['username' => 'testuser', 'password' => 'testpassword']);

        // Build mock BicBucStriim where addUser() throws exception
        $mock = $this->getMockBuilder(BicBucStriim::class)
            ->setConstructorArgs(['data/data.db', true])
            ->onlyMethods(['addUser'])
            ->getMock();
        $mock->expects($this->once())->method('addUser')->willThrowException(new Exception('Test exception'));
        $app->getContainer()->set(BicBucStriim::class, $mock);

        $expected = 'Error while applying changes';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDeleteUserInvalidId(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('DELETE', '/admin/users/abc/');

        $expected = 400;
        $response = $app->handle($request);
        $this->assertEquals($expected, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testDeleteUserValid(): void
    {
        $app = TestHelper::getApp();
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        foreach ($bbs->users() as $existing) {
            if ($existing['username'] === 'testuser') {
                $bbs->deleteUser($existing['id']);
                break;
            }
        }
        $user = $bbs->addUser('testuser', 'testpassword');
        $this->assertNotNull($user);
        $userId = $user->unbox()->getProperties()['id'];

        $request = TestHelper::getAuthRequest('DELETE', '/admin/users/' . $userId . '/');

        $expected = 'Changes applied';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        // the user must be gone, without leaving a trace behind
        $this->assertNull($bbs->user($userId));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testModifyUserValid(): void
    {
        $app = TestHelper::getApp();
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        foreach ($bbs->users() as $existing) {
            if ($existing['username'] === 'testuser') {
                $bbs->deleteUser($existing['id']);
                break;
            }
        }
        $user = $bbs->addUser('testuser', 'testpassword');
        $this->assertNotNull($user);
        $userId = $user->unbox()->getProperties()['id'];

        // same payload the admin_user page sends via js/script.js
        // (role select values are 'normal'/'admin', mapped by changeUser())
        $params = [
            'username' => 'testuser',
            'password' => 'newpassword',
            'languages' => 'deu',
            'tags' => 'poetry',
            'role' => 'admin',
        ];
        $request = TestHelper::getAuthRequest('PUT', '/admin/users/' . $userId . '/', $params);

        $expected = 'Changes applied';
        $response = $app->handle($request);
        $result = json_decode((string) $response->getBody(), true);
        $this->assertStringContainsString($expected, $result['msg']);
        $this->assertEquals('deu', $result['user']['languages']);
        $this->assertEquals('poetry', $result['user']['tags']);
        $this->assertEquals('1', (string) $result['user']['role']);

        // changes must be persisted, without leaving a trace behind
        $changed = $bbs->user($userId);
        $this->assertEquals('deu', $changed->languages);
        $this->assertEquals('poetry', $changed->tags);
        $this->assertTrue($bbs->deleteUser($userId));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testModifyUserInvalid(): void
    {
        $app = TestHelper::getApp();
        $bbs = $app->getContainer()->get(BicBucStriim::class);
        foreach ($bbs->users() as $existing) {
            if ($existing['username'] === 'testuser') {
                $bbs->deleteUser($existing['id']);
                break;
            }
        }
        $user = $bbs->addUser('testuser', 'testpassword');
        $this->assertNotNull($user);
        $userId = $user->unbox()->getProperties()['id'];

        // empty password: the model returns null, like UsersTest::testChangeUser
        $params = [
            'username' => 'testuser',
            'password' => '',
            'languages' => 'deu',
            'tags' => 'poetry',
            'role' => '0',
        ];
        $request = TestHelper::getAuthRequest('PUT', '/admin/users/' . $userId . '/', $params);

        $expected = 'Error while applying changes';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());

        $this->assertTrue($bbs->deleteUser($userId));
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAdmin(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/');

        $expected = '<div data-role="page" data-title="BicBucStriim :: Configuration" id="padmin">';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testConfiguration(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/configuration/');

        $expected = '<div data-role="page" data-title="BicBucStriim :: Configuration" id="padmin_configuration" data-ajax="false">';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetIdTemplates(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/idtemplates/');

        $expected = 'Enter URLs with the replacement parameter';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testModifyIdTemplate(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('PUT', '/admin/idtemplates/test1/');
        $request = $request->withParsedBody([
            'name' => 'test1',
            'url' => 'testval',
            'label' => 'testlabel',
        ]);

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testModifyIdTemplate')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testClearIdTemplate(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('DELETE', '/admin/idtemplates/test1/');

        $expected = '"msg": "Changes applied"';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetSmtpConfig(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('GET', '/admin/mail/');

        $expected = '<title>BicBucStriim :: Mail configuration</title>';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGetSmtpConfig')]
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testChangeSmtpConfig(): void
    {
        $app = TestHelper::getApp();
        $request = TestHelper::getAuthRequest('PUT', '/admin/mail/');
        $request = $request->withParsedBody([
            'username' => 'testuser',
            'password' => 'testpassword',
            'smtpserver' => 'testsmtpserver',
            'smtpport' => 25,
            'smtpenc' => 0,
        ]);

        $expected = '<title>BicBucStriim :: Undefined message!</title>';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }
}
