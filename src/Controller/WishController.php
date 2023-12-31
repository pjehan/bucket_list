<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Wish;
use App\Form\WishType;
use App\Services\Censurator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/wish")
 */
class WishController extends AbstractController
{
    /**
     * @Route("/", name="wish_index")
     * @IsGranted("ROLE_USER")
     */
    public function index(EntityManagerInterface $entityManager): Response
    {
        $wishRepository = $entityManager->getRepository(Wish::class);
        $wishes = $wishRepository->findBy(['user' => $this->getUser()], ['dateCreated' => 'DESC']);

        return $this->render('wish/index.html.twig', ['wishes' => $wishes]);
    }

    /**
     * @Route("/{id}", name="wish_show", requirements={"id"="\d+"})
     */
    public function show(Wish $wish): Response
    {
        return $this->render('wish/show.html.twig', ['wish' => $wish]);
    }

    /**
     * @Route("/new", name="wish_new")
     * @IsGranted("ROLE_USER")
     */
    public function new(Request $request, EntityManagerInterface $entityManager, Censurator $censurator): Response
    {
        $wish = new Wish();
        $wish->setUser($this->getUser());
        $wishForm = $this->createForm(WishType::class, $wish);

        $wishForm->handleRequest($request);

        if ($wishForm->isSubmitted() && $wishForm->isValid()) {
            $wish->setDescription($censurator->purify($wish->getDescription()));

            $entityManager->persist($wish);
            $entityManager->flush();

            $this->addFlash('success', 'Souhait enregistré avec succès !');

            return $this->redirectToRoute('wish_show', ['id' => $wish->getId()]);
        }

        return $this->render('wish/new.html.twig', [
            'wishForm' => $wishForm->createView()
        ]);
    }

    /**
     * @Route("/{id}/edit", name="wish_edit")
     * @IsGranted("ROLE_USER")
     */
    public function edit(Wish $wish, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $wish->getUser() && !$this->isGranted("ROLE_ADMIN")) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce souhait.");
        }

        $wishForm = $this->createForm(WishType::class, $wish);

        $wishForm->handleRequest($request);

        if ($wishForm->isSubmitted() && $wishForm->isValid()) {
            $entityManager->persist($wish);
            $entityManager->flush();

            $this->addFlash('success', 'Souhait enregistré avec succès !');

            return $this->redirectToRoute('wish_show', ['id' => $wish->getId()]);
        }

        return $this->render('wish/edit.html.twig', [
            'wishForm' => $wishForm->createView()
        ]);
    }




}
