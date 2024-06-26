<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        
        $session= $request->getSession();

        // calculer le montant total de mon panier
        $carTotal = 0;

        if (!is_null($session->get('cart')) && count($session->get('cart')) > 0) {
            for ($i=0; $i < count($session->get('cart')["id"]); $i++) { 
                $carTotal += floatval($session->get('cart')['id'][$i]) * $session->get('cart')["quantity"][$i];
             }
        }

        return $this->render('cart/index.html.twig', [
            'cartItems' => $session->get('cart'),
            'cartTotal' => $carTotal,
        ]);
    }

    #[Route('/cart/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function addProduct(ProductRepository $productRepository, Request $request, int $id): Response
    {

         // créer la session

        $session= $request->getSession();

        if (!$session->get('cart')) {
            $session->set('cart', [
                "id" => [],
                "name" => [],
                "description" => [],
                "picture" => [],
                "price" => [],
                "quantity" => [],
                "priceIdStripe" => [],
            ]);
        }

        $cart = $session->get('cart');
     

        // ajouter le produit au panier

        // récupérer les infos du produit en BDD et l'ajouter à mon panier
        $product = $productRepository->find($id);
        $cart["id"][] = $product->getId();
        $cart["name"][] = $product->getName();
        $cart["description"][] = $product->getDescription();
        $cart["picture"][] = $product->getPicture();
        $cart["price"][] = $product->getPrice();
        $cart["quantity"][] = 1;
        $cart["priceIdStripe"][] = $product->getPriceIdStripe();

        $session->set('cart', $cart);

        // calculer le montant total de mon panier
        $carTotal = 0;

        for ($i=0; $i < count($session->get('cart')["id"]); $i++) { 
            $carTotal += (float) $session->get('cart')["price"][$i] * $session->get('cart')["quantity"][$i];
        }

        // afficher la page panier

        return $this->render('cart/index.html.twig', [
            'cartItems' => $session->get('cart'),
            'cartTotal' => $carTotal,
        ]);
    }

    #[Route('/cart/delete', name: 'app_cart_delete', methods: ['GET'])]
    public function deleteCart(EntityManagerInterface $em, Request $request): Response
    {
        
        $session= $request->getSession();
        $session->set('cart', []);

        return $this->redirectToRoute('app_cart');

      
    }
}

