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
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\ExtEventLoop;
use React\EventLoop\Factory;
use React\EventLoop\LibEventLoop;
use React\EventLoop\LibEvLoop;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Socket\SecureServer;
use React\Socket\Server;
use React\ZMQ\Context;
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

    /**
     * @var Server
     */
    protected $webSock;

    /**
     * @var WsServer
     */
    protected $wsServer;

    /**
     * @var LibEventLoop|LibEvLoop|ExtEventLoop|StreamSelectLoop
     */
    protected $loop;

    /**
     * @var IoServer
     */
    protected $server;

    /**
     * @var PusherBus
     */
    protected $bus;

    /**
     *
     */
    public function handle()
    {
        $serverUri = $this->getServerUri();
        $this->line('Preparing server...');
        $this->buildWsServer($serverUri);
        $this->server = new IoServer(
            new HttpServer(
                $this->wsServer
            ),
            $this->webSock
        );
        $this->info('Running at '.$this->webSock->getAddress());
        $this->loop->run();
    }

    /**
     * @param string $serverUri
     */
    protected function buildWsServer(string $serverUri): void
    {
        $this->buildBus();
        $this->loop = Factory::create();
        $context = new Context($this->loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind('tcp://127.0.0.1:5555');
        $pull->on('message', [$this->bus, 'onSendUpdate']);

        $this->webSock = new Server($serverUri, $this->loop);
        $this->wsServer = new WsServer(
            new WampServer(
                $this->bus
            )
        );
        $this->wsServer->enableKeepAlive($this->loop, 30);
    }

    /**
     * Builds BUS.
     * You can extend this command in your own and override this method only to provide custom Bus.
     */
    protected function buildBus(): void
    {
        $this->bus = new PusherBus;
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