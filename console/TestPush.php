<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 5/1/18
 * Time: 2:28 PM
 */

namespace Viamage\RealTime\Console;

use Illuminate\Console\Command;
use Viamage\RealTime\Classes\Pusher;
use Viamage\RealTime\Models\Settings;
use ZMQ;
use ZMQContext;

/**
 * Class TestPush
 * @package Viamage\RealTime\Console
 */
class TestPush extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'realtime:test';

    /**
     * The console command description.
     */
    protected $description = 'Sends test push';

    /**
     *
     * @throws \ZMQSocketException
     */
    public function handle()
    {
        Pusher::push(
            [
                'topic'   => 'test_channel',
                'details' => ['message' => 'OK', 'details' => 'Works!']
            ]
        );
        $this->info('Pushed example payload to test_channel');
    }

    /**
     * @return string
     */
    protected function getServerUri(): string
    {
        $settings = Settings::instance();
        $serverIp = $settings->get('websockets_server_ip', '0.0.0.0');
        $serverPort = $settings->get('websockets_server_port', '6010');

        return $serverIp.':'.$serverPort;
    }

}