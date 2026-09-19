<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Domain\Documents\EmailCapture\CapturedAttachment;
use App\Domain\Documents\EmailCapture\EmailInbox;
use App\Models\Folder;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Dépose dans YOSEFA les pièces jointes des emails non lus de la boîte configurée
 * (config/ged.php > email_capture), dans un unique dossier "à classer" — un tri fin reste manuel,
 * cette fonctionnalité évite seulement la copie manuelle depuis la messagerie. Chaque pièce
 * jointe passe par le même DocumentUploadService que n'importe quel dépôt (antivirus, contrôle
 * de type/taille compris) : une pièce jointe rejetée est simplement ignorée, jamais bloquante
 * pour le reste du message.
 */
class EmailCaptureService
{
    public function __construct(
        private readonly EmailInbox $inbox,
        private readonly DocumentUploadService $uploadService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function capture(): int
    {
        $folder = Folder::withoutTenantScope()->find(config('ged.email_capture.target_folder_id'));
        $uploader = User::query()->find(config('ged.email_capture.uploader_user_id'));

        if ($folder === null || $uploader === null) {
            throw new RuntimeException(
                "Capture email mal configurée : GED_EMAIL_CAPTURE_TARGET_FOLDER_ID ou GED_EMAIL_CAPTURE_UPLOADER_USER_ID ne correspond à aucun dossier/utilisateur.",
            );
        }

        $this->tenantContext->set($folder->company);

        $deposited = 0;

        foreach ($this->inbox->fetchUnseen() as $email) {
            foreach ($email->attachments as $attachment) {
                if ($this->deposit($folder, $uploader, $attachment)) {
                    $deposited++;
                }
            }

            $email->markAsProcessed();
        }

        return $deposited;
    }

    private function deposit(Folder $folder, User $uploader, CapturedAttachment $attachment): bool
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'yosefa_mail_');
        file_put_contents($tmpPath, $attachment->content);

        try {
            $uploadedFile = new UploadedFile($tmpPath, $attachment->filename, test: true);
            $this->uploadService->upload($folder, $uploadedFile, $uploader);

            return true;
        } catch (ValidationException) {
            // Extension interdite, antivirus, taille... : on ignore cette pièce jointe et on
            // continue avec les suivantes plutôt que de faire échouer toute la capture.
            return false;
        } finally {
            @unlink($tmpPath);
        }
    }
}
