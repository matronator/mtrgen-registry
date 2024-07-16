<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    public function account()
    {
        return $this->belongsTo(Account::class)->withDefault();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'account_id', 'address', 'network', 'contract'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
