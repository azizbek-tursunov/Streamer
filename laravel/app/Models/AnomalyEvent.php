<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'anomaly_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
    ];

    public function anomaly(): BelongsTo
    {
        return $this->belongsTo(Anomaly::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
