<?php namespace Viamage\RealTime\Models;

use Model;

/**
 * Token Model
 */
class Token extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'viamage_realtime_tokens';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];
}
