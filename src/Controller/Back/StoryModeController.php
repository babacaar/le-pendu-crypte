<?php

namespace App\Controller\Back;

use App\Entity\StoryMode;
use App\Form\StoryModeType;
use App\Repository\StoryModeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('admin/story/mode')]
final class StoryModeController extends AbstractController
{
    #[Route(name: 'app_story_mode_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(StoryModeRepository $storyModeRepository): Response
    {
        return $this->render('back/story_mode/index.html.twig', [
            'story_modes' => $storyModeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_story_mode_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $story = new StoryMode();
        $story->setPublishedAt(new \DateTimeImmutable());
        $story->setCreatedAt(new \DateTimeImmutable());
    
        $form = $this->createForm(StoryModeType::class, $story);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload if provided
            $imageFile = $form->get('picture')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('story_images_directory'), $newFilename);
                $story->setPicture($newFilename);  
            }
            $slug = $slugger->slug($story->getTitle())->lower();
            $story->setSlug($slug);
            $story->setCurrentChapter(1);

    
            // Associate each chapter with the story
            foreach ($story->getChapters() as $chapter) {
                $chapter->setStoryMode($story);
                $chapter->setCreatedAt(new \DateTimeImmutable());
            }
    
            $entityManager->persist($story);
            $entityManager->flush();
    
            $this->addFlash('success', 'Histoire créée avec succès !');
    
            return $this->redirectToRoute('app_story_mode_index');
        }
    
        return $this->render('back/story_mode/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_story_mode_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(StoryMode $storyMode): Response
    {
        return $this->render('back/story_mode/show.html.twig', [
            'story_mode' => $storyMode,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_story_mode_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, StoryMode $story, EntityManagerInterface $entityManager): Response
    {
        $oldFilename = $story->getPicture();  // Store old picture filename

        $form = $this->createForm(StoryModeType::class, $story);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('picture')->getData();
            if ($imageFile) {
                $newFilename = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('story_images_directory'), $newFilename);
                $story->setPicture($newFilename);
            } else {
                $story->setPicture($oldFilename);  // Keep the old image if none was uploaded
            }

            $entityManager->flush();

            $this->addFlash('success', 'Histoire mise à jour avec succès !');

            return $this->redirectToRoute('app_story_mode_index');
        }

        return $this->render('back/story_mode/edit.html.twig', [
            'form' => $form->createView(),
            'story_mode'=> $story,
        ]);
    }

    #[Route('/{id}', name: 'app_story_mode_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, StoryMode $storyMode, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$storyMode->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($storyMode);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_story_mode_index', [], Response::HTTP_SEE_OTHER);
    }
}
