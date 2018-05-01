<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 5/1/18
 * Time: 2:43 PM
 */

namespace Viamage\RealTime\Classes;

use ZMQ;
use ZMQContext;

class Pusher
{
    public static function push(array $data): void
    {
        self::validateData($data);
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'viamage_realtime');
        $socket->connect('tcp://localhost:5555');

        $socket->send(json_encode($data));
    }

    private static function validateData(array $data): void
    {
        $rules = [
            'user_id' => 'required',
            'topic'   => 'required',
        ];
        $v = \Validator::make($data, $rules);
        if ($v->fails()) {
            throw new \ValidationException($v);
        }
    }
}