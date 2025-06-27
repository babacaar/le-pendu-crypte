<?php

namespace App\Controller\Back;

use App\Entity\FreeMode;
use App\Form\FreeModeType;
use App\Repository\FreeModeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('admin/free/mode')]
final class FreeModeController extends AbstractController
{
    #[Route(name: 'app_free_mode_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(FreeModeRepository $freeModeRepository): Response
    {
        return $this->render('back/free_mode/index.html.twig', [
            'free_modes' => $freeModeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_free_mode_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $freeMode = new FreeMode();
        $form = $this->createForm(FreeModeType::class, $freeMode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($freeMode);
            $entityManager->flush();

            return $this->redirectToRoute('app_free_mode_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/free_mode/new.html.twig', [
            'free_mode' => $freeMode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_free_mode_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(FreeMode $freeMode): Response
    {
        return $this->render('back/free_mode/show.html.twig', [
            'free_mode' => $freeMode,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_free_mode_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, FreeMode $freeMode, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(FreeModeType::class, $freeMode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_free_mode_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/free_mode/edit.html.twig', [
            'free_mode' => $freeMode,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_free_mode_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, FreeMode $freeMode, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$freeMode->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($freeMode);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_free_mode_index', [], Response::HTTP_SEE_OTHER);
    }
}
