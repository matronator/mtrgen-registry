<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory;

    public const TYPE_TEMPLATE = 'template';
    public const TYPE_BUNDLE = 'bundle';
    public const TYPES = [
        self::TYPE_TEMPLATE,
        self::TYPE_BUNDLE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'filename', 'vendor', 'user_id', 'type', 'downloads'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
