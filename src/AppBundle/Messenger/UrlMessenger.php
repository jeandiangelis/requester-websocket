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
    protected $subscribedTopics;

    /**
     * UrlMessenger constructor.
     */
    public function __construct()
    {
        $this->subscribedTopics = new \SplStack();
    }


    public function onOpen(ConnectionInterface $conn)
    {

    }

    public function onClose(ConnectionInterface $conn)
    {

    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {

    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {

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
        if ($this->subscribedTopics->isEmpty()) {
            $this->subscribedTopics->push($topic);
        }
    }

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic to unsubscribe from
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {

    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onTopicEntry($entry)
    {
        $entryData = json_decode($entry, true);
        $topic = $this->subscribedTopics->pop();
        $topic->broadcast($entryData);
    }
}
