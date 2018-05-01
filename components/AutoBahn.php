<?php namespace Viamage\RealTime\Components;

use Cms\Classes\ComponentBase;

class AutoBahn extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'AutoBahn Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    public function onRun()
    {
        $this->addJs('assets/js/ab.js');
    }
}
