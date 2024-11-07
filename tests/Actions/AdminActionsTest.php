<?php

use BicBucStriim\Actions\AdminActions;
use BicBucStriim\AppData\BicBucStriim;
use BicBucStriim\Utilities\RequestUtil;
use BicBucStriim\Utilities\TestHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AdminActions::class)]
class AdminActionsTest extends PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetUsers(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/users/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = '<form action="./admin/users/" method="post" id="newuserform"';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetUser(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/users/1/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = '<form action="./admin/users/1/" method="put" id="userform"';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetUserInvalidId(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/users/abc/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

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

        $request = RequestUtil::getServerRequest('POST', '/admin/users/', ['username' => 'testuser', 'password' => 'testpassword']);
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

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
        $request = RequestUtil::getServerRequest('POST', '/admin/users/', ['username' => '', 'password' => 'testpassword']);
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = 'Error while applying changes';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAddUserException(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('POST', '/admin/users/', ['username' => 'testuser', 'password' => 'testpassword']);
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $mock = $this->createMock(BicBucStriim::class);
        $mock->method('addUser')->willThrowException(new Exception('Test exception'));
        $app->getContainer()->set(BicBucStriim::class, $mock);

        $expected = 'No or bad configuration database.';
        $response = $app->handle($request);
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testAdmin(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = '<a href="./admin/configuration/">';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testConfiguration(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/configuration/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = '<form action="./admin/configuration/" method="post" id="adminform">';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetIdTemplates(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/idtemplates/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = 'Enter URLs with the replacement parameter';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testModifyIdTemplate(): void
    {
        $this->fail('DB freezes up');
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('PUT', '/admin/idtemplates/test1/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');
        $request = $request->withParsedBody([
            'name' => 'test1',
            'val' => 'testval',
            'label' => 'testlabel',
        ]);

        $expected = '{"msg":"Admin modified"}';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testClearIdTemplate(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('DELETE', '/admin/idtemplates/test1/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = 'Error while applying changes';
        $response = $app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testGetSmtpConfig(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('GET', '/admin/mail/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');

        $expected = '<title>BicBucStriim :: Mail configuration</title>';
        $response = $app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expected, (string) $response->getBody());
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    public function testChangeSmtpConfig(): void
    {
        $app = TestHelper::getApp();
        $request = RequestUtil::getServerRequest('PUT', '/admin/mail/');
        $request = $request->withHeader('PHP_AUTH_USER', 'admin')->withHeader('PHP_AUTH_PW', 'admin');
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
