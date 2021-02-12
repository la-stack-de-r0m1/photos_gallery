<?php

/**
 * This file is part of the photos_gallery project
 * 
 * Author: Romain Bertholon <romain.bertholon@gmail.com>
 */

namespace App\Repository;

use App\Entity\Picture;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Picture|null find($id, $lockMode = null, $lockVersion = null)
 * @method Picture|null findOneBy(array $criteria, array $orderBy = null)
 * @method Picture[]    findAll()
 * @method Picture[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PictureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Picture::class);
    }

    /**
     * Get the previous picture.
     * 
     * @param $pictureId the current picture Id
     * 
     * @return Picture the picture preceeding the picture with pictureId, or null.
     */
    public function getPreviousPicture($pictureId) : Picture
    {
        return $this->createQueryBuilder('u')
                ->select('u')
                ->where('u.id < :pictureId')
                ->setParameter(':pictureId', $pictureId)
                ->orderBy('u.id', 'DESC')
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;
    }

    /**
     * Get the next picture.
     * 
     * @param $pictureId the current picture Id
     * 
     * @return Picture the picture following the picture with pictureId, or null.
     */
    public function getNextPicture($pictureId) : Picture
    {
        return $this->createQueryBuilder('u')
                ->select('u')
                ->where('u.id > :pictureId')
                ->setParameter(':pictureId', $pictureId)
                ->orderBy('u.id', 'ASC')
                ->setFirstResult(0)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
        ;
    }
}
