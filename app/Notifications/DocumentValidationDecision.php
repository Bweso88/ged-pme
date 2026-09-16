<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use App\Models\WorkflowAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentValidationDecision extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Document $document, private readonly string $decision, private readonly ?string $commentaire = null) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    private function label(): string
    {
        return match ($this->decision) {
            WorkflowAction::APPROUVE => 'approuvé',
            WorkflowAction::REJETE => 'rejeté',
            WorkflowAction::DEMANDE_MODIFICATION => 'renvoyé pour modification',
            default => $this->decision,
        };
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message' => "Votre document « {$this->document->nom} » a été {$this->label()}.",
            'document_id' => $this->document->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Décision sur votre document')
            ->line("Votre document « {$this->document->nom} » a été {$this->label()}.");

        if ($this->commentaire) {
            $mail->line("Commentaire : {$this->commentaire}");
        }

        return $mail->action('Consulter le document', route('documents.show', $this->document));
    }
}
