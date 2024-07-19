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

use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;

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


    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage', ['_locale' => 'en']);
    }

    #[Route(
        path: '/{_locale}/',
        name: 'homepage',
        requirements: [
            '_locale' => '%app.supported_locales%',
        ],
    )]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', [
             'conferences' => $conferenceRepository->findAll(),
        ]);
        //])->setSharedMaxAge(600);
    }

    #[Route('/{_locale<%app.supported_locales%>}/conference_header', name: 'conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
        //])->setSharedMaxAge(600);
    }

    #[Route('/{_locale<%app.supported_locales%>}/conference/{slug}', name: 'conference')]
    public function show(
        Request $request
        , Conference $conference
        , CommentRepository $commentRepository
        //, SpamChecker $spamChecker
        , NotifierInterface $notifier
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

            $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }
        if ($form->isSubmitted()) {
            $notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $commentPaginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            'conference'  => $conference,
            //'comments' => $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']),
            'comments' => $commentPaginator,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next'     => min(count($commentPaginator), $offset + CommentRepository::COMMENTS_PER_PAGE),
            'comment_form' => $form,
        ]);
    }
}
