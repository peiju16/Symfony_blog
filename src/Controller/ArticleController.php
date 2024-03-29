<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Service\ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $articles= $paginator->paginate(
            $articles = $articleRepository->findAll(),
            $request->query->getInt('page', 1), /*page number*/
            2 /*limit per page*/
        );

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);

        
    }

    #[Route('/new', name: 'app_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ImageService $imageService): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $fileName = $imageService->copyImage("picture", $this->getParameter('article_picture_directory'), $form);
            $article->setPicture($fileName);
            
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre article a bien été ajouté'
            );

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/new.html.twig', [
            'article' => $article,
            'articleForm' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article, EntityManagerInterface $em, int $id): Response
    {
        $comments = $em->getRepository(Comment::class)->findBy(array("article" => $id));

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'comments' => $comments,
           
        ]);
    }

    #[Route('/category/{id_category}', name: 'app_get_article_by_category', methods: ['GET'])]
    public function getArticleByCategory(EntityManagerInterface $entityManager, int $id_category): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findBy(array("category" => $id_category));
        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager, ImageService $imageService): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        //$articlePicture =  $article->getPicture();
        // $article->setPicture(
        //     new File($this->getParameter('article_picture_directory').'/'.$article->getPicture())
        // );

        if ($form->isSubmitted() && $form->isValid()) {

                $fileName = $imageService->copyImage("picture", $this->getParameter('article_picture_directory'), $form);
                $article->setPicture($fileName);
           
                $entityManager->persist($article);
                $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre article a bien été modifié'
            );

            return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'articleForm' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$article->getId(), $request->request->get('_token'))) { //isCsrfTokenValid pour protéger les controllers à eviter être attaqué
            $entityManager->remove($article);
            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre article a bien été supprimé'
            );
        }

        return $this->redirectToRoute('app_article_index', [], Response::HTTP_SEE_OTHER);
    }

 

   
}
