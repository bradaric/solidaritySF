<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Schools;
use App\Entity\City;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CityFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get city names from Schools data class
        $cityNames = array_keys(Schools::getSchoolsMap());

        // Sort cities alphabetically
        sort($cityNames);

        foreach ($cityNames as $name) {
            $city = new City();
            $city->setName($name);
            $manager->persist($city);
        }

        $manager->flush();
    }

    /**
     * @return int[]
     */
    public static function getGroups(): array
    {
        return [1];
    }
}
