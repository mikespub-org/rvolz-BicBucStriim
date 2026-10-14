<?php

declare(strict_types=1);

namespace BicBucStriim\Tests\Session;

use BicBucStriim\Session\SessionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Session cookie/segment helpers.
 */
#[CoversClass(\BicBucStriim\Session\Session::class)]
#[CoversClass(SessionFactory::class)]
class SessionTest extends TestCase
{
    public function testSessionCookieHelpers(): void
    {
        $session = new SessionFactory()->newInstance(['lang' => 'de']);

        $this->assertSame('de', $session->getCookie('lang'));
        $this->assertSame('fallback', $session->getCookie('missing', 'fallback'));
        $this->assertNull($session->getCookie('missing'));

        $session->setCookie('lang', 'fr');
        $this->assertSame('fr', $session->getCookie('lang'));
    }

    public function testSessionSegments(): void
    {
        $session = new SessionFactory()->newInstance([]);

        // Segments are distinct per name: a value set on the local segment
        // is visible there and absent from the auth segment.
        $local = $session->getLocalSegment();
        $local->set('k', 'v');
        $this->assertSame('v', $local->get('k'));

        $auth = $session->getAuthSegment();
        $this->assertNull($auth->get('k'));
        $this->assertNotSame($local, $auth);
    }
}
