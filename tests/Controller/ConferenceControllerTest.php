<?php
/*
 * https://symfony.com/doc/current/testing.html#testing-application-assertions
 */

namespace App\Tests\Controller;

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
        $crawler = $client->request('GET', '/');

        $response = $client->getResponse();
        echo __METHOD__.' : response : ' .print_r($response,true);

        $this->assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 1 comments")');
    }

    public function testCommentSubmission()
    {
        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2019');
        $client->submitForm('Submit', [
            'comment[author]' => 'Fabien',
            'comment[text]' => 'Some feedback from an automated functional test',
            'comment[email]' => 'me@automat.ed',
            'comment[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

}
