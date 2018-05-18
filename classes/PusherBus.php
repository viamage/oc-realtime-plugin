<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 5/1/18
 * Time: 2:26 PM
 */

namespace Viamage\RealTime\Classes;

use Crypt;
use October\Rain\Auth\AuthException;
use October\Rain\Exception\ApplicationException;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Viamage\RealTime\Models\Settings;
use Viamage\RealTime\Models\Token;
use Viamage\RealTime\ValueObjects\UserSessionData;

/**
 * Class PusherBus
 * @package Viamage\RealTime\Classes
 */
class PusherBus implements WampServerInterface
{
    /**
     * @var
     */
    private $settings;

    private $consoleOutput;

    /**
     * PusherBus constructor.
     */
    public function __construct()
    {
        $this->settings = Settings::instance();
        $this->consoleOutput = new ConsoleOutput();
    }

    /**
     * @var array
     */
    protected $subscribedTopics = [];

    /**
     * @var array
     */
    protected $perSessionSubs = [];

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn)
    {
        $cookies = $this->getCookies($conn);
        $userData = $this->decryptSessionUserData($cookies);
        if ($this->settings->get('require_user') && !$userData->userId && !$userData->userKey) {
            $conn->send('Not authorized, go to hell');
            $conn->close();
        }

        if ($userData->userId && $userData->userKey) {
            $conn->userId = $userData->userId;
            $conn->userKey = $userData->userKey;
        }

        $this->messageToConsole('Connection opened by <user>', $conn);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn)
    {
        $this->messageToConsole('Connection closed for <user>', $conn);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception          $e
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        dump('Error!', $e->getMessage());
    }

    /**
     * An RPC call has been received
     * @param \Ratchet\ConnectionInterface $conn
     * @param string                       $id     The unique ID of the RPC, required to respond to
     * @param string|Topic                 $topic  The topic to execute the call against
     * @param array                        $params Call parameters received from the client
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        $this->messageToConsole('Call by <user>', $conn);

        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    /**
     * @param ConnectionInterface $conn
     * @param                     $msg
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $this->messageToConsole('Message by <user>', $conn);

        $conn->send('Not allowed, mate');
        $conn->close();
    }

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to subscribe to
     * @return null
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $wampSessionId = $conn->WAMP->sessionId;
        if (array_key_exists($wampSessionId, $this->perSessionSubs)) {
            if ($this->perSessionSubs[$wampSessionId] > $this->settings->get('subscriptions_limit', 5)) {
                $this->messageToConsole('Max subscriptions reached for <user>', $conn);
                $conn->close();

                return null;
            }
            ++$this->perSessionSubs[$wampSessionId];
        } else {
            $this->perSessionSubs[$wampSessionId] = 1;
        }
        $this->messageToConsole('New subscription by <user> for topic '.$topic->getId(), $conn);

        $this->subscribedTopics[$topic->getId()] = $topic;

        if (isset($conn->userKey) && $conn->userKey && strpos($topic->getId(), $conn->userKey) === false) {
            $this->subscribedTopics[$topic->getId().'_'.$conn->userKey] = $topic;
        }
    }

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to unsubscribe from
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        $this->messageToConsole('<user> unsubscribed from topic'.$topic->getId(), $conn);
    }

    /**
     * A client is attempting to publish content to a subscribed connections on a URI
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic    The topic the user has attempted to publish to
     * @param string                       $event    Payload of the publish
     * @param array                        $exclude  A list of session IDs the message should be excluded from (blacklist)
     * @param array                        $eligible A list of session Ids the message should be send to (whitelist)
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $this->messageToConsole('<user> tried to publish'.$topic->getId(), $conn);
        $conn->close();
    }

    /**
     * @param string $entry
     * @return null|void
     */
    public function onSendUpdate(string $entry): void
    {
        $entryData = json_decode($entry, true);
        $user = $this->getUser($entryData);
        // If the lookup topic object isn't set there is no one to publish to
        if ($user && array_key_exists($entryData['topic'].'_'.$user->realtimeToken->token, $this->subscribedTopics)) {
            $topic = $this->subscribedTopics[$entryData['topic'].'_'.$user->realtimeToken->token];
        } elseif (array_key_exists($entryData['topic'], $this->subscribedTopics)) {
            $topic = $this->subscribedTopics[$entryData['topic']];
        } else {
            return;
        }

        // re-send the data to all the clients subscribed to that category
        $topic->broadcast($entryData);
    }

    /**
     * @param ConnectionInterface $conn
     * @return array
     * @throws ApplicationException
     */
    private function getCookies(ConnectionInterface $conn): array
    {
        // Get the cookies
        $cookiesRaw = $conn->httpRequest->getHeader('Cookie');
        if (array_key_exists(0, $cookiesRaw)) {
            $cookies = [];
            foreach (explode('; ', $cookiesRaw[0]) as $cookieEntry) {
                $array = explode('=', $cookieEntry);
                $cookies[$array[0]] = $array[1];
            }
        } else {
            throw new ApplicationException('Invalid cookies');
        }

        return $cookies;
    }

    /**
     * @param array $entryData
     * @return \Keios\ProUser\Models\User|\RainLab\User\Models\User|null
     */
    private function getUser(array $entryData)
    {
        if (!array_key_exists('user_id', $entryData)) {
            return null;
        }
        $user = null;
        if (class_exists('Keios\ProUser\Models\User')) {
            $user = \Keios\ProUser\Models\User::where('id', $entryData['user_id'])->with('realtimeToken')->first();
        } elseif (class_exists('RainLab\User\Models\User')) {
            $user = RainLab\User\Models\User::where('id', $entryData['user_id'])->with('realtimeToken')->first();
        }

        return $user;
    }

    /**
     * @param array $cookies
     * @return UserSessionData
     * @throws AuthException
     */
    private function decryptSessionUserData(array $cookies): UserSessionData
    {
        $result = new UserSessionData();
        $laravelCookie = urldecode($cookies['viamage_realtime']);
        $tokenData = Crypt::decrypt($laravelCookie);
        $token = Token::where('token', $tokenData)->with('user')->first();
        if ($token) {
            $result->userId = $token->user_id;
            $result->userKey = $token->token;

            return $result;
        }

        throw new AuthException('No valid user found');
    }

    private function messageToConsole(string $message, ConnectionInterface $conn): void
    {
        $userString = 'anonymous';
        if (isset($conn->userId)) {
            $userString = 'user '.$conn->userId;
        }

        $message = str_replace('<user>', $userString, $message);
        $this->consoleOutput->writeln($message);

    }
}