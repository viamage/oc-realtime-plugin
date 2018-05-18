<?php namespace Viamage\RealTime\Components;

use Cms\Classes\ComponentBase;
use Viamage\RealTime\Models\Token;
use Cookie;

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
        $token = $this->getUserToken();
        Cookie::queue('viamage_realtime', $token);
        $this->addJs('assets/js/ab.js');
    }

    public function getUserToken(){
        $user = \Auth::getUser();
        if($user->realtimeToken){
            return $user->realtimeToken->token;
        }
        return $this->buildRealTimeToken($user);
    }

    private function buildRealTimeToken($user)
    {
        $token = hash('SHA512', $user->login . $user->password);
        $model = new Token();
        $model->user_id = $user->id;
        $model->token = $token;
        $model->save();
        return $token;
    }
}
