<?php

declare(strict_types=1);

namespace App\Domain\Documents\EmailCapture;

use Webklex\PHPIMAP\ClientManager;

/**
 * Implémentation réelle via webklex/php-imap (client IMAP pur PHP, pas besoin de l'extension
 * native ext-imap). Configuration : voir config/ged.php > email_capture.
 */
class WebklexEmailInbox implements EmailInbox
{
    /** @param array{host: ?string, port: int, encryption: string|false, validate_cert: bool, username: ?string, password: ?string, mailbox: string} $config */
    public function __construct(private readonly array $config) {}

    public function fetchUnseen(): iterable
    {
        $client = (new ClientManager())->make([
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'encryption' => $this->config['encryption'],
            'validate_cert' => $this->config['validate_cert'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'protocol' => 'imap',
        ]);

        $client->connect();

        $mailbox = $client->getFolder($this->config['mailbox']);

        if ($mailbox === null) {
            return;
        }

        foreach ($mailbox->messages()->whereUnseen()->get() as $message) {
            $attachments = [];
            foreach ($message->getAttachments() as $attachment) {
                $attachments[] = new CapturedAttachment((string) $attachment->getName(), (string) $attachment->getContent());
            }

            yield new InboundEmail(
                (string) $message->getSubject(),
                $attachments,
                fn () => $message->setFlag('Seen'),
            );
        }
    }
}
