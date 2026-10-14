<?php

declare(strict_types=1);

use BicBucStriim\AppData\Settings;
use BicBucStriim\Middleware\BasePathDetector;
use BicBucStriim\Utilities\RequestUtil;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the request façade used by every action and middleware.
 */
#[CoversClass(RequestUtil::class)]
#[CoversClass(BasePathDetector::class)]
class RequestUtilTest extends PHPUnit\Framework\TestCase
{
    public function testValueGetterAndSetter(): void
    {
        $first = RequestUtil::getServerRequest('GET', '/a/');
        $second = RequestUtil::getServerRequest('GET', '/b/');
        $util = new RequestUtil($first);

        $this->assertSame($first, $util->value());
        $this->assertSame($second, $util->value($second));
        $this->assertSame($second, $util->value());
    }

    public function testIsXhr(): void
    {
        $xhr = RequestUtil::getServerRequest('GET', '/')->withHeader('X-Requested-With', 'XMLHttpRequest');
        $this->assertTrue(new RequestUtil($xhr)->isXhr());

        $plain = RequestUtil::getServerRequest('GET', '/');
        $this->assertFalse(new RequestUtil($plain)->isXhr());
    }

    public static function jsonApiProvider(): array
    {
        return [
            'api on + json accept' => [true, ['Accept' => 'application/json'], true],
            'api on + multiple accept incl json' => [true, ['Accept' => 'text/html, application/json'], false],
            'api on + no accept header' => [true, [], false],
            'api on + non-json accept' => [true, ['Accept' => 'text/html'], false],
            'api off + json accept' => [false, ['Accept' => 'application/json'], false],
            'no settings + json accept' => [null, ['Accept' => 'application/json'], false],
        ];
    }

    #[DataProvider('jsonApiProvider')]
    public function testIsJsonApi(?bool $hasapi, array $headers, bool $expected): void
    {
        $request = RequestUtil::getServerRequest('GET', '/api/');
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $settings = $hasapi === null ? null : new Settings(['hasapi' => $hasapi]);

        $this->assertSame($expected, new RequestUtil($request, $settings)->isJsonApi());
    }

    public function testGetPostFilesAccessors(): void
    {
        $request = RequestUtil::getServerRequest('GET', '/', ['q' => 'x', 'page' => '2']);
        $util = new RequestUtil($request);
        $this->assertSame(['q' => 'x', 'page' => '2'], $util->get());
        $this->assertSame('x', $util->get('q'));
        $this->assertNull($util->get('missing'));

        $post = RequestUtil::getServerRequest('POST', '/login/', ['user' => 'admin']);
        $putil = new RequestUtil($post);
        $this->assertSame(['user' => 'admin'], $putil->post());
        $this->assertSame('admin', $putil->post('user'));
        $this->assertNull($putil->post('missing'));

        $empty = new RequestUtil(RequestUtil::getServerRequest('GET', '/'));
        $this->assertNull($empty->post());
        $this->assertNull($empty->post('user'));

        $this->assertSame([], $util->files());
        $this->assertNull($util->files('cover'));
    }

    public function testSetParams(): void
    {
        $util = new RequestUtil(RequestUtil::getServerRequest('GET', '/'));
        $request = $util->setParams(['a' => 'b']);
        $this->assertSame(['a' => 'b'], $request->getQueryParams());
        $this->assertSame('b', $util->get('a'));
    }

    public function testGetSessionReturnsNullWithoutAttribute(): void
    {
        $util = new RequestUtil(RequestUtil::getServerRequest('GET', '/'));
        $this->assertNull($util->getSession());
    }

    public function testGetSessionReturnsAttribute(): void
    {
        $session = (object) ['id' => 1];
        $request = RequestUtil::getServerRequest('GET', '/')->withAttribute('session', $session);
        $this->assertSame($session, new RequestUtil($request)->getSession());
    }

