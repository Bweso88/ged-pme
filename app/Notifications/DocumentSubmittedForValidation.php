<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSubmittedForValidation extends Notification implements ShouldQueue
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
            'message' => "Document « {$this->document->nom} » en attente de votre validation.",
            'document_id' => $this->document->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document en attente de validation')
            ->line("Le document « {$this->document->nom} » attend votre décision.")
            ->action('Consulter le document', route('documents.show', $this->document));
    }
}
