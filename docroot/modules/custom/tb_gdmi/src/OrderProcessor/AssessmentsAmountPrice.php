<?php

namespace Drupal\tb_gdmi\OrderProcessor;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;

/**
 * Calculates order amount based on the participants list.
 */
class AssessmentsAmountPrice implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    
    $order_bundle = $order->bundle();
    $items = $order->getItems();
    $participants = $order->getData('participants');

    if (!empty($items) && $participants !== NULL) {
      $item = reset($items);
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
      $purchased_entity = $item->getPurchasedEntity();
      $product_bundle = $purchased_entity->bundle();
      
      // DEFAULT ORDER GROUP TYPE.
      if ($order_bundle === 'default' && $product_bundle === 'gdmi_assessment') {
        $type = $purchased_entity->attribute_assessment_type->entity->label();
        if (strtolower($type) === 'group') {
          $item->setQuantity(count($participants));
        }
      }

      // PARTICIPANTA EXPANSION.
      if ($order_bundle === 'gdmi_expand_participants' && $product_bundle === 'gdmi_expand_participants' && $participants !== NULL) {
        $new_participants = array_filter($participants, function($item) { return !isset($item['expansion']) || (isset($item['expansion']) && $item['expansion'] === '1');});
        if (!empty($new_participants)) {
          $item->setQuantity(count($new_participants));
        }
      }
    }
  }

}