<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConferenceController extends AbstractController
{

    #[Route('/', name: 'homepage')]
    public function index( Request $request ): Response
    {
//         return $this->render('conference/index.html.twig', [
//             'controller_name' => 'ConferenceController',
//         ]);

        dump($request);  // to console

        $greet = '';
        if ($name = $request->query->get('hello')) {
            $greet = sprintf('<h1>Hello %s!</h1>', htmlspecialchars($name));
        }

        $rv = <<<EOF
<html>
<body>
    <p>$greet</p>
    <p><img src="/images/under-construction.gif" /></p>
</body>
</html>
EOF;
        return new Response( $rv );
    }
}
