<?php

use BicBucStriim\Utilities\Mailer;

/**
 * @covers \BicBucStriim\Utilities\Mailer
 */
class MailerTest extends PHPUnit\Framework\TestCase
{
    public const FIXT = './tests/fixtures';

    public static function mailProvider()
    {
        return [
            // type, method, expected, result
            ['mail', 'getMailerMail', 0, ''],
            ['sendmail', 'getMailerSendmail', 0, 'Could not execute: /usr/sbin/sendmail -t -i'],
            ['smtp', 'getMailerSmtp', 0, 'SMTP connect() failed.'],
        ];
    }

    /**
     * @dataProvider mailProvider
     */
    public function testSendMail($type, $method, $expected, $result): void
    {
        /** @var Mailer $mailer */
        $mailer = [$this, $method]();
        $bookpath = self::FIXT . '/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $subject = 'BicBucStriim';
        $recipient = 'kindle@example.org';
        $sender = 'bicbucstriim@example.org';
        $filename = 'Serie Lessing [1] Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $message_success = $mailer->createBookMessage($bookpath, $subject, $recipient, $sender, $filename);
        $this->assertEquals(1, $message_success);

        $send_success = $mailer->sendMessage();
        $this->assertEquals($expected, $send_success);
        $dump = $mailer->getDump();
        $this->assertStringContainsString($result, $dump);

        $content = $mailer->getMessage();
        $pattern = [
            '/Message-ID: <.+>/',
            '/Date: .+/',
            '/ boundary="b1=_\w+"/',
            '/--b1=_\w+/',
        ];
        $replacement = [
            'Message-ID: <generated>',
            'Date: generated',
            ' boundary="b1=_generated"',
            '--b1=_generated',
        ];
        $content = preg_replace($pattern, $replacement, $content);
        $template = file_get_contents(self::FIXT . '/test-mailer-' . $type . '.message.txt');
        $this->assertEquals($template, $content);
    }

    public function getMailerSmtp()
    {
        $config = $this->getMailerSmtpConfig();
        $mailer = new Mailer(Mailer::SMTP, $config);
        return $mailer;
    }

    public function getMailerSmtpConfig($encryption = 0)
    {
        $config = [
            'username' => 'smtp_user',
            'password' => 'smtp_password',
            //'smtp-server' => 'smtp_server.example.org',
            'smtp-server' => 'localhost',
            'smtp-port' => 25,
        ];
        if ($encryption == 1) {
            $config['smtp-encryption'] = Mailer::SSL;
            $config['smtp-port'] = 465;
        } elseif ($encryption == 2) {
            $config['smtp-encryption'] = Mailer::TLS;
            $config['smtp-port'] = 587;
        }
        return $config;
    }

    public function getMailerSendmail()
    {
        $mailer = new Mailer(Mailer::SENDMAIL);
        return $mailer;
    }

    public function getMailerMail()
    {
        $mailer = new Mailer(Mailer::MAIL);
        return $mailer;
    }
}
