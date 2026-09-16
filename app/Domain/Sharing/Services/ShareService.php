<?php

declare(strict_types=1);

namespace App\Domain\Sharing\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\User;
use App\Models\UserGroup;
use App\Notifications\DocumentShared;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Partage sécurisé (voir doc ged-pme/docs/01, §19) : jamais de lien public permanent — la
 * contrainte est doublement appliquée (ici et par le CHECK SQL sur document_shares).
 */
class ShareService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function shareWithUser(Document $document, User $target, User $author, ?\DateTimeInterface $expireAt = null): DocumentShare
    {
        $share = DocumentShare::query()->create([
            'document_id' => $document->id,
            'cree_par' => $author->id,
            'type' => DocumentShare::TYPE_UTILISATEUR,
            'cible_user_id' => $target->id,
            'expire_at' => $expireAt,
        ]);

        $target->notify(new DocumentShared($document, $author->name));
        $this->auditLogger->log($author, AuditLog::PARTAGE, $document, ['cible_user_id' => $target->id]);

        return $share;
    }

    public function shareWithGroup(Document $document, UserGroup $group, User $author, ?\DateTimeInterface $expireAt = null): DocumentShare
    {
        $share = DocumentShare::query()->create([
            'document_id' => $document->id,
            'cree_par' => $author->id,
            'type' => DocumentShare::TYPE_GROUPE,
            'cible_group_id' => $group->id,
            'expire_at' => $expireAt,
        ]);

        $group->members->each(fn (User $u) => $u->notify(new DocumentShared($document, $author->name)));
        $this->auditLogger->log($author, AuditLog::PARTAGE, $document, ['cible_group_id' => $group->id]);

        return $share;
    }

    /** Un lien doit toujours expirer : aucune valeur par défaut infinie n'est acceptée. */
    public function createLink(Document $document, User $author, \DateTimeInterface $expireAt, ?string $password = null): DocumentShare
    {
        if ($expireAt < now()) {
            throw ValidationException::withMessages(['expire_at' => 'La date d\'expiration doit être future.']);
        }

        $share = DocumentShare::query()->create([
            'document_id' => $document->id,
            'cree_par' => $author->id,
            'type' => DocumentShare::TYPE_LIEN,
            'token' => Str::random(48),
            'mot_de_passe_hash' => $password !== null ? Hash::make($password) : null,
            'expire_at' => $expireAt,
        ]);

        $this->auditLogger->log($author, AuditLog::PARTAGE, $document, ['type' => 'lien']);

        return $share;
    }

    public function revoke(DocumentShare $share, User $author): void
    {
        $share->forceFill(['revoque' => true])->save();
        $this->auditLogger->log($author, AuditLog::CHANGEMENT_PERMISSION, $share->document, ['action' => 'revocation_partage']);
    }

    public function resolveLink(string $token, ?string $password): DocumentShare
    {
        $share = DocumentShare::withoutTenantScope()->where('token', $token)->firstOrFail();

        if (! $share->isActive()) {
            throw ValidationException::withMessages(['token' => 'Ce lien de partage a expiré ou a été révoqué.']);
        }

        if ($share->mot_de_passe_hash !== null && (! is_string($password) || ! Hash::check($password, $share->mot_de_passe_hash))) {
            throw ValidationException::withMessages(['password' => 'Mot de passe incorrect.']);
        }

        return $share;
    }
}
