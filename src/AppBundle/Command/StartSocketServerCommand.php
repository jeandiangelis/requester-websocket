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
                'ip',
                InputArgument::REQUIRED,
                'set the ip of zeromq'
            )->addArgument(
                'port',
                InputArgument::OPTIONAL,
                'set the port of zeromq'
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
        $ip = $input->getArgument('ip');
        $port = $input->getArgument('port');

        $loop   = Factory::create();
        $urlMsg = new UrlMessenger($this->getContainer());

        $context = new Context($loop);
        $pull = $context->getSocket(\ZMQ::SOCKET_PULL);
        var_dump('tcp://' . $ip . ':' . $port);
        $pull->bind('tcp://' . $ip . ':' . $port);
        $pull->on('message', [$urlMsg, 'onTopicEntry']);

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
        $output->writeln('Websocket server started. Listening on port 8080');
        $loop->run();
    }
}