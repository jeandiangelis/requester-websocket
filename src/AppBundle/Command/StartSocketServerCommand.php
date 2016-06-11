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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loop   = Factory::create();
        $urlMsg = new UrlMessenger();

        // Listen for the web server to make a ZeroMQ push after an ajax request
        $context = new Context($loop);
        $pull = $context->getSocket(\ZMQ::SOCKET_PULL);
        $pull->bind('tcp://172.17.0.2:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
        $pull->on('message', [$urlMsg, 'onUrlEntry']);

        // Set up our WebSocket server for clients wanting real-time updates
        $webSock = new Server($loop);
        $webSock->listen(8080, '0.0.0.0'); // Binding to 0.0.0.0 means remotes can connect
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