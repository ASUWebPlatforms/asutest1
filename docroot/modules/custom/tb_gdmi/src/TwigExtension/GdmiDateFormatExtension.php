<?php
namespace Drupal\tb_gdmi\TwigExtension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class GdmiDateFormatExtension.
 */
class GdmiDateFormatExtension extends AbstractExtension {

  public function getFunctions() {
    return [
      new TwigFunction('gdmi_date', [$this, 'gdmiFormatDate']),
    ];
  }

  public static function gdmiFormatDate($date, $timezone, $format) {
    $date = $date->date;
    // $date->setTimezone(new DateTimeZone($timezone));
    return $date->format($format);
  }
}