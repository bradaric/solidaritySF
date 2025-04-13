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
    
    /**
     * Test submitting the new educator form
     */
    public function testSubmitNewEducatorForm(): void
    {
        // Before testing, ensure our delegate has at least one school
        $this->addSchoolToDelegate();
        
        $this->loginAsDelegate();
        $crawler = $this->client->request('GET', '/prijavi-ostecenog');
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $form = $crawler->selectButton('Sačuvaj')->form();
        
        // Get a school ID from the delegate's schools
        $schoolOptions = $crawler->filter('select[name="educator_edit[school]"] option')->extract(['value']);
        $schoolId = !empty($schoolOptions[1]) ? $schoolOptions[1] : null;
        
        if (!$schoolId) {
            $this->markTestSkipped('No school options available for this delegate');
        }
        
        $form['educator_edit[name]'] = 'Test Educator';
        $form['educator_edit[school]'] = $schoolId;
        $form['educator_edit[amount]'] = '50000';
        $form['educator_edit[accountNumber]'] = '265104031000361092';

        $this->client->submit($form);
        
        // If there's a validation error, the form will be redisplayed
        if ($this->client->getResponse()->getStatusCode() === Response::HTTP_OK) {
            $this->fail('Form submission did not redirect but returned HTTP 200. See /tmp/form_debug.html for details.');
        }
        
        // Check for any redirect (might not be exactly to /osteceni)
        $this->assertTrue($this->client->getResponse()->isRedirect(), 
            'Response is not a redirect. Status code: ' . $this->client->getResponse()->getStatusCode());
        
        // Follow redirect and check success message
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-success');
    }
    
    /**
     * Test when a user with ROLE_DELEGATE visits the request page
     */
    public function testRequestAccessForExistingDelegate(): void
    {
        $this->loginAsDelegate();
        $this->client->request('GET', '/postani-delegat');
        
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        // Should show approval template rather than the form
        $this->assertSelectorExists('.card-body');
    }
    
    /**
     * Test the edit educator page
     */
    public function testEditEducator(): void
    {
        // First create the city and school
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        // Create city if it doesn't exist
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $city = $cityRepository->findOneBy([]);
        if (!$city) {
            $city = new \App\Entity\City();
            $city->setName('Test City');
            $entityManager->persist($city);
            $entityManager->flush();
        }
        
        // Create school type if it doesn't exist
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        $schoolType = $schoolTypeRepository->findOneBy([]);
        if (!$schoolType) {
            $schoolType = new \App\Entity\SchoolType();
            $schoolType->setName('Test School Type');
            $entityManager->persist($schoolType);
            $entityManager->flush();
        }
        
        // Create test school
        $school = new \App\Entity\School();
        $school->setName('Test School ' . uniqid());
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();
        
        // Get delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        
        // Assign school to delegate
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();
        
        // Login as delegate
        $this->client->loginUser($delegate);
        
        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Test Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        $educator->setCreatedBy($delegate);
        $entityManager->persist($educator);
        $entityManager->flush();
        
        // Request the edit page
        $this->client->request('GET', '/osteceni/' . $educator->getId() . '/izmeni-podatke');
        
        // Verify response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('input[name="educator_edit[name]"]');
    }
    
    /**
     * Test the delete educator confirmation page
     */
    public function testDeleteEducatorConfirmation(): void
    {
        // First create the city and school
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        // Create city if it doesn't exist
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $city = $cityRepository->findOneBy([]);
        if (!$city) {
            $city = new \App\Entity\City();
            $city->setName('Test City');
            $entityManager->persist($city);
            $entityManager->flush();
        }
        
        // Create school type if it doesn't exist
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        $schoolType = $schoolTypeRepository->findOneBy([]);
        if (!$schoolType) {
            $schoolType = new \App\Entity\SchoolType();
            $schoolType->setName('Test School Type');
            $entityManager->persist($schoolType);
            $entityManager->flush();
        }
        
        // Create test school
        $school = new \App\Entity\School();
        $school->setName('Test Delete School ' . uniqid());
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        $entityManager->flush();
        
        // Get delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        
        // Assign school to delegate
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();
        
        // Login as delegate
        $this->client->loginUser($delegate);
        
        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Test Delete Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361098');
        $educator->setCreatedBy($delegate);
        $entityManager->persist($educator);
        $entityManager->flush();
        
        // Request the delete page
        $this->client->request('GET', '/osteceni/' . $educator->getId() . '/brisanje');
        
        // Verify response
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorExists('form');
        $this->assertSelectorExists('h2.card-title');
    }
    
    /**
     * Test that a delegate cannot edit/delete educators from schools they don't have access to
     */
    public function testCannotAccessOtherSchoolEducator(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        // Create a school not assigned to our delegate
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        
        // Get or create a city
        $city = $cityRepository->findOneBy([]);
        if (!$city) {
            $city = new \App\Entity\City();
            $city->setName('Test City');
            $entityManager->persist($city);
        }
        
        // Get or create a school type
        $schoolType = $schoolTypeRepository->findOneBy([]);
        if (!$schoolType) {
            $schoolType = new \App\Entity\SchoolType();
            $schoolType->setName('Test School Type');
            $entityManager->persist($schoolType);
            $entityManager->flush(); // Flush to save these entities first
        }
        
        // Create a new school that's NOT linked to our delegate
        $school = new \App\Entity\School();
        $school->setName('Other School');
        $school->setCity($city);
        $school->setType($schoolType);
        $entityManager->persist($school);
        
        // Create educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Other Educator');
        $educator->setSchool($school);
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        // Find an admin user for creator
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@gmail.com']);
        if (!$adminUser) {
            // If no admin user is found, use the delegate user instead
            $adminUser = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        }
        $educator->setCreatedBy($adminUser);
        $entityManager->persist($educator);
        $entityManager->flush();
        
        // Now try to access this educator
        $this->loginAsDelegate();
        $this->client->request('GET', '/osteceni/' . $educator->getId() . '/izmeni-podatke');
        
        // Should get an access denied error
        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
    
    /**
     * Helper method to create an educator for the delegate
     */
    private function createEducatorForDelegate()
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        // Make sure delegate has a school
        $this->addSchoolToDelegate();
        
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        
        // Get the school assigned to this delegate (should exist after addSchoolToDelegate)
        $delegateSchool = $delegate->getUserDelegateSchools()->first();
        if (!$delegateSchool) {
            // Create a new school and assign it directly
            $cityRepository = $entityManager->getRepository('App\Entity\City');
            $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
            
            // Get or create a city
            $city = $cityRepository->findOneBy([]);
            if (!$city) {
                $city = new \App\Entity\City();
                $city->setName('Test City');
                $entityManager->persist($city);
            }
            
            // Get or create a school type
            $schoolType = $schoolTypeRepository->findOneBy([]);
            if (!$schoolType) {
                $schoolType = new \App\Entity\SchoolType();
                $schoolType->setName('Test School Type');
                $entityManager->persist($schoolType);
                $entityManager->flush();
            }
            
            // Create a new school for the delegate
            $school = new \App\Entity\School();
            $school->setName('Delegate Test School');
            $school->setCity($city);
            $school->setType($schoolType);
            $entityManager->persist($school);
            
            // Create UserDelegateSchool connection
            $delegateSchool = new \App\Entity\UserDelegateSchool();
            $delegateSchool->setUser($delegate);
            $delegateSchool->setSchool($school);
            $entityManager->persist($delegateSchool);
            $entityManager->flush();
        }
        
        // Create an educator in this school
        $educator = new \App\Entity\Educator();
        $educator->setName('Test Educator');
        $educator->setSchool($delegateSchool->getSchool());
        $educator->setAmount(50000);
        $educator->setAccountNumber('265104031000361092');
        $educator->setCreatedBy($delegate);
        
        $entityManager->persist($educator);
        $entityManager->flush();
        
        return $educator;
    }
    
    /**
     * Helper to ensure the delegate has a school assigned
     */
    private function addSchoolToDelegate(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        
        // Get the delegate user
        $delegate = $this->userRepository->findOneBy(['email' => 'delegat@gmail.com']);
        
        // Check if delegate already has schools
        if ($delegate->getUserDelegateSchools()->count() > 0) {
            return;
        }
        
        // Create a test school if needed
        // First check for existing school and city
        $schoolRepository = $entityManager->getRepository('App\Entity\School');
        $cityRepository = $entityManager->getRepository('App\Entity\City');
        $schoolTypeRepository = $entityManager->getRepository('App\Entity\SchoolType');
        
        $school = $schoolRepository->findOneBy([]);
        if (!$school) {
            $city = $cityRepository->findOneBy([]);
            $schoolType = $schoolTypeRepository->findOneBy([]);
            
            if (!$city || !$schoolType) {
                $this->markTestSkipped('Missing required city or school type fixtures');
                return;
            }
            
            // Create a new school
            $school = new \App\Entity\School();
            $school->setName('Test School');
            $school->setCity($city);
            $school->setType($schoolType);
            $entityManager->persist($school);
            $entityManager->flush();
        }
        
        // Create UserDelegateSchool connection
        $delegateSchool = new \App\Entity\UserDelegateSchool();
        $delegateSchool->setUser($delegate);
        $delegateSchool->setSchool($school);
        $entityManager->persist($delegateSchool);
        $entityManager->flush();
    }
}
