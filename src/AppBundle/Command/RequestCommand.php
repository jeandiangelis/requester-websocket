<?php

namespace AppBundle\Command;

use AppBundle\Entity\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RequestCommand
 */
class RequestCommand extends ContainerAwareCommand
{
    const URL_ID = 'id';

    protected function configure()
    {
        $this
            ->setName('launch:request')
            ->setDescription('Request launcher')
            ->addArgument(
                static::URL_ID,
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this
            ->getContainer()
            ->get('doctrine')
        ;

        $id         = $input->getArgument(static::URL_ID);
        $em         = $doctrine->getManager();
        $repo       = $doctrine->getRepository(Url::class);
        $url        = $repo->find($id);

        $client     = new Client();

        $promise = $client->requestAsync(Request::METHOD_GET, $url->getName(), []);
        $promise->then(
            function (ResponseInterface $response) use ($url, $em, $output) {
                $url->setStatus($response->getStatusCode());
                $em->persist($url);
                $em->flush();

                $context    = new \ZMQContext();
                $socket     = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
                $socket->connect("tcp://172.17.0.2:5555");
                $socket->send($this->getContainer()->get('jms_serializer')->serialize($url, 'json'));
                $socket->disconnect("tcp://172.17.0.2:5555");
            },
            function (RequestException $exception) use ($url, $em) {
                $url->setStatus($exception->getCode());
                $em->persist($url);
                $em->flush();

                $context    = new \ZMQContext();
                $socket     = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
                $socket->connect("tcp://172.17.0.2:5555");
                $socket->send($this->getContainer()->get('jms_serializer')->serialize($url, 'json'));
                $socket->disconnect("tcp://172.17.0.2:5555");
            }
        );
    }
}