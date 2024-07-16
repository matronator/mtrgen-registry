<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Account extends Model implements AuthorizableContract
{
    use Authorizable, HasFactory;

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'id_address', 'stx_address', 'stx_testnet_address', 'btc_address', 'registered_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
