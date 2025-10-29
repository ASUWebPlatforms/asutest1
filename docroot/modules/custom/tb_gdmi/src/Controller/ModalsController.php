<?php
namespace Drupal\tb_gdmi\Controller;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for modals.
 */
class ModalsController extends ControllerBase {

   /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * Constructs a new ModalsController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   */
  public function __construct(RendererInterface $renderer, CartManagerInterface $cart_manager, CartProviderInterface $cart_provider) {
    $this->renderer = $renderer;
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider')
    );
  }

  /**
   * Returns a modal.
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A renderable array.
   */
  public function modal(Request $request) {

    // This to future integrations of diferent modal types and args.
    $data = $request->request->all();

    $content['id'] = $data['id'];
    $content['type'] = isset($data['type']) ? $data['type'] : NULL;
    $content['title'] = $this->t($data['title']);
    $content['subtitle'] = isset($data['subtitle']) ? $data['subtitle'] : NULL;
    $content['description'] = $this->t($data['description']);
    $content['close_btn']['text'] = $this->t($data['close_btn_text']);
    $content['confirm_btn']['text'] = $this->t($data['confirm_btn_text']);
    $content['icon'] = isset($data['icon']) ? $data['icon'] : NULL;
    $build = [
      '#theme' => 'gdmi_modal',
      '#content' => $content
    ];

    return new JsonResponse(['html' =>  $this->renderer->render($build)]);
  }

  /**
   * Cancel the current purchase empty cart.
   */
  public function cancelPurchase() {
  
    $carts = $this->cartProvider->getCarts();
    foreach ($carts as $order) {
      $order->unsetData('group_name');
      $order->unsetData('organization');
      $order->unsetData('participants');
      $order->unsetData('csv_file');
      $order->unsetData('purchaser_participation');
      $order->unsetData('group_id');
      $order->unsetData('admin_designation');
      $order->unsetData('admin_designation_email');
      $order->unsetData('admin_designation_participation');
      $this->cartManager->emptyCart($order);
    }

    return new RedirectResponse(Url::fromRoute('tb_gdmi.dashboard')->toString());
  }

}
