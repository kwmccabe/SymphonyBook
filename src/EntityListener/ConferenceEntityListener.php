<?php

namespace App\EntityListener;

use App\Entity\Conference;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

//use Psr\Log\LoggerInterface;


#[AsEntityListener(event: Events::prePersist, entity: Conference::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Conference::class)]
class ConferenceEntityListener
{

    public function __construct(
        private SluggerInterface $slugger,
//        private LoggerInterface $logger,
    ) {
//$this->logger->info('XXX - '.__METHOD__);
    }

    public function prePersist(Conference $conference, LifecycleEventArgs $event)
    {
//$this->logger->info('XXX - '.__METHOD__);
        $conference->computeSlug($this->slugger);
    }

    public function preUpdate(Conference $conference, LifecycleEventArgs $event)
    {
//$this->logger->info('XXX - '.__METHOD__);
        $conference->computeSlug($this->slugger);
    }
}
