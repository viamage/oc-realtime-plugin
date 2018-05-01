<?php namespace Viamage\RealTime\Models;

use Lang;
use Model;
use System\Models\MailTemplate;

/**
 * Class Settings
 *
 * @package Viamage\CallbackManager\Models
 */
class Settings extends Model
{
    public $settingsFields = 'fields.yaml';
    /**
     * @var array
     */
    public $implement = ['System.Behaviors.SettingsModel'];

    /**
     * @var string
     */
    public $settingsCode = 'viamage_realtime';
}
