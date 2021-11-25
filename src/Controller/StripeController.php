<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\StripeClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-checkout-session/{reference}", name="app_stripe_create_session")
     */
    public function index(EntityManagerInterface $entityManager, Cart $cart, $reference): Response
    {

        $stripe = new StripeClient('sk_test_51JytuhBIZyC7qDYHMANTouWfCE9l8J6RkWCPVjFUEMWEyyLwPOho6sHNkIIxEZyl1mLXTKRpiH6E9Atb3FApuh8P00v6i3DOGy');

        $product_for_stripe = [];
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';

        Stripe::setApiKey('sk_test_51JytuhBIZyC7qDYHMANTouWfCE9l8J6RkWCPVjFUEMWEyyLwPOho6sHNkIIxEZyl1mLXTKRpiH6E9Atb3FApuh8P00v6i3DOGy');

        $order = $entityManager->getRepository(Order::class)->findOneByReference($reference);

        if (!$order) {
            new JsonResponse(['error' => 'order']);
        }

        foreach ($order->getOrderDetails()->getValues() as $product) {

            $produit = $stripe->products->create([
                'name'=> $product->getProduct(),
            ]);
            
            $price = Price::create([
                'product' => $produit->id,
                'unit_amount' => $product->getPrice(),
                'currency' => 'eur',
              ]);

            $product_for_stripe[] = [
                'price' => $price->id,
                'quantity' => $product->getQuantity(),
            ];
        }

        $produit = $stripe->products->create([
            'name'=> 'Livraison : '.$order->getCarrierName(),
        ]);
        
        $price = Price::create([
            'product' => $produit->id,
            'unit_amount' => $order->getCarrierPrice(),
            'currency' => 'eur',
          ]);

        $product_for_stripe[] = [
            'price' => $price->id,
            'quantity' => 1,
        ];

        $checkout_session = Session::create([
            'customer_email' => $this->getUser()->getEmail(),
            'line_items' => [
                $product_for_stripe,
            ],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
        ]);

        $order->setStripeSessionId($checkout_session->id);

        $entityManager->flush();

        $response = new JsonResponse( ['id' => $checkout_session->id]);

        return $response;
    }
}
