<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    /**
     * @Route("/nos-produits", name="app_products")
     */
    public function index(EntityManagerInterface $entityManager): Response
    {

        $products = $entityManager->getRepository(Product::class)->findAll();
        return $this->render('product/index.html.twig', [
            'products' => $products,
        ]);
    }


        /**
     * @Route("/produit/{slug}", name="app_product")
     */
    public function show(EntityManagerInterface $entityManager, $slug): Response
    {

        $product = $entityManager->getRepository(Product::class)->findOneBySlug($slug);

        if ( !$product) {
            return $this->redirectToRoute('app_products');
        }



        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
