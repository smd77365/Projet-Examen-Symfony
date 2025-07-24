<?php

namespace App\Command;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Créer des données de test pour l\'application',
)]
class CreateTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création des données de test');

        // Créer un utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setIsVerified(true);
        $this->entityManager->persist($admin);

        // Créer un utilisateur normal
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $user->setIsVerified(true);
        $this->entityManager->persist($user);

        // Créer des catégories
        $categories = [
            'Électronique' => 'Produits électroniques et informatiques',
            'Vêtements' => 'Habillement et accessoires',
            'Livres' => 'Livres et publications',
            'Sport' => 'Équipements et vêtements de sport',
            'Maison' => 'Articles pour la maison et le jardin'
        ];

        $categorieEntities = [];
        foreach ($categories as $nom => $description) {
            $categorie = new Categorie();
            $categorie->setNom($nom);
            $categorie->setDescription($description);
            $this->entityManager->persist($categorie);
            $categorieEntities[$nom] = $categorie;
        }

        // Créer des produits
        $produits = [
            [
                'nom' => 'Smartphone Galaxy S23',
                'description' => 'Smartphone Samsung Galaxy S23 avec écran 6.1" et appareil photo 50MP',
                'prix' => 899.99,
                'stock' => 15,
                'categorie' => 'Électronique'
            ],
            [
                'nom' => 'Laptop Dell Inspiron',
                'description' => 'Ordinateur portable Dell Inspiron 15" avec processeur Intel i5',
                'prix' => 699.99,
                'stock' => 8,
                'categorie' => 'Électronique'
            ],
            [
                'nom' => 'T-shirt en coton',
                'description' => 'T-shirt en coton bio, disponible en plusieurs couleurs',
                'prix' => 19.99,
                'stock' => 50,
                'categorie' => 'Vêtements'
            ],
            [
                'nom' => 'Jeans slim fit',
                'description' => 'Jeans slim fit en denim stretch, très confortable',
                'prix' => 49.99,
                'stock' => 25,
                'categorie' => 'Vêtements'
            ],
            [
                'nom' => 'Le Petit Prince',
                'description' => 'Édition illustrée du célèbre roman de Saint-Exupéry',
                'prix' => 12.99,
                'stock' => 30,
                'categorie' => 'Livres'
            ],
            [
                'nom' => 'Harry Potter à l\'école des sorciers',
                'description' => 'Premier tome de la série Harry Potter',
                'prix' => 15.99,
                'stock' => 20,
                'categorie' => 'Livres'
            ],
            [
                'nom' => 'Ballon de football',
                'description' => 'Ballon de football professionnel taille 5',
                'prix' => 29.99,
                'stock' => 12,
                'categorie' => 'Sport'
            ],
            [
                'nom' => 'Raquette de tennis',
                'description' => 'Raquette de tennis professionnelle avec cordage',
                'prix' => 89.99,
                'stock' => 7,
                'categorie' => 'Sport'
            ],
            [
                'nom' => 'Lampe de bureau LED',
                'description' => 'Lampe de bureau moderne avec éclairage LED réglable',
                'prix' => 39.99,
                'stock' => 18,
                'categorie' => 'Maison'
            ],
            [
                'nom' => 'Cafetière programmable',
                'description' => 'Cafetière programmable avec minuterie et filtre permanent',
                'prix' => 79.99,
                'stock' => 10,
                'categorie' => 'Maison'
            ]
        ];

        foreach ($produits as $produitData) {
            $produit = new Produit();
            $produit->setNom($produitData['nom']);
            $produit->setDescription($produitData['description']);
            $produit->setPrix($produitData['prix']);
            $produit->setStock($produitData['stock']);
            $produit->setCategorie($categorieEntities[$produitData['categorie']]);
            $this->entityManager->persist($produit);
        }

        $this->entityManager->flush();

        $io->success([
            'Données de test créées avec succès !',
            '',
            'Comptes créés :',
            '- Admin : admin@example.com / admin123',
            '- User : user@example.com / user123',
            '',
            'Catégories créées : ' . count($categories),
            'Produits créés : ' . count($produits)
        ]);

        return Command::SUCCESS;
    }
} 