<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSyncSetting extends Model
{
    protected $fillable = [
        'source_url','username','password_encrypted','interval_minutes','enabled'
    ];

    protected $casts = [
        'enabled'=>'boolean','interval_minutes'=>'int'
    ];
}
