<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use App\ImageOptimizer;
use App\Notification\CommentReviewNotification;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;

use Psr\Log\LoggerInterface;

// Autowire doesn't work here?  Set in services.yaml
//use Symfony\Component\Messenger\Attribute\AsMessageHandler;  // use #[AsMessageHandler]


class CommentMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SpamChecker $spamChecker,
        private CommentRepository $commentRepository,
        private MessageBusInterface $bus,
        private WorkflowInterface $commentStateMachine,
        private NotifierInterface $notifier,
        private ImageOptimizer $imageOptimizer,
        #[Autowire('%photo_dir%')] private string $photoDir,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function __invoke(CommentMessage $message) : void
    {
$this->logger->debug('XXX'.__METHOD__);

        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

//         $spamScore = $this->spamChecker->getSpamScore($comment, $message->getContext());
//         if (2 === $spamScore) {
//             $comment->setStatus('spam');
//         } else {
//             $comment->setStatus('published');
//         }

        if ($this->commentStateMachine->can($comment, 'accept'))
        {
            $spamScore = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = match ($spamScore) {
                2 => 'reject_spam',
                1 => 'might_be_spam',
                default => 'accept',
            };
            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);

        } elseif ($this->commentStateMachine->can($comment, 'publish')
                || $this->commentStateMachine->can($comment, 'publish_ham')
                )
        {
//             $this->commentStateMachine->apply($comment, $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham');
//             $this->entityManager->flush();

//             $this->mailer->send((new NotificationEmail())
//                 ->subject('New comment posted')
//                 ->htmlTemplate('emails/comment_notification.html.twig')
//                 ->from($this->adminEmail)
//                 ->to($this->adminEmail)
//                 ->context(['comment' => $comment])
//             );

            $this->notifier->send(new CommentReviewNotification($comment), ...$this->notifier->getAdminRecipients());

            //$notification = new CommentReviewNotification($comment, $message->getReviewUrl());
            //$this->notifier->send($notification, ...$this->notifier->getAdminRecipients());


        } elseif ($this->commentStateMachine->can($comment, 'optimize'))
        {
            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir.'/'.$comment->getPhotoFilename());
            }
            $this->commentStateMachine->apply($comment, 'optimize');
            $this->entityManager->flush();

            // notify comment.email ???

        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', ['comment' => $comment->getId(), 'status' => $comment->getStatus()]);
        }

//$this->logger->debug('XXX', ['comment' => $comment->getId(), 'status' => $comment->getStatus()]);

        $this->entityManager->flush();
    }

}  // end class CommentMessageHandler
