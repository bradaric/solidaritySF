<?php

namespace App\Controller\Admin;

use App\Entity\UserDelegateRequest;
use App\Entity\UserDelegateSchool;
use App\Form\Admin\UserDelegateRequestEditType;
use App\Form\Admin\UserDelegateRequestSearchType;
use App\Repository\UserDelegateRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/user-delegate-request', name: 'admin_user_delegate_request_')]
final class UserDelegateRequestController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(Request $request, UserDelegateRequestRepository $userDelegateRequestRepository): Response
    {
        $form = $this->createForm(UserDelegateRequestSearchType::class);
        $form->handleRequest($request);

        $criteria = [];
        if ($form->isSubmitted()) {
            $criteria = $form->getData();
        }

        $page = $request->query->getInt('page', 1);
        return $this->render('admin/userDelegateRequest/list.html.twig', [
            'userDelegateRequests' => $userDelegateRequestRepository->search($criteria, $page),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, UserDelegateRequest $userDelegateRequest, EntityManagerInterface $entityManager): Response {
        if ($userDelegateRequest->getStatus() != UserDelegateRequest::STATUS_NEW) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UserDelegateRequestEditType::class, $userDelegateRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($userDelegateRequest);
            $entityManager->flush();

            if ($userDelegateRequest->getStatus() == UserDelegateRequest::STATUS_CONFIRMED) {

                // Add role "ROLE_DELEGATE" to user
                $user = $userDelegateRequest->getUser();
                $user->addRole('ROLE_DELEGATE');
                $entityManager->persist($user);

                // Add school to delegate
                $userDelegateSchool = new UserDelegateSchool();
                $userDelegateSchool->setUser($user);
                $userDelegateSchool->setSchool($userDelegateRequest->getSchool());
                $entityManager->persist($userDelegateSchool);

                $entityManager->flush();
                $this->addFlash('success', 'Zahtev za delegata je prihvaćen. Korisniku je dodeljena privilegija za delegata kao i škola.');
            }

            return $this->redirectToRoute('admin_user_delegate_request_list');
        }

        return $this->render('admin/userDelegateRequest/edit.html.twig', [
            'userDelegateRequest' => $userDelegateRequest,
            'form' => $form->createView(),
        ]);
    }
}
