<?php

namespace AppBundle\Messenger;

use AppBundle\Entity\Url;
use Doctrine\ORM\EntityManager;
use Guzzle\Http\Exception\RequestException;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

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
     * @var Container
     */
    protected $container;

    /**
     * UrlMessenger constructor.
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;
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
        var_dump($e);
    }

    /**
     * A request to subscribe to a topic has been made
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic $topic The topic to subscribe to
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $this->subscribedTopics->push($topic);
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
        $entryData = json_decode($entry);

        if ($this->subscribedTopics->isEmpty()) {
            return;
        }
        
        $topic = $this->subscribedTopics->pop();
        $topic->broadcast($entryData);
    }
}
