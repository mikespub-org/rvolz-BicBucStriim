<?php

use BicBucStriim\Utilities\Mailer;

/**
 * @covers \BicBucStriim\Utilities\Mailer
 * @covers \Utilities
 */
class MailerTest extends PHPUnit\Framework\TestCase
{
    public const FIXT = './tests/fixtures';

    public static function mailProvider()
    {
        return [
            // type, method, expected, result
            ['mail', 'getMailerMail', 1, ''],
            ['sendmail', 'getMailerSendmail', 0, 'Expected response code 220 but got an empty response'],
            ['smtp', 'getMailerSmtp', 0, 'Connection could not be established with host'],
        ];
    }

    /**
     * @dataProvider mailProvider
     */
    public function testSendMail($type, $method, $expected, $result)
    {
        /** @var Mailer $mailer */
        $mailer = [$this, $method]();
        $bookpath = self::FIXT . '/lib2/Gotthold Ephraim Lessing/Lob der Faulheit (1)/Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $subject = 'BicBucStriim';
        $recipient = 'kindle@example.org';
        $sender = 'bicbucstriim@example.org';
        $filename = 'Serie Lessing [1] Lob der Faulheit - Gotthold Ephraim Lessing.epub';
        $message = $mailer->createBookMessage($bookpath, $subject, $recipient, $sender, $filename);
        //$message->clearSigners();
        $content = $message->toString();
        $pattern = [
            '/Message-ID: <\w+@swift\.generated>/',
            '/Date: .+/',
            '/ boundary="_=_swift_\w+_=_"/',
            '/--_=_swift_\w+_=_/m',
        ];
        $replacement = [
            'Message-ID: <generated@swift.generated>',
            'Date: generated',
            ' boundary="_=_swift_generated_=_"',
            '--_=_swift_generated_=_',
        ];
        $content = preg_replace($pattern, $replacement, $content);
        $template = file_get_contents(self::FIXT . '/test-mailer.message.txt');
        $this->assertEquals($template, $content);
        $send_success = $mailer->sendMessage($message);
        $this->assertEquals($expected, $send_success);
        $dump = $mailer->getDump();
        $this->assertStringContainsString($result, $dump);
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
