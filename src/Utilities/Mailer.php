<?php

namespace BicBucStriim\Utilities;

use BicBucStriim\AppData\Settings;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use Exception;

class Mailer
{
    // SMTP transport
    public const SMTP = 0;
    // Sendmail transport
    public const SENDMAIL = 1;
    // PHP mail transport
    public const MAIL = 2;
    public const MOCK = 3;

    public const SSL = 'ssl';
    public const TLS = 'tls';

    /** @var PHPMailer */
    protected $mailer;
    /** @var string */
    protected $dump;

    /**
     * Initialize the transport mechanism. Default is PHP mail.
     * @param int  $transportType one of SMTP, SENDMAIL or MAIL
     * @param array<string, mixed> $config
     * Configuration values, mainly for the SMTP transport:
     *     'smtp-server' - server name
     *     'smtp-port' - port
     *     'smtp-encryption' - 'ssl' or 'tls', if encryption is required
     *     'username' - SMTP user
     *     'password' - SMTP password
     */
    final public function __construct($transportType = Mailer::MAIL, $config = [])
    {
        if ($transportType == Mailer::SMTP) {
            $this->setSmtpConfig($config);
        } elseif ($transportType == Mailer::SENDMAIL) {
            $this->setSendmailConfig($config);
        } elseif ($transportType == Mailer::MOCK) {
            $this->setMockConfig($config);
        } else {
            $this->setMailConfig($config);
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public function setMockConfig($config)
    {
        //Create a new PHPMailer instance
        $this->mailer = new class extends PHPMailer {
            public function send()
            {
                return true;
            }
        };
        $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
    }

    /**
     * See https://github.com/PHPMailer/PHPMailer/blob/master/examples/mail.phps
     * @param array<string, mixed> $config
     * @return void
     */
    public function setMailConfig($config)
    {
        //Create a new PHPMailer instance
        $this->mailer = new PHPMailer();
        $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
    }

    /**
     * See https://github.com/PHPMailer/PHPMailer/blob/master/examples/sendmail.phps
     * @param array<string, mixed> $config
     * @return void
     */
    public function setSendmailConfig($config)
    {
        //Create a new PHPMailer instance
        $this->mailer = new PHPMailer();
        $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
        //Set PHPMailer to use the sendmail transport
        $this->mailer->isSendmail();
    }

    /**
     * See https://github.com/PHPMailer/PHPMailer/blob/master/examples/smtp.phps
     * @param array<string, mixed> $config
     * @return void
     */
    public function setSmtpConfig($config)
    {
        //Create a new PHPMailer instance
        $this->mailer = new PHPMailer();
        $this->mailer->CharSet = PHPMailer::CHARSET_UTF8;
        //Tell PHPMailer to use SMTP
        $this->mailer->isSMTP();
        //Enable SMTP debugging
        $this->mailer->SMTPDebug = SMTP::DEBUG_OFF;
        //$this->mailer->SMTPDebug = SMTP::DEBUG_CLIENT;
        //$this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
        //Set the hostname of the mail server
        $this->mailer->Host = $config['smtp-server'];
        //Set the SMTP port number - likely to be 25, 465 or 587
        $this->mailer->Port = $config['smtp-port'];
        //Whether to use SMTP authentication
        $this->mailer->SMTPAuth = true;
        //Username to use for SMTP authentication
        $this->mailer->Username = $config['username'];
        //Password to use for SMTP authentication
        $this->mailer->Password = $config['password'];
        if (isset($config['smtp-encryption'])) {
            $this->setSmtpEncryption($config);
        }
    }

    /**
     * See https://github.com/PHPMailer/PHPMailer/blob/master/examples/ssl_options.phps
     * @param array<string, mixed> $config
     * @return void
     */
    public function setSmtpEncryption($config)
    {
        if ($config['smtp-encryption'] == static::TLS) {
            //Set the SMTP port number:
            // - 587 for SMTP+STARTTLS
            //$this->mailer->Port = 587;
            //Set the encryption mechanism to use:
            // - STARTTLS (explicit TLS on port 587)
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            //Custom connection options
            //Note that these settings are INSECURE
            //$this->mailer->SMTPOptions = [];
        } elseif ($config['smtp-encryption'] == static::SSL) {
            //Set the SMTP port number:
            // - 465 for SMTP with implicit TLS, a.k.a. RFC8314 SMTPS or
            //$this->mailer->Port = 465;
            //Set the encryption mechanism to use:
            // - SMTPS (implicit TLS on port 465) or
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            //Custom connection options
            //Note that these settings are INSECURE
            //$this->mailer->SMTPOptions = [];
        } else {
            throw new Exception('Invalid SMTP encryption option');
        }
    }

    /**
     * Create a mail for sending a book to a Kindle account or elsewhere.
     * @param string $bookpath  complete path to the book/file to be sent
     * @param string $subject   mail subject line
     * @param string $recipient mail address of recipient
     * @param string $sender    mail address of sender
     * @param string $filename  new filename
     * @return bool true if successfully created with attachment, false otherwise
     */
    public function createBookMessage($bookpath, $subject, $recipient, $sender, $filename)
    {
        $contentType = CalibreUtil::titleMimeType($bookpath);
        // See https://github.com/PHPMailer/PHPMailer/blob/master/examples/send_file_upload.phps
        $this->mailer->setFrom($sender);
        $this->mailer->addAddress($recipient);
        $this->mailer->Subject = $subject;
        $this->mailer->Body = 'This book was sent to you by BicBucStriim.';
        //Attach the uploaded file
        if (!$this->mailer->addAttachment($bookpath, $filename, PHPMailer::ENCODING_BASE64, $contentType)) {
            $this->dump = 'Failed to attach file ' . $filename;
            return false;
        }
        return true;
    }

    /**
     * Returns a dump of the last sending process. Just for troubleshooting.
     * @return string
     */
    public function getDump()
    {
        return $this->dump;
    }

    /**
     * Send an email via the transport.
     * @return int number of messages sent
     */
    public function sendMessage()
    {
        // See also https://github.com/PHPMailer/PHPMailer/blob/master/examples/exceptions.phps
        try {
            //send the message, check for errors
            if (!$this->mailer->send()) {
                $this->dump = $this->mailer->ErrorInfo;
                return 0;
            } else {
                return 1;
            }
            //Note that we don't need check the response from this because it will throw an exception if it has trouble
            //$this->mailer->send();
        } catch (Exception $e) {
            $this->dump = $e->getMessage();
            return 0;
        }
    }

    /**
     * Get the message after sending it
     * @return string
     */
    public function getMessage()
    {
        return $this->mailer->getSentMIMEMessage();
    }

    /**
     * Get new Mailer instance
     * @param Settings $settings
     * @return self
     */
    public static function newInstance($settings)
    {
        if ($settings->mailer == static::SMTP) {
            $mail = [
                'username' => $settings[Settings::SMTP_USER],
                'password' => $settings[Settings::SMTP_PASSWORD],
                'smtp-server' => $settings[Settings::SMTP_SERVER],
                'smtp-port' => $settings[Settings::SMTP_PORT],
            ];
            if ($settings[Settings::SMTP_ENCRYPTION] == 1) {
                $mail['smtp-encryption'] = static::SSL;
            } elseif ($settings[Settings::SMTP_ENCRYPTION] == 2) {
                $mail['smtp-encryption'] = static::TLS;
            }
            $mailer = new self(static::SMTP, $mail);
        } elseif ($settings->mailer == static::SENDMAIL) {
            $mailer = new self(static::SENDMAIL);
        } elseif ($settings->mailer == static::MOCK) {
            $mailer = new self(static::MOCK);
        } else {
            $mailer = new self(static::MAIL);
        }
        return $mailer;
    }
}
