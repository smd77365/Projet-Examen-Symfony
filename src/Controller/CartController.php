<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\CartItemRepository;
use App\Repository\OrderRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/cart')]
#[IsGranted('ROLE_USER')]
class CartController extends AbstractController
{
    #[Route('/', name: 'app_cart_index', methods: ['GET'])]
    public function index(CartItemRepository $cartItemRepository): Response
    {
        $user = $this->getUser();
        $cartItems = $cartItemRepository->findByUser($user);
        $total = $cartItemRepository->getCartTotal($user);
        $itemCount = $cartItemRepository->getCartItemCount($user);

        return $this->render('cart/index.html.twig', [
            'cart_items' => $cartItems,
            'total' => $total,
            'item_count' => $itemCount,
        ]);
    }

    #[Route('/add/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(Request $request, int $id, ProduitRepository $produitRepository, CartItemRepository $cartItemRepository): Response
    {
        $produit = $produitRepository->find($id);
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }

        $user = $this->getUser();
        $quantite = (int) $request->request->get('quantite', 1);

        // Vérifier le stock
        if ($quantite > $produit->getStock()) {
            $this->addFlash('error', 'Stock insuffisant pour ce produit.');
            return $this->redirectToRoute('app_produit_show', ['id' => $id]);
        }

        // Vérifier si le produit est déjà dans le panier
        $existingCartItem = $cartItemRepository->findOneByUserAndProduct($user, $produit);

        if ($existingCartItem) {
            // Mettre à jour la quantité
            $newQuantite = $existingCartItem->getQuantite() + $quantite;
            if ($newQuantite > $produit->getStock()) {
                $this->addFlash('error', 'Stock insuffisant pour ajouter cette quantité.');
                return $this->redirectToRoute('app_produit_show', ['id' => $id]);
            }
            $existingCartItem->setQuantite($newQuantite);
        } else {
            // Créer un nouvel élément de panier
            $cartItem = new CartItem();
            $cartItem->setProduit($produit);
            $cartItem->setQuantite($quantite);
            $cartItem->setUser($user);
            $cartItemRepository->save($cartItem, true);
        }

        $this->addFlash('success', 'Produit ajouté au panier avec succès.');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/update/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, CartItem $cartItem, CartItemRepository $cartItemRepository): Response
    {
        $quantite = (int) $request->request->get('quantite');
        
        if ($quantite <= 0) {
            $cartItemRepository->remove($cartItem, true);
            $this->addFlash('success', 'Produit supprimé du panier.');
        } else {
            if ($quantite > $cartItem->getProduit()->getStock()) {
                $this->addFlash('error', 'Stock insuffisant pour cette quantité.');
                return $this->redirectToRoute('app_cart_index');
            }
            
            $cartItem->setQuantite($quantite);
            $cartItemRepository->save($cartItem, true);
            $this->addFlash('success', 'Quantité mise à jour.');
        }

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/remove/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(CartItem $cartItem, CartItemRepository $cartItemRepository): Response
    {
        $cartItemRepository->remove($cartItem, true);
        $this->addFlash('success', 'Produit supprimé du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clear(CartItemRepository $cartItemRepository): Response
    {
        $user = $this->getUser();
        $cartItems = $cartItemRepository->findByUser($user);
        
        foreach ($cartItems as $cartItem) {
            $cartItemRepository->remove($cartItem);
        }
        $cartItemRepository->getEntityManager()->flush();
        
        $this->addFlash('success', 'Panier vidé avec succès.');
        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/checkout', name: 'app_cart_checkout', methods: ['POST'])]
    public function checkout(CartItemRepository $cartItemRepository, OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        $cartItems = $cartItemRepository->findByUser($user);

        if (empty($cartItems)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        // Créer la commande
        $order = new Order();
        $order->setUser($user);
        $order->setStatus('confirmed');

        // Créer les éléments de commande
        foreach ($cartItems as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduit($cartItem->getProduit());
            $orderItem->setQuantite($cartItem->getQuantite());
            $orderItem->setPrixUnitaire($cartItem->getProduit()->getPrix());
            $order->addOrderItem($orderItem);

            // Mettre à jour le stock
            $produit = $cartItem->getProduit();
            $produit->setStock($produit->getStock() - $cartItem->getQuantite());
        }

        $order->setTotal($order->calculateTotal());
        $orderRepository->save($order, true);

        // Vider le panier
        foreach ($cartItems as $cartItem) {
            $cartItemRepository->remove($cartItem);
        }
        $cartItemRepository->getEntityManager()->flush();

        $this->addFlash('success', 'Commande validée avec succès !');
        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }
} 