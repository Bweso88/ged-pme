<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetentionDueSoon extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Document $document) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => "Le document « {$this->document->nom} » arrive à échéance de conservation.",
            'document_id' => $this->document->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Échéance de conservation à traiter')
            ->line("Le document « {$this->document->nom} » arrive à échéance de sa politique de conservation.")
            ->action('Traiter la proposition', route('archives.index'));
    }
}
