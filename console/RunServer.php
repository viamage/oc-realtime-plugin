<?php
/**
 * Created by PhpStorm.
 * User: Łukasz Biały
 * URL: http://keios.eu
 * Date: 8/13/15
 * Time: 2:17 AM
 */

namespace Viamage\RealTime\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Keios\ProUser\Models\Country;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\SecureServer;
use React\Socket\Server;
use React\ZMQ\Context;
use Viamage\CallbackManager\Models\Rate;
use Viamage\RealTime\Classes\Bus;
use Viamage\RealTime\Classes\PusherBus;
use Viamage\RealTime\Models\Settings;
use ZMQ;

/**
 * Class Optimize
 * @package Keios\Apparatus\Console
 */
class RunServer extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'realtime:run-server';

    /**
     * The console command description.
     */
    protected $description = 'Runs WebSocket Server';

    public function handle()
    {
        $settings = Settings::instance();
        $serverIp = $settings->get('websockets_server_ip', '0.0.0.0');
        $serverPort = $settings->get('websockets_server_port', '6010');

        $loop = Factory::create();
        $bus = new PusherBus;
        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new Context($loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
        $pull->on('message', [$bus, 'onSendUpdate']);

        // Set up our WebSocket server for clients wanting real-time updates
        $serverUri = $serverIp.':'.$serverPort;
        $webSock = new Server($serverUri, $loop); // Binding to 0.0.0.0 means remotes can connect
        $wsServer = new WsServer(
            new WampServer(
                $bus
            )
        );
        $wsServer->enableKeepAlive($loop, 30);
        $server = new IoServer(
            new HttpServer(
                $wsServer
            ),
            $webSock
        );

        dump('Running at ' . $webSock->getAddress());
        $loop->run();
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [];
    }
}