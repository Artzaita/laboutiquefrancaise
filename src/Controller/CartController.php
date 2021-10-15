<?php

namespace App\Controller;

use App\Classe\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    /**
     * @Route("/mon-panier", name="app_cart")
     */
    public function index(Cart $cart): Response
    {
        dd($cart->get());
        return $this->render('cart/index.html.twig');
    }

    /**
     * @Route("/mon-panier/add/{id}", name="app_add_to_cart")
     */
    public function add(Cart $cart, $id): Response
    {

        $cart->add($id);

        return $this->redirectToRoute('app_cart');
    }

    /**
     * @Route("/mon-panier/remove", name="app_remove_my_cart")
     */
    public function remove(Cart $cart): Response
    {
        $cart->remove();
        return $this->redirectToRoute('app_products');
    }
}
