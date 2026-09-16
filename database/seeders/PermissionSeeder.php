<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Catalogue global de permissions atomiques (voir doc ged-pme/docs/04, §12).
 */
class PermissionSeeder extends Seeder
{
    public const CATALOG = [
        'document.view' => 'Voir un document',
        'document.download' => 'Télécharger un document',
        'document.create' => 'Créer / uploader un document',
        'document.edit_metadata' => 'Modifier les métadonnées / renommer',
        'document.new_version' => 'Créer une nouvelle version',
        'document.restore_version' => 'Restaurer une ancienne version',
        'document.move' => 'Déplacer un document',
        'document.share' => 'Partager un document',
        'document.submit_workflow' => 'Soumettre un document au workflow',
        'document.validate' => 'Valider une étape de workflow (approuver/rejeter)',
        'document.archive' => 'Archiver un document',
        'document.delete' => 'Supprimer (mettre à la corbeille)',
        'document.restore_trash' => 'Restaurer depuis la corbeille',
        'document.delete_permanent' => 'Suppression définitive',
        'folder.manage' => 'Gérer la structure documentaire (dossiers)',
        'admin.users' => 'Gérer les utilisateurs, rôles et groupes',
        'admin.settings' => 'Configurer workflows, rétention et paramètres',
        'admin.audit' => 'Consulter le journal d\'audit',
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $code => $label) {
            Permission::query()->firstOrCreate(['code' => $code], ['label' => $label]);
        }
    }
}
