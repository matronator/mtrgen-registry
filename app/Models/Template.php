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

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'tags_templates')->withTimestamps();
    }

    public static function allPublic($columns = ['*'])
    {
        return static::query()->where('private', '=', false)->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    /**
     * Begin querying the model with only public templates.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryPublic()
    {
        return (new static)->newQuery()->where('private', '=', false);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'filename', 'vendor', 'user_id', 'type', 'private', 'downloads', 'description'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [];
}
