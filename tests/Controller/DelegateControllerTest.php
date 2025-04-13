<?php

namespace App\Tests\Controller;

use App\DataFixtures\CityFixtures;
use App\DataFixtures\EducatorFixtures;
use App\DataFixtures\SchoolFixtures;
use App\DataFixtures\SchoolTypeFixtures;
use App\DataFixtures\UserDelegateRequestFixtures;
use App\DataFixtures\UserDelegateSchoolFixtures;
use App\DataFixtures\UserFixtures;
use App\Repository\UserRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DelegateControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AbstractDatabaseTool $databaseTool;
    private ?UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->databaseTool = $container->get(DatabaseToolCollection::class)->get();
        $this->loadFixtures();

        $this->userRepository = $container->get(UserRepository::class);
    }

    private function loadFixtures(): void
    {
        $this->databaseTool->loadFixtures([
            UserFixtures::class,
            CityFixtures::class,
            SchoolTypeFixtures::class,
            SchoolFixtures::class,
            UserDelegateRequestFixtures::class,
            UserDelegateSchoolFixtures::class,
            EducatorFixtures::class,
        ]);
    }

    private function loginAsUser(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'korisnik@gmail.com']);
        $this->client->loginUser($user);
    }

    private function loginAsDelegate(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        if (!$user) {
            throw new \RuntimeException('Delegate user not found. Check UserFixtures for the correct email.');
        }
        $this->client->loginUser($user);
    }

    public function testRedirectToLoginWhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/postani-delegat');

        $this->assertTrue($this->client->getResponse()->isRedirect());
        $this->assertStringContainsString('/logovanje', $this->client->getResponse()->headers->get('Location'));
    }

    public function testRequestAccessPage(): void
    {
        $this->loginAsUser();
        $this->client->request('GET', '/postani-delegat');

        // Just check that the page loads with 200 OK status
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEducatorsList(): void
    {
        $this->loginAsDelegate();
        $crawler = $this->client->request('GET', '/osteceni');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        // Check for the table
        $this->assertCount(1, $crawler->filter('table'));
        // Check for the add button (by content rather than href)
        $this->assertSelectorTextContains('a.btn-primary', 'Dodaj');
    }
    
    /**
     * Test redirecting to new educator form
     */
    public function testNewEducatorForm(): void
    {
        $this->loginAsDelegate();
        $this->client->request('GET', '/prijavi-ostecenog');
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
    }
}
