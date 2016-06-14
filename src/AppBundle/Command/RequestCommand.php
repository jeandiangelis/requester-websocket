<?php

namespace AppBundle\Command;

use AppBundle\Entity\Url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use JMS\Serializer\Serializer;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
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

    /**
     * @var Serializer
     */
    private $serializer;

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
        $this->serializer = $this
            ->getContainer()
            ->get('jms_serializer')
        ;

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

            $id     = $input->getArgument(static::URL_ID);
            $repo   = $doctrine->getRepository(Url::class);
            $url    = $repo->find($id);

            $client = new Client();

            $promise = $client->requestAsync('GET', $url->getName(), [
                'progress' => function ($downloadSize, $downloaded, $uploadSize, $uploaded) use ($output, $url) {
                    $url->setSize($downloaded);
                },
                'allow_redirects' => [
                    'on_redirect' => function (
                        RequestInterface $request,
                        ResponseInterface $response,
                        UriInterface $uri) use ($url, $output) {
                        $url->setStatus($response->getStatusCode());
                        $this->sendMessage($url);

                    }
                ]
            ]);

            $promise->then(
                function (ResponseInterface $response) use ($url, $output) {
                    $url->setStatus($response->getStatusCode());
                    $this->sendMessage($url);
                },
                function (RequestException $exception) use ($url) {
                    $status = $exception->getCode();
                    $url->setStatus($status);
                    $this->sendMessage($url);
                }
            );

            $promise->wait();

            $doctrine->getManager()->persist($url);
            $doctrine->getManager()->flush();
        }
    }

    /**
     * @param $message
     */
    private function sendMessage(Url $message)
    {
        $json = $this->serializer->serialize($message, 'json');

        $context    = new \ZMQContext();
        $socket     = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');

        $socket->connect("tcp://172.17.0.2:5555");
        $socket->send($json);
        $socket->disconnect("tcp://172.17.0.2:5555");
    }
}