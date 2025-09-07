<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderSyncRun extends Model
{
    protected $fillable = [
        'started_at','finished_at','status','message','new_orders','updated_orders'
    ];

    protected $casts = [
        'started_at'=>'datetime','finished_at'=>'datetime','new_orders'=>'int','updated_orders'=>'int'
    ];
}
