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
    const LAST_BATCH = 'last_batch';

    protected function configure()
    {
        $this
            ->setName('launch:request')
            ->setDescription('Request launcher')
            ->addArgument(
                static::LAST_BATCH,
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this
            ->getContainer()
            ->get('doctrine')
        ;
//
        $batch     = $input->getArgument(static::LAST_BATCH);
        $em     = $doctrine->getEntityManager();
        $repo   = $doctrine->getRepository(Url::class);
        $urls   = $repo->findBy(['batch' => $batch]);

        $context = new \ZMQContext();
        $socket = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
        $socket->connect("tcp://172.17.0.2:5555");
        $socket->send($this->getContainer()->get('jms_serializer')->serialize($urls, 'json'));
//        $client = new Client();
//
//        $promise = $client->requestAsync(Request::METHOD_GET, $url->getName(), [
//        ]);
//
//        $promise->then(
//            function (ResponseInterface $response) use ($url, $em) {
//                $url->setStatus($response->getStatusCode());
//                echo $response->getBody();
//                $em->persist($url);
//                $em->flush();
//            },
//            function (RequestException $exception) use ($url, $em) {
//                $url->setStatus($exception->getCode());
//                echo $exception->getMessage();
//                $em->persist($url);
//                $em->flush();
//            }
//        );
    }
}