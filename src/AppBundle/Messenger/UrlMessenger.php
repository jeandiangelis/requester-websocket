<?php

namespace AppBundle\Messenger;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;

/**
 * Class UrlMessenger
 */
class UrlMessenger implements WampServerInterface
{
    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = [];

    public function onOpen(ConnectionInterface $conn)
    {

    }

    public function onClose(ConnectionInterface $conn)
    {

    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {

    }

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic to subscribe to
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $this->subscribedTopics[$topic->getId()] = $topic;
    }

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic to unsubscribe from
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        // TODO: Implement onUnSubscribe() method.
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onUrlEntry($entry)
    {
        $entryData = json_decode($entry, true);

        if (!array_key_exists($entryData['id'], $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$entryData['id']];

        $topic->broadcast($entryData);
    }
}
