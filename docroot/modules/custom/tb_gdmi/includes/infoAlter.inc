<?php

/**
 * Implements hook_commerce_checkout_pane_info_alter().
 */
function tb_gdmi_commerce_checkout_pane_info_alter(&$definitions) {
  if (isset($definitions['review'])) {
    $definitions['review']['class'] = \Drupal\tb_gdmi\Plugin\Commerce\CheckoutPane\GdmiReviewPane::class;
    $definitions['review']['provider'] = 'tb_gdmi';
  }
}