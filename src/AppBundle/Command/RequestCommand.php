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
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new \RuntimeException('Could not fork the process');
        } elseif ($pid > 0) {
            $output->writeln('New url being processed ' . $pid);
        } else {
            $doctrine = $this
                ->getContainer()
                ->get('doctrine')
            ;

            $serializer = $this
                ->getContainer()
                ->get('jms_serializer')
            ;

            $id     = $input->getArgument(static::URL_ID);
            $repo   = $doctrine->getRepository(Url::class);
            $url    = $repo->find($id);

            $client = new Client();

            $promise = $client->requestAsync('GET', $url->getName(), [
                'progress' => function ($downloadSize, $downloaded, $uploadSize, $uploaded) use ($output, $url) {
                    $output->writeln($url->getName() . ' ---- ' . $downloadSize . ' ++++ ' . $downloaded);
                    $url->setSize($downloaded);
                }
            ]);
            
            $promise->then(
                function (ResponseInterface $response) use ($url, $serializer, $output) {
                    $status     = $response->getStatusCode();
                    $context    = new \ZMQContext();
                    $socket     = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
                    $socket->connect("tcp://172.17.0.2:5555");

                    $url->setStatus($status);
                    $output->writeln('status: ' . $status);
                    $jsonUrl = $serializer->serialize($url, 'json');
                    $socket->send($jsonUrl);
                    $socket->disconnect("tcp://172.17.0.2:5555");
                },
                function (RequestException $exception) use ($url, $serializer, $output) {
                    $status     = $exception->getCode();
                    $context    = new \ZMQContext();
                    $socket     = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
                    $socket->connect("tcp://172.17.0.2:5555");

                    $url->setStatus($status);
                    $socket->send($serializer->serialize($url, 'json'));
                }
            );

            $promise->wait();

            $doctrine->getManager()->persist($url);
            $doctrine->getManager()->flush();
        }
    }
}