<?php

namespace App\DataFixtures\Data;

class Names
{
    public static function getFirstNames(): array
    {
        return [
            'Aleksandar', 'Andrej', 'Branislav', 'Bojan', 'Boris',
            'Dragan', 'Dušan', 'Dejan', 'Damjan', 'Darko',
            'Filip', 'Goran', 'Igor', 'Ivan', 'Jovan',
            'Lazar', 'Luka', 'Marko', 'Milan', 'Miloš',
            'Nemanja', 'Nikola', 'Ognjen', 'Petar', 'Pavle',
            'Stefan', 'Srđan', 'Uroš', 'Vladimir', 'Vuk',
            'Ana', 'Aleksandra', 'Bojana', 'Dragana', 'Danijela',
            'Elena', 'Gordana', 'Ivana', 'Jelena', 'Jovana',
            'Katarina', 'Kristina', 'Ljiljana', 'Marina', 'Marija',
            'Milica', 'Nina', 'Nevena', 'Olivera', 'Sanja',
            'Sonja', 'Svetlana', 'Tamara', 'Tijana', 'Vesna',
        ];
    }

    public static function getLastNames(): array
    {
        return [
            'Petrović', 'Jovanović', 'Popović', 'Đorđević', 'Stojanović',
            'Ristić', 'Stanković', 'Pavlović', 'Marković', 'Nikolić',
            'Ilić', 'Kovačević', 'Lazić', 'Janković', 'Vuković',
            'Tomić', 'Todorović', 'Simić', 'Kostić', 'Dimitrijević',
            'Savić', 'Radovanović', 'Obradović', 'Mladenović', 'Mitrović',
            'Stefanović', 'Vasić', 'Živković', 'Lukić', 'Krstić',
        ];
    }
}
