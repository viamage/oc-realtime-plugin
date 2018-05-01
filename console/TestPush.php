<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 5/1/18
 * Time: 2:28 PM
 */

namespace Viamage\RealTime\Console;

use Illuminate\Console\Command;
use ZMQ;
use ZMQContext;

class TestPush extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'realtime:test';

    /**
     * The console command description.
     */
    protected $description = 'Runs WebSocket Server';

    public function handle()
    {
        $data = [
            'state'   => 'finished',
            'topic'   => 'callbacks_$2y$10$BBtVrd6bghgt81XhZclgYuHXtjO2KgtltKG78pCMwr/9NxgLtwme.',
        ];
        $context = new ZMQContext();
        $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'viamage_realtime');
        $socket->connect('tcp://localhost:5555');

        $socket->send(json_encode($data));
    }

}