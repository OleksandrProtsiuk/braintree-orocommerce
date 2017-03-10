<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;

/**
 * @dbIsolation
 */
class CustomerUserProfileControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);
    }

    public function testViewProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_profile'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(LoadCustomerUserData::AUTH_USER, $crawler->filter('.user-page')->html());
    }

    public function testEditProfile()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form->offsetSet('oro_customer_frontend_customer_user_profile[firstName]', 'CustomerUserUpdated');

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('CustomerUserUpdated', $crawler->filter('.user-page')->html());
    }

    public function testEditProfilePasswordMismatch()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form->offsetSet(
            'oro_customer_frontend_customer_user_profile[changePassword]',
            [
                'currentPassword' => LoadCustomerUserData::AUTH_PW,
                'plainPassword' => [
                    'first' => '123456',
                    'second' => '654321',
                ]
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains('The password fields must match.', $crawler->filter('.password_first span')->html());
    }

    public function testEditProfileWithoutCurrentPassword()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_profile_update'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form();
        $form->offsetSet(
            'oro_customer_frontend_customer_user_profile[changePassword]',
            [
                'currentPassword' => '123456',
                'plainPassword' => [
                    'first' => '123456',
                    'second' => '123456',
                ]
            ]
        );
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(
            'This value should be the user\'s current password.',
            $crawler->filter('.current_password span')->html()
        );
    }
}
