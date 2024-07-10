<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Conference;
use App\Repository\ConferenceRepository;
use App\Repository\CommentRepository;


class ConferenceController extends AbstractController
{

    #[Route('/', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return $this->render('conference/index.html.twig', [
             'conferences' => $conferenceRepository->findAll(),
        ]);
    }

    /*
     * $conferences add in guestbook/src/EventSubscriber/TwigEventSubscriber.php
     */
    #[Route('/conference/{id}', name: 'conference')]
    public function show(
        Request $request
        , Conference $conference
        , CommentRepository $commentRepository
        //, ConferenceRepository $conferenceRepository
        ): Response
    {
        $offset = max(0, $request->query->getInt('offset', 0));
        $commentPaginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            //'conferences' => $conferenceRepository->findAll(),
            'conference'  => $conference,
            //'comments' => $commentRepository->findBy(['conference' => $conference], ['createdAt' => 'DESC']),
            'comments' => $commentPaginator,
            'previous' => $offset - CommentRepository::COMMENTS_PER_PAGE,
            'next'     => min(count($commentPaginator), $offset + CommentRepository::COMMENTS_PER_PAGE),
        ]);
    }
}
