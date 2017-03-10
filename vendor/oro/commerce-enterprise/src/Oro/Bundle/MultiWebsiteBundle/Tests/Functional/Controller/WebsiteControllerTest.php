<?php

namespace Oro\Bundle\MultiWebsiteBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class WebsiteControllerTest extends WebTestCase
{
    const WEBSITE_TEST_NAME = 'OroCRM';
    const WEBSITE_UPDATED_TEST_NAME = 'OroCommerce';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_multiwebsite_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('websites-grid', $crawler->html());
        $this->assertEquals('Websites', $crawler->filter('h1.oro-subtitle')->html());
    }

    /**
     * @return int
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_multiwebsite_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'oro_multiwebsite_type' => [
                '_token' => $form['oro_multiwebsite_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WEBSITE_TEST_NAME,
                'fallback' => true,
            ],
        ];

        $this->client->followRedirects(true);

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertWebsiteSaved($crawler, self::WEBSITE_TEST_NAME);

        $website = $this->getWebsiteDataByName(self::WEBSITE_TEST_NAME);

        return $website->getId();
    }

    /**
     * @depends testCreate
     * @param int $id
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_multiwebsite_update', ['id' => $id]));
        $html = $crawler->html();

        $this->assertContains(self::WEBSITE_TEST_NAME, $html);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'oro_multiwebsite_type' => [
                '_token' => $form['oro_multiwebsite_type[_token]']->getValue(),
                'owner' => $this->getCurrentUser()->getId(),
                'name' => self::WEBSITE_UPDATED_TEST_NAME,
                'fallback' => true,
            ],
        ];

        // Submit form
        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertWebsiteSaved($crawler, self::WEBSITE_UPDATED_TEST_NAME);

        $website = $this->getWebsiteDataByName(self::WEBSITE_UPDATED_TEST_NAME);
        $this->assertEquals($id, $website->getId());
    }

    /**
     * @return User
     */
    protected function getCurrentUser()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser();
    }

    /**
     * @param string $name
     * @return Website
     */
    protected function getWebsiteDataByName($name)
    {
        /** @var Website $website */
        $website = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroWebsiteBundle:Website')
            ->getRepository('OroWebsiteBundle:Website')
            ->findOneBy(['name' => $name]);
        $this->assertNotEmpty($website);

        return $website;
    }

    /**
     * @param Crawler $crawler
     * @param string $websiteName
     */
    protected function assertWebsiteSaved(Crawler $crawler, $websiteName)
    {
        $html = $crawler->html();
        $this->assertContains('Website has been saved', $html);
        $this->assertContains($websiteName, $html);
        $this->assertEquals($websiteName, $crawler->filter('h1.user-name')->html());
    }
}
