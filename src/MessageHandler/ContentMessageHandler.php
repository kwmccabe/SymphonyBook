<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;

// Autowire doesn't work here?  Set in services.yaml
//use Symfony\Component\Messenger\Attribute\AsMessageHandler;  // use #[AsMessageHandler]


class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
    ) {
    }

    public function __invoke(CommentMessage $message) : void
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        $spamScore = $this->spamChecker->getSpamScore($comment, $message->getContext());

        if (2 === $spamScore) {
            $comment->setStatus('spam');
        } else {
            $comment->setStatus('published');
        }

        $this->entityManager->flush();
    }
}
