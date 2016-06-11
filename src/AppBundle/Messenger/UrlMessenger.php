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
    protected $urls = [];

    public function onOpen(ConnectionInterface $conn)
    {

    }

    public function onClose(ConnectionInterface $conn)
    {

    }

    public function onCall(ConnectionInterface $conn, $id, $url, array $params)
    {

    }

    public function onPublish(ConnectionInterface $conn, $url, $event, array $exclude, array $eligible)
    {
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {

    }

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $url The topic to subscribe to
     */
    public function onSubscribe(ConnectionInterface $conn, $url)
    {
        $this->urls[$url->getId()] = $url;
    }

    /**
     * A request to unsubscribe from a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $url The topic to unsubscribe from
     */
    public function onUnSubscribe(ConnectionInterface $conn, $url)
    {

    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onUrlEntry($entry)
    {
        $entryData = json_decode($entry, true);

        if (!array_key_exists($entryData['id'], $this->urls)) {
            return;
        }

        $url = $this->urls[$entryData['id']];

        $url->broadcast($entryData);
    }
}
