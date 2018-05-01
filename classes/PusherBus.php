<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 5/1/18
 * Time: 2:26 PM
 */

namespace Viamage\RealTime\Classes;

use Crypt;
use October\Rain\Exception\ApplicationException;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Viamage\RealTime\ValueObjects\UserSessionData;

/**
 * Class PusherBus
 * @package Viamage\RealTime\Classes
 */
class PusherBus implements WampServerInterface
{

    /**
     * @var array
     */
    protected $subscribedTopics = [];

    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        $cookies = $this->getCookies($conn);
        $userData = $this->decryptSessionUserData($cookies);
        $conn->userId = $userData->userId;
        $conn->userKey = $userData->userKey;

        dump('Connection opened by user '.$conn->userId);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        dump('Connection closed by user '.$conn->userId);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception          $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
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
    function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        dump('Call by user '.$conn->userId);
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to subscribe to
     */
    function onSubscribe(ConnectionInterface $conn, $topic)
    {
        dump('New subscription by user '.$conn->userId.' for topic '.$topic->getId());
        $this->subscribedTopics[$topic->getId()] = $topic;
        if(property_exists($conn, 'userKey') && $conn->userKey){
            $this->subscribedTopics[$topic->getId().'_'.$conn->userKey] = $topic;
        }
    }

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to unsubscribe from
     */
    function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        dump('User '.$conn->userId.' unsubscribed from '.$topic->getId());
    }

    /**
     * A client is attempting to publish content to a subscribed connections on a URI
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic    The topic the user has attempted to publish to
     * @param string                       $event    Payload of the publish
     * @param array                        $exclude  A list of session IDs the message should be excluded from (blacklist)
     * @param array                        $eligible A list of session Ids the message should be send to (whitelist)
     */
    function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        dump('User '.$conn->userId.' tried to publish');
        $conn->close();
    }

    /**
     * @param string $entry
     * @return null|void
     */
    public function onSendUpdate($entry)
    {
        $entryData = json_decode($entry, true);
        $user = $this->getUser($entryData);
        // If the lookup topic object isn't set there is no one to publish to
        if ($user && array_key_exists($entryData['topic'].'_'.$user->persist_code, $this->subscribedTopics)) {
            $topic = $this->subscribedTopics[$entryData['topic'].'_'.$user->persist_code];
        } elseif (array_key_exists($entryData['topic'], $this->subscribedTopics)) {
            $topic = $this->subscribedTopics[$entryData['topic']];
        } else {
            return null;
        }

        // re-send the data to all the clients subscribed to that category
        $topic->broadcast($entryData);
    }

    /**
     * @param ConnectionInterface $conn
     * @return array
     * @throws ApplicationException
     */
    private function getCookies(ConnectionInterface $conn)
    {
        // Get the cookies
        $cookiesRaw = $conn->httpRequest->getHeader('Cookie');
        if (array_key_exists(0, $cookiesRaw)) {
            $cookies = [];
            $cookieEntries = explode('; ', $cookiesRaw[0]);
            foreach ($cookieEntries as $cookieEntry) {
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
    private function getUser($entryData)
    {
        if (!array_key_exists('user_id', $entryData)) {
            return null;
        }
        $user = null;
        if (class_exists('Keios\ProUser\Models\User')) {
            $user = \Keios\ProUser\Models\User::where('id', $entryData['user_id'])->first();
        } elseif (class_exists('RainLab\User\Models\User')) {
            $user = RainLab\User\Models\User::where('id', $entryData['user_id'])->first();
        }

        return $user;
    }

    /**
     * @param array $cookies
     * @return UserSessionData
     */
    private function decryptSessionUserData($cookies)
    {
        $result = new UserSessionData();
        $laravelCookie = urldecode($cookies['user_auth']);
        $userData = Crypt::decrypt($laravelCookie);
        if (\is_array($userData)) {
            if (array_key_exists(0, $userData)) {
                $result->userId = $userData[0];
            }
            if (array_key_exists(1, $userData)) {
                $result->userKey = $userData[1];
            }
        }

        return $result;
    }
}