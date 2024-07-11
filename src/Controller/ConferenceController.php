<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use App\Entity\Conference;
use App\Entity\Comment;
use App\Repository\ConferenceRepository;
use App\Repository\CommentRepository;

use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;


class ConferenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', [
             'conferences' => $conferenceRepository->findAll(),
        ]);
    }

    /*
     * $conferences added in guestbook/src/EventSubscriber/TwigEventSubscriber.php
     */
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request $request
        , Conference $conference
        , CommentRepository $commentRepository
        , #[Autowire('%photo_dir%')] string $photoDir,
        ): Response
    {

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                $photo->move($photoDir, $filename);
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

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
