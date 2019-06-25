<?php

namespace App\DataFixtures;

use App\Entity\Defibrillator;
use App\Entity\Maintenance;
use App\Entity\User;
use App\Entity\Utilization;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use MongoDB\Driver\Manager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture {


    public function load(ObjectManager $manager) {


        // Load defibrillators from OpenDataToulouse
        $jsonData = json_decode(
            file_get_contents(
                "https://data.toulouse-metropole.fr/explore/dataset/defibrillateurs/download/?format=json&timezone=Europe/Berlin"
            ),
            true
        );

        $defibrillators = [];
        foreach ($jsonData as $data) {
            $defibrillator = new Defibrillator();
            $defibrillator->setLongitude($data['fields']['geo_point_2d'][1]);
            $defibrillator->setLatitude($data['fields']['geo_point_2d'][0]);

            $implantation = isset($data['fields']['implantation']) ? $data['fields']['implantation'] : '';
            $note = $data['fields']['nom_site'] ."\n".
                $implantation ."\n".
                $data['fields']['adresse'] ."\n".
                $data['fields']['accessibilite'];

            $defibrillator->setNote($note);

            $rand = mt_rand(0, 10);
            if ($rand <= 1) $defibrillator->setReported(true);

            $manager->persist($defibrillator);
            $defibrillators[] = $defibrillator;
        }
        $manager->flush();

        // Create random maintenances and utilizations
        $dateEnd = new DateTime();
        $dateEnd->modify('-1 day');
        $dateStart = new DateTime();
        $dateStart->modify('-1 year');

        foreach ($defibrillators as $defibrillator) {
            for ($i = 0; $i < mt_rand(0, 10); ++$i) {
                $maintenance = new Maintenance();
                $maintenance->setDefibrillator($defibrillator);
                $maintenance->setUser($users[mt_rand(0, count($users)-1)]);
                    $doneAt = new DateTime();
                    $doneAt->setTimestamp(mt_rand($dateStart->getTimestamp(), $dateEnd->getTimestamp()));
                $maintenance->setDoneAt($doneAt);
                $maintenance->setNote($faker->sentence);

                $manager->persist($maintenance);
            }

            for ($i = 0; $i < mt_rand(0, 20); ++$i) {
                $utilization = new Utilization();
                $utilization->setDefibrillator($defibrillator);
                $utilization->setUser($users[mt_rand(0, count($users)-1)]);
                $doneAt = new DateTime();
                $doneAt->setTimestamp(mt_rand($dateStart->getTimestamp(), $dateEnd->getTimestamp()));
                $utilization->setDoneAt($doneAt);

                $manager->persist($utilization);
            }
        }
        $manager->flush();
    }
}
