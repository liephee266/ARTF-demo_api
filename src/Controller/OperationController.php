<?php
namespace App\Controller;

use App\Entity\User;
use Pagerfanta\Pagerfanta;
use App\Entity\Operation;
use App\Entity\Organisation;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Services\Toolkit;


#[Route('/api/v1/operations')]
class OperationController extends AbstractController
{
    private OperationRepository $OperationRepository;
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;
    private Toolkit $toolkit;

    public function __construct(
        OperationRepository $OperationRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        Toolkit $toolkit
    ) {
        $this->OperationRepository = $OperationRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->toolkit = $toolkit;
    }

    #[Route('/{id}', name: 'operation_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            //code...
            $operation = $this->OperationRepository->find($id);
            $data = $this->serializer->serialize($operation, 'json', ['groups' => 'api_Operation_show']);
            return new JsonResponse([ 'data' => json_decode($data), 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            //throw $th;
            return new JsonResponse(['message' => 'Operation introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    } 

    #[Route('/', name: 'operation_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            //code...
            $t = ($this->entityManager->getRepository(Operation::class)->getMontantTotalDuMois($data['NumeroCNIExpediteur']) >= 1000000);
            if ($t) {
                # code...
                $data['statu'] = "Rejeté";
                //la fonction pour
            }
            $operation = new Operation();
            $operation->setMontant($data['montant'])
                    ->setNomDestinataire($data['nom_destinataire'] )
                    ->setNumeroCNIDestinataire($data['numero_cni_destinataire'])
                    ->setNumeroCNIExpediteur($data['numero_cni_expediteur'])
                    ->setNomExpediteur($data['nom_expediteur'])
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());
            if ($data['id_user'] !== null) {
                $id_user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['id_user']]);
                $operation->setUser($id_user);
            }
            
            $this->entityManager->persist($operation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Operation crée avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => "un problème est survenu lors de la création de l Operation", 'code' => 500], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', name: 'operation_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $operation = $this->OperationRepository->find($id);
            $data = json_decode($request->getContent(), true);
            $operation->setMontant($data['montant'] ?? $operation->getMontant())
                    ->setNomDestinataire($data['nom_destinataire'] ?? $operation->getNomdestinataire())
                    ->setNumeroCNIExpediteur($data['numero_cni_expediteur'] ?? $operation->getNumeroCNIExpediteur())
                    ->setNumeroCNIDestinataire($data['numero_cni_destinataire'] ?? $operation->getNumeroCNIDestinataire())
                    ->setNomExpediteur($data['nom_expediteur'] ?? $operation->getNomExpediteur())
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());
                    if ($data['id_user'] !== null) {
                        $id_user = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $data['id_user']]);
                        $operation->setUser($id_user);
                    }
            $this->entityManager->persist($operation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Operation modifié avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Operation introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/{id}', name: 'operation_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $operation = $this->OperationRepository->find($id);        
            $this->entityManager->remove($operation);
            $this->entityManager->flush();
            return new JsonResponse(['message' => 'Operation supprimé avec succès', 'code' => 200], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => 'Operation introuvable', 'code' => 404], Response::HTTP_NOT_FOUND);
        }
    }
    #[Route("/montants/{numeroCniexpediteur}", name: "operation_montants", methods: ['GET'])]
    public function index(string $numeroCniexpediteur, OperationRepository $operationRepository): Response
    {
        // Récupérer la somme des montants pour ce numéro d'expéditeur dans le mois
        $montantTotalDuMois = $operationRepository->getMontantTotalDuMois($numeroCniexpediteur);
        
        // Limite autorisée
        $limite = 1000000;

        // Vérifier si la somme dépasse la limite
        if ($montantTotalDuMois > $limite) {
            // Retourner un message d'erreur si la somme dépasse la limite
            return $this->json([
                'message' => 'Vous avez dépassé la limite autorisée de 1 000 000 dans le mois.',
                'montant_total' => $montantTotalDuMois
            ], Response::HTTP_BAD_REQUEST);
        }

        // Sinon, retourner la somme des montants avec un message de validation
        return $this->json([
            'montant_total' => $montantTotalDuMois,
            'message' => 'Le montant total des transactions dans le mois est valide.'
        ]);
    }
}
