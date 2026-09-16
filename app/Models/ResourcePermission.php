<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResourcePermission extends Model
{
    use BelongsToCompany, HasUuids;

    public const TYPE_FOLDER = 'folder';

    public const TYPE_DOCUMENT = 'document';

    protected $fillable = [
        'company_id', 'user_id', 'user_group_id', 'resource_type', 'resource_id', 'permission_code', 'granted',
    ];

    protected function casts(): array
    {
        return ['granted' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
