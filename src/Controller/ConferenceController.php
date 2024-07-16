<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Conference;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\ConferenceRepository;
use App\Repository\CommentRepository;

use Symfony\Component\Messenger\MessageBusInterface;
use App\Message\CommentMessage;

//use App\SpamChecker;
use Psr\Log\LoggerInterface;


class ConferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', [
             'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

    /*
     * $conferences added in guestbook/src/EventSubscriber/TwigEventSubscriber.php
     */
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request
        , Conference $conference
        , CommentRepository $commentRepository
        //, SpamChecker $spamChecker
        , #[Autowire('%photo_dir%')] string $photoDir,
        ): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $comment->setConference($conference);
            $comment->setStatus('submitted');

            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);

            $context = [
                'user_ip'    => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer'   => $request->headers->get('referer'),
                'permalink'  => $request->getUri(),
            ];

//             $spamScore = $spamChecker->getSpamScore($comment, $context);
// $this->logger->info('XXX - '.__METHOD__.' spamScore='.$spamScore);
//             if (2 === $spamScore) {
//                 throw new \RuntimeException('Blatant spam, go away!');
//             }
//             if (1 === $spamScore) {
//                 $comment->setStatus('spam');
//             }

            $this->entityManager->flush();
            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $commentPaginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            //'conferences' => $conferenceRepository->findAll(),
            'conference'  => $conference,
            //'comments' => $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']),
            'comments' => $commentPaginator,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next'     => min(count($commentPaginator), $offset + CommentRepository::COMMENTS_PER_PAGE),
            'comment_form' => $form,
        ]);
    }
}
