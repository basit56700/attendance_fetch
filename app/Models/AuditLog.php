<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'action',
        'details',
        'ip_address',
        'status',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, ?string $details = null, string $status = 'success', ?int $userId = null, ?string $username = null): self
    {
        $currentUser = auth()->user();

        return self::create([
            'user_id' => $userId ?? $currentUser?->id,
            'username' => $username ?? $currentUser?->name ?? 'System/Guest',
            'action' => strtoupper($action),
            'details' => $details,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'status' => $status,
            'created_at' => now(),
        ]);
    }
}
