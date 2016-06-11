<?php

namespace AppBundle\Command;

use AppBundle\Messenger\UrlMessenger;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use React\ZMQ\Context;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class StartSocketServerCommand
 */
class StartSocketServerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('socketserver:start')
            ->setDescription('Starts the websocket server')
            ->addArgument(
                'port',
                InputArgument::OPTIONAL,
                'set the port of your websocket server'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \React\Socket\ConnectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loop   = Factory::create();
        $urlMsg = new UrlMessenger();

        $context = new Context($loop);
        $pull = $context->getSocket(\ZMQ::SOCKET_PULL);
        $pull->bind('tcp://172.17.0.2:5555');
        $pull->on('message', [$urlMsg, 'onUrlEntry']);

        $webSock = new Server($loop);
        $webSock->listen(8080, '0.0.0.0');
        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WampServer(
                        $urlMsg
                    )
                )
            ),
            $webSock
        );

        $loop->run();
    }
}