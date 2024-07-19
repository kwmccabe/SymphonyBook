<?php
/*
 * https://symfony.com/doc/current/testing.html#testing-application-assertions
 */

namespace App\Tests\Controller;

use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConferenceControllerTest extends WebTestCase
{

//     public function testIndex()
//     {
//         $client = static::createClient();
//         $client->request('GET', '/');
//
//         //$response = $client->getResponse();
//         //echo __METHOD__.' : response : ' .print_r($response,true);
//
//         $this->assertResponseIsSuccessful();
//         $this->assertSelectorTextContains('h2', 'your feedback');
//     }

    public function testConferencePage()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/');

//         $response = $client->getResponse();
//         echo __METHOD__.' : response : ' .print_r($response,true);

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testCommentSubmission()
    {
        $email = 'me@automat.ed';

        $client = static::createClient();
        $client->request('GET', '/en/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment[author]' => 'Fabien',
            'comment[text]' => 'Some feedback from an automated functional test',
            'comment[email]' => $email,
            'comment[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);
        $this->assertResponseRedirects();

        // simulate comment validation
        //$comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment = static::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setStatus('published');
        //self::getContainer()->get(EntityManagerInterface::class)->flush();
        static::getContainer()->get(EntityManagerInterface::class)->flush();

// // (1) boot the Symfony kernel
// self::bootKernel();
// // (2) use static::getContainer() to access the service container
// $container = static::getContainer();
// // (3) run some service & test the result
// $newsletterGenerator = $container->get(NewsletterGenerator::class);
// $newsletter = $newsletterGenerator->generateMonthlyNews(/* ... */);

        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 3 comments")');


    }

/*
    public function testMailerAssertions()
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $this->assertEmailCount(1);
        $event = $this->getMailerEvent(0);
        $this->assertEmailIsQueued($event);

        $email = $this->getMailerMessage(0);
        $this->assertEmailHeaderSame($email, 'To', 'fabien@example.com');
        $this->assertEmailTextBodyContains($email, 'Bar');
        $this->assertEmailAttachmentCount($email, 1);
    }
*/

}  // end class ConferenceControllerTest

