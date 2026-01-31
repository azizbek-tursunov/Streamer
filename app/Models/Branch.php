<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    protected $fillable = ['name'];

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }
}
