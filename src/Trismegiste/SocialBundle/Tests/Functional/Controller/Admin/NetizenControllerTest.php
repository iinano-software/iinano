<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Tests\Functional\Controller\Admin;

use Symfony\Component\DomCrawler\Link;

/**
 * NetizenControllerTest tests NetizenController
 */
class NetizenControllerTest extends AdminControllerTestCase
{

    /**
     * @test
     */
    public function initialize()
    {
        parent::initialize();
    }

    /**
     * @depends initialize
     */
    public function testAccessNetizenListing()
    {
        $this->assertSecuredPage('admin_netizen_listing');
        $crawler = $this->client->getCrawler();
        $this->assertCount(1, $crawler->filter('div.content tr th:contains("Nickname")'));
        $this->assertCount(0, $crawler->filter('div.content table tr td')); // no research criteria => no listing

        $form = $crawler->selectButton('Search')->form();
        $crawler = $this->client->submit($form, ['social_netizen_filter' =>
            ['nickname' => 'si']
        ]);

        $showLink = $crawler->filter('div.content table td a:contains("simple")');
        $this->assertCount(1, $showLink);

        return $showLink->first()->link();
    }

    /**
     * @depends testAccessNetizenListing
     */
    public function testNetizenShow(Link $showUrl)
    {
        $this->client->followRedirects();
        $this->logIn('admin');

        $crawler = $this->client->click($showUrl);
        $this->assertCount(1, $crawler->filter('div.content:contains("simple")'));
        $this->assertCount(1, $crawler->filter('div.content:contains("Group: user")'));

        return $crawler->filter('.topbar a[href$=promote]')->link();
    }

    /**
     * @depends testNetizenShow
     */
    public function testUserPromotion(Link $promote)
    {
        $this->client->followRedirects();
        $this->logIn('admin');
        $crawler = $this->client->click($promote);

        $form = $crawler->selectButton('Promote')->form();
        $crawler = $this->client->submit($form, ["social_netizen_role" =>
            ['group' => 'ROLE_MODERATOR']
        ]);

        $this->assertCount(1, $crawler->filter('div.content:contains("Group: moderator")'));
    }

    /**
     * @depends initialize
     */
    public function testHimselfPromotion()
    {
        $this->client->followRedirects();
        $this->logIn('admin');

        $user = $this->getService('social.netizen.repository')
                ->findByNickname('admin');
        $crawler = $this->getPage('admin_netizen_promote', ['id' => (string) $user->getId()]);

        $form = $crawler->selectButton('Promote')->form();
        $crawler = $this->client->submit($form, ["social_netizen_role" =>
            ['group' => 'ROLE_MODERATOR']
        ]);

        $this->assertCurrentRoute('admin_netizen_promote', ['id' => (string) $user->getId()]);
        $this->assertCount(1, $crawler->filter('script:contains("promote yourself")'));
    }

    /**
     * @depends testAccessNetizenListing
     */
    public function testEditLastTicket(Link $showUrl)
    {
        $this->client->followRedirects();
        $this->logIn('admin');

        $crawler = $this->client->click($showUrl);
        $this->assertCount(2, $crawler->filter('div.content table tr'));

        $editLink = $crawler->filter('div.content tr td a i[class="icon-pencil"]')->parents()->link();
        $crawler = $this->client->click($editLink);
        $form = $crawler->selectButton('Edit')->form();
        $crawler = $this->client->submit($form, ["admin_ticket" =>
            ['expiredAt' => ['year' => 2019, 'month' => 4, 'day' => 1]]
        ]);

        $this->assertCount(1, $crawler->filter('table td:contains("April 1, 2019")'));
    }

    /**
     * @depends testAccessNetizenListing
     */
    public function testEditLastTicketWithoutPrivileges(Link $showUrl)
    {
        $this->client->followRedirects();
        $this->logIn('moderator');

        $this->client->click($showUrl);
        $this->assertEquals(403, $this->getCurrentResponse()->getStatusCode());

        $this->getPage('admin_netizen_listing');
        $this->assertEquals(403, $this->getCurrentResponse()->getStatusCode());
    }

    /**
     * @depends testUserPromotion
     */
    public function testExportCsv()
    {
        $this->logIn('admin');
        $crawler = $this->getPage('admin_netizen_listing');
        $form = $crawler->selectButton('Export all')->form();
        $this->client->submit($form, ['social_netizen_filter' => ['group' => 'ROLE_MODERATOR']]);

        $csv = $this->client->getResponse();
        $this->assertInstanceOf('Trismegiste\SocialBundle\Utils\CsvResponse', $csv);
        $content = (string) $csv;
        $this->assertRegExp('#^nickname#m', $content);
        $this->assertRegExp('#^"simple"#m', $content);
        $this->assertRegExp('#^"moderator"#m', $content);
    }

}
