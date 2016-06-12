<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Url;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

/**
 * Class DefaultController
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $a = $this->getDoctrine()->getRepository(Url::class)->findAll();
        foreach ($a as $b) {
            $this->getDoctrine()->getManager()->remove($b);
        }

        $this->getDoctrine()->getManager()->flush();
        return $this->render('AppBundle::index.html.twig');
    }

    /**
     * @Route("/api/urls", name="urls")
     * @Method(methods={"GET"})
     */
    public function getUrls(Request $request)
    {
        $data = $this
            ->get('doctrine')
            ->getRepository(Url::class)
            ->findAll()
        ;

        $json = $this->get('jms_serializer')->serialize($data, 'json');

        return new Response($json, 200, []);
    }

    /**
     * @Route("/api/urls", name="urls_post")
     * @Method(methods={"POST"})
     */
    public function saveUrls(Request $request)
    {
        $urls = explode("\n", $request->get('urls'));

        $doctrine = $this->getDoctrine();

        /** @var Url $lastBatchUrl */
        $lastBatchUrl = $doctrine
            ->getRepository(Url::class)
            ->getLastBatch()
        ;

        $nextBatch = 1;

        if ($lastBatchUrl) {
            $nextBatch = $lastBatchUrl->getBatch() + 1;
        }

        $entities = [];
        foreach ($urls as $url) {
            $entities[] = $entity = new Url($url, $nextBatch, -1);
            $doctrine->getManager()->persist($entity);
        }

        $doctrine->getManager()->flush();

        foreach ($entities as $entity) {
            $id = $entity->getId();
            $command = "php ../app/console launch:request {$id}";
            $process = new Process($command);
            $process->start();

            while ($process->isRunning()) {}
            echo $process->getOutput();exit;
        }
//        echo $process->getOutput();exit;
        $json = $this->get('jms_serializer')->serialize($entities, 'json');

        return new Response($json, 200, []);
    }
}
