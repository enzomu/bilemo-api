<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $clients = [];

        $fixedClients = [
            ['name' => 'TechStore', 'email' => 'admin@techstore.com'],
            ['name' => 'Shop1', 'email' => 'admin@shop1.com'],
            ['name' => 'Shop2', 'email' => 'admin@shop2.com'],
        ];

        foreach ($fixedClients as $clientData) {
            $client = new Client();
            $client->setName($clientData['name'])
                ->setEmail($clientData['email'])
                ->setPassword($this->passwordHasher->hashPassword($client, 'password123'))
                ->setIsActive(true);

            $manager->persist($client);
            $clients[] = $client;
        }

        $products = [
            ['brand' => 'Apple', 'models' => ['iPhone 14', 'iPhone 15', 'iPhone 16']],
            ['brand' => 'Samsung', 'models' => ['Galaxy S24', 'Galaxy A55', 'Galaxy Note']],
            ['brand' => 'Xiaomi', 'models' => ['Redmi Note 13', 'Mi 14', 'POCO X6']],
            ['brand' => 'OnePlus', 'models' => ['OnePlus 12', 'OnePlus 11']],
        ];

        foreach ($products as $brandData) {
            foreach ($brandData['models'] as $model) {
                $product = new Product();
                $product->setName($model)
                    ->setBrand($brandData['brand'])
                    ->setModel($faker->numerify('A####'))
                    ->setPrice($faker->numberBetween(100, 1000) . '.00')
                    ->setDescription('Smartphone ' . $brandData['brand'] . ' avec écran haute définition et appareil photo performant.')
                    ->setSpecifications([
                        'screen' => $faker->randomElement(['6', '7', '8']) . ' pouces',
                        'storage' => $faker->randomElement(['128 GB', '256 GB', '512 GB']),
                    ]);

                $manager->persist($product);
            }
        }

        foreach ($clients as $client) {
            $userCount = $faker->numberBetween(5, 10);

            for ($i = 0; $i < $userCount; $i++) {
                $user = new User();
                $user->setFirstName($faker->firstName())
                    ->setLastName($faker->lastName())
                    ->setEmail($faker->email())
                    ->setClient($client);

                $manager->persist($user);
            }
        }

        $manager->flush();
    }
}
