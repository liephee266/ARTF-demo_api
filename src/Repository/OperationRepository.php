<?php
// src/Repository/OperationRepository.php

namespace App\Repository;

use App\Entity\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    /**
     * Cette méthode calcule la somme des montants des opérations d'un expéditeur dans le mois
     * et retourne cette somme.
     */
    public function getMontantTotalDuMois(string $numeroCniexpediteur)
    {
        $dateDebutMois = new DateTime('first day of this month');
        $dateFinMois = new DateTime('now');

        $queryBuilder = $this->createQueryBuilder('o')
            ->select('SUM(o.Montant) as totalMontant')
            ->where('o.NumeroCNIExpediteur = :NumeroCNIExpediteur')
            ->andWhere('o.CreatedAt >= :dateDebutMois')
            ->andWhere('o.CreatedAt <= :dateFinMois')
            ->setParameter('NumeroCNIExpediteur', $numeroCniexpediteur)
            ->setParameter('dateDebutMois', $dateDebutMois)
            ->setParameter('dateFinMois', $dateFinMois);

        $result = $queryBuilder->getQuery()->getSingleScalarResult();

        // Retourne la somme des montants, ou 0 si aucune opération n'a été trouvée
        return $result ;
    }
}
