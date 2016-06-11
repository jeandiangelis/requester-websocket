<?php

namespace AppBundle\Command;

use AppBundle\Messenger\UrlMessenger;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
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
        $port = $input->getArgument('port');

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new UrlMessenger()
                )
            ),
            $port ?: '8080'
        );

//        $output->writeln('Socket server started successfully');
        $server->run();
    }
}