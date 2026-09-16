<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentShared extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Document $document, private readonly string $sharedByName) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => "{$this->sharedByName} a partagé « {$this->document->nom} » avec vous.",
            'document_id' => $this->document->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Un document a été partagé avec vous')
            ->line("{$this->sharedByName} a partagé le document « {$this->document->nom} » avec vous.")
            ->action('Consulter le document', route('documents.show', $this->document));
    }
}
