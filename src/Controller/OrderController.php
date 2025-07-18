<?php

namespace App\Controller;

use App\Entity\Product;
use App\Store\ShoppingCart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderController extends AbstractController
{
    #[Route('/cart', name: 'app_order_cart')]
    public function cart(ShoppingCart $cart): Response
    {
        return $this->render('order/cart.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/product/{slug:product}/add', name: 'app_cart_product_add', methods: ['POST'])]
    public function addProductToCart(Request $request, Product $product, ShoppingCart $cart): Response
    {
        $quantity = $request->request->getInt('quantity', 1);
        $quantity = $quantity > 0 ? $quantity : 1;
        $cart->addProduct($product, $quantity);

        $this->addFlash('success', 'Yummy lemonade has been added to your cart!');

        return $this->redirectToRoute('app_order_cart');
    }

    #[Route('/cart/product/{slug:product}/delete', name: 'app_cart_product_delete', methods: ['POST'])]
    public function deleteProductFromCart(Product $product, ShoppingCart $cart): Response
    {
        $cart->deleteProduct($product);

        $this->addFlash('success', 'Yummy lemonade has been deleted from your cart! Too sour?');

        return $this->redirectToRoute('app_order_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clearCart(ShoppingCart $cart): Response
    {
        $cart->clear();

        $this->addFlash('success', 'Cart cleared!');

        return $this->redirectToRoute('app_order_cart');
    }

    #[Route('/checkout', name: 'app_cart_checkout')]
    public function checkout(
        #[Target('lemonSqueezyClient')]
        HttpClientInterface $lsClient,
        ShoppingCart $cart,
    ): Response {
        $lsCheckoutUrl  = $this->createLsCheckoutUrl($lsClient, $cart);

        return $this->redirect($lsCheckoutUrl);
    }

    private function createLsCheckoutUrl(HttpClientInterface $lsClient, ShoppingCart $cart): string
    {
        if ($cart->isEmpty()) {
            throw new \LogicException('Nothing to checkout!');
        }
        $response = $lsClient->request(Request::METHOD_POST, 'checkouts', [
            'json' => [
                'data' => [
                    'type' => 'checkouts',
                    'relationships' => [
                        'store' => [
                            'data' => [
                                'type' => 'stores',
                                'id' => '173746',
                            ],
                        ],
                        'variant' => [
                            'data' => [
                                'type' => 'variant',
                                'id' => '845276',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $lsCheckout = $response->toArray();

        return $lsCheckout['data']['attributes']['url'];
    }
}
