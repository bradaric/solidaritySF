<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\Schools;
use App\Entity\SchoolType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SchoolTypeFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $types = Schools::getSchoolTypes();
        foreach ($types as $type) {
            $schoolType = new SchoolType();
            $schoolType->setName($type);
            $manager->persist($schoolType);
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
