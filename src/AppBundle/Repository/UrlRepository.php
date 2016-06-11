<?php

namespace AppBundle\Repository;

/**
 * UrlRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UrlRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @return array
     */
    public function getLastBatch()
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT u FROM AppBundle:Url u ORDER BY u.batch')
            ->getFirstResult()
        ;
    }
}
