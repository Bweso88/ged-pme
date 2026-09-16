<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Rôles système partagés par toutes les entreprises (company_id NULL), avec leur jeu de
 * permissions par défaut — voir la matrice ged-pme/docs/04-securite-workflow-archivage.md, §12.
 *
 * Les droits marqués "selon droit explicite" dans la matrice ne sont volontairement pas inclus
 * ici : ils s'accordent au cas par cas via ResourcePermission (dossier/document précis).
 */
class SystemRoleSeeder extends Seeder
{
    private const LABELS = [
        Role::SUPER_ADMIN => 'Super Administrateur',
        Role::ADMIN_ENTREPRISE => 'Administrateur de l\'entreprise',
        Role::RESPONSABLE_DOCUMENTAIRE => 'Responsable documentaire',
        Role::MANAGER => 'Manager',
        Role::EMPLOYE => 'Employé',
        Role::LECTEUR => 'Lecteur / Consultation uniquement',
    ];

    private function matrix(): array
    {
        return [
            Role::ADMIN_ENTREPRISE => array_keys(PermissionSeeder::CATALOG), // toutes les permissions
            Role::RESPONSABLE_DOCUMENTAIRE => [
                'document.view', 'document.download', 'document.create', 'document.edit_metadata',
                'document.new_version', 'document.restore_version', 'document.move', 'document.share',
                'document.submit_workflow', 'document.validate', 'document.archive', 'document.delete',
                'document.restore_trash', 'folder.manage', 'admin.settings', 'admin.audit',
            ],
            Role::MANAGER => [
                'document.view', 'document.download', 'document.create', 'document.edit_metadata',
                'document.new_version', 'document.submit_workflow', 'document.validate',
                'document.delete', 'document.restore_trash',
            ],
            Role::EMPLOYE => [
                'document.view', 'document.download', 'document.create', 'document.edit_metadata',
                'document.new_version', 'document.move', 'document.submit_workflow',
                'document.delete', 'document.restore_trash',
            ],
            Role::LECTEUR => [
                'document.view',
            ],
        ];
    }

    public function run(): void
    {
        // Le Super Administrateur ne détient aucune permission tenant : il gère la plateforme
        // (table companies), jamais le contenu documentaire d'une entreprise cliente.
        Role::query()->firstOrCreate(
            ['company_id' => null, 'nom' => self::LABELS[Role::SUPER_ADMIN]],
            ['code' => Role::SUPER_ADMIN, 'is_system' => true],
        );

        foreach ($this->matrix() as $code => $permissionCodes) {
            $role = Role::query()->firstOrCreate(
                ['company_id' => null, 'nom' => self::LABELS[$code]],
                ['code' => $code, 'is_system' => true],
            );

            $permissionIds = Permission::query()->whereIn('code', $permissionCodes)->pluck('id');
            $role->permissions()->sync($permissionIds);
        }
    }
}