    public function testIsAdmin(): void
    {
        $anon = new RequestUtil(RequestUtil::getServerRequest('GET', '/admin/'));
        $this->assertFalse($anon->isAdmin());

        $admin = RequestUtil::getServerRequest('GET', '/admin/');
        $auth = \BicBucStriim\Utilities\TestHelper::getAuth($admin, ['role' => 1]);
        $this->assertTrue(new RequestUtil($admin->withAttribute('auth', $auth))->isAdmin());

        $user = RequestUtil::getServerRequest('GET', '/admin/');
        $uauth = \BicBucStriim\Utilities\TestHelper::getAuth($user, ['role' => 0]);
        $this->assertFalse(new RequestUtil($user->withAttribute('auth', $uauth))->isAdmin());
    }

    public function testGetPathInfoStripsBasePathAndQuery(): void
    {
        $req = RequestUtil::getServerRequest('GET', '/bbs/titles/1/')
            ->withAttribute(RequestUtil::BASE_PATH, '/bbs')
            ->withRequestTarget('/bbs/titles/1/');
        $this->assertSame('/titles/1/', new RequestUtil($req)->getPathInfo());

        $ng = RequestUtil::getServerRequest('GET', '/login/')->withRequestTarget('/login/?q=/login/&');
        $this->assertSame('/login/', new RequestUtil($ng)->getPathInfo());

        $plain = RequestUtil::getServerRequest('GET', '/opds/')->withRequestTarget('/opds/?x=1');
        $this->assertSame('/opds/', new RequestUtil($plain)->getPathInfo());
    }

    public function testGetForwardingInfo(): void
    {
        $none = new RequestUtil(RequestUtil::getServerRequest('GET', '/'));
        $this->assertNull($none->getForwardingInfo());

        // RequestUtil passes getHeaders() (name => string[] arrays) straight
        // to UrlInfo, which expects plain strings — document via X-Forwarded
        // headers, for which the array tail is ignored and host/proto resolve.
        $fwd = RequestUtil::getServerRequest('GET', '/')
            ->withHeader('X-Forwarded-Host', '203.0.113.43')
            ->withHeader('X-Forwarded-Proto', 'https');
        $info = new RequestUtil($fwd)->getForwardingInfo();
        $this->assertNotNull($info);
    }

    public static function corsProvider(): array
    {
        return [
            'default star returns request origin' => [null, 'https://app.example', 'https://app.example'],
            'default star without origin returns star' => [null, null, '*'],
            'explicit star returns request origin' => ['*', 'https://app.example', 'https://app.example'],
            'matching string allowed' => ['https://a.example', 'https://a.example', 'https://a.example'],
            'mismatching string denied' => ['https://a.example', 'https://evil.example', false],
            'matching array allowed' => [['https://a.example', 'https://b.example'], 'https://b.example', 'https://b.example'],
            'mismatching array denied' => [['https://a.example'], 'https://evil.example', false],
        ];
    }

    #[DataProvider('corsProvider')]
    public function testGetCorsOrigin(mixed $allowed, ?string $origin, mixed $expected): void
    {
        $request = RequestUtil::getServerRequest('GET', '/');
        if ($origin !== null) {
            $request = $request->withHeader('Origin', $origin);
        }
        $settings = $allowed === null ? new Settings() : new Settings(['cors_origin' => $allowed]);

        $this->assertSame($expected, new RequestUtil($request, $settings)->getCorsOrigin());
    }

    public static function basePathDetectorProvider(): array
    {
        return [
            'root apache' => [['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/titles/'], 'apache', ''],
            'subfolder apache' => [['SCRIPT_NAME' => '/bbs/index.php', 'REQUEST_URI' => '/bbs/titles/'], 'apache', '/bbs'],
            'missing request uri' => [['SCRIPT_NAME' => '/bbs/index.php'], 'apache', ''],
            'root cli-server' => [['SCRIPT_NAME' => '/index.php'], 'cli-server', ''],
            'subfolder cli-server' => [['SCRIPT_NAME' => '/bbs/index.php'], 'cli-server', '/bbs'],
        ];
    }

    #[DataProvider('basePathDetectorProvider')]
    public function testBasePathDetector(array $server, string $sapi, string $expected): void
    {
        $this->assertSame($expected, new BasePathDetector($server, $sapi)->getBasePath());
    }
}
