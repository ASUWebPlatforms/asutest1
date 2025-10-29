<?php

namespace Drupal\tb_gdmi\EventSubscriber;

use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemAddEvent;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Redirects users to the cart page if they have items in the cart.
 */
class CartRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;
  
  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The purchasing path alias.
   *
   * @var string
   */
  const PURCHASING_ROUTE = 'tb_gdmi.dashboard_purchasing';

  /**
   * CartRedirectSubscriber constructor.
   *
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service for accessing the current request.
   */
  public function __construct(CartProviderInterface $cart_provider, RequestStack $request_stack) {
    $this->cartProvider = $cart_provider;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirectToCart'];
    $events[CartEvents::CART_ORDER_ITEM_ADD][] = ['orderItemAddRedirect'];
    return $events;
  }

  /**
   * Redirects users to the cart page if they have items in the cart.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function redirectToCart(RequestEvent $event) {
    $route_name = $event->getRequest()->get('_route');
    $request_uri =  $event->getRequest()->getRequestUri();
    if ($route_name === self::PURCHASING_ROUTE || strpos($request_uri, '/pricing') !== FALSE) {
      // Get the current user's cart.
      $cart = $this->cartProvider->getCart('default');
      if ($cart && count($cart->getItems())) {
        $checkout_url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]);
        $event->setResponse(new RedirectResponse($checkout_url->toString()));
      }
    } 
  }

  /**
   * Redirects users to the checkout flow after add an item.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemAddEvent $event
   *   The event to process.
   */
  public function orderItemAddRedirect(CartOrderItemAddEvent $event) {
    $cart = $event->getCart();
    $checkout_url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]);
    $response = new RedirectResponse($checkout_url ->toString());
    $this->requestStack->getCurrentRequest()->attributes->set('response', $response);
  }

}
