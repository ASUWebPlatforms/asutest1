import 'popper.js';
import 'bootstrap';

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.updateHeaderLogoLinks = {
    attach: function (context) {
      // Only load behavior once
      if (context === document) {
        var nav = $('.header-main .navbar');
        var logos = $('<div class="navbar-logos"></div>');
        var logo_thunderbird_single = $('<a href="/" class="navbar-logo-link"><img src="/themes/custom/thunderbird/assets/img/logo-thunderbird-asu.svg" alt="thunderbird logo"></a>');
        var logo_thunderbird = $('<a href="/" class="navbar-logo-link"><img src="/themes/custom/thunderbird/assets/img/logo-thunderbird.svg" alt="thunderbird logo"></a>');
        var logo_asu = $('<a class="navbar-logo-link" href="https://www.asu.edu"><img src="/themes/custom/thunderbird/assets/img/logo-asu.svg" alt="asu logo"></a>');
        var use_single_logo = false; // Change if they want the logo behavior separate
        
        // Attach logos
        if (use_single_logo == true) logos.append(logo_thunderbird_single);
        else logos.append(logo_thunderbird, logo_asu);


        if (drupalSettings.asu_brand?.gdmi) {
          const gdmi_configs = drupalSettings.asu_brand.gdmi;
          if (gdmi_configs.enabled === 1) {
            $('#asuHeader').css({boxShadow: 'none'});
            const gdmi_title = $('<a class="title-subunit-name" href="" title="Najafi Global Mindset Institute">Najafi Global Mindset Institute</a>');
            logos.append(gdmi_title);
          }
        }

        // Append new logos
        nav.prepend(logos);
      }
    }
  };

  Drupal.behaviors.updateMobileFooter = {
    attach: function (context) {
      // Fix Webspark footer toggle behavior on mobile
      $('.card-header', context).on('click', function(e) {
        var toggle = $(this);
        var toggleClassName = 'show';
        var isShowing = toggle.hasClass(toggleClassName) ? true : false;
        if (isShowing == false) toggle.addClass(toggleClassName);
        else toggle.removeClass(toggleClassName);
      });
    }
  };

  // External links
  Drupal.behaviors.externallinks = {
    attach: function (context) {
      $('a[href^="http"]').each(function () {
        var anchor = $(this);
        var url = anchor.attr('href');
        var protocol = '';

        if (url.indexOf("https://") === 0 ) {
          protocol = 'https://';
        }
        else {
          protocol = 'http://';
        }

        addTargetBlank(anchor, url, protocol);
      });

      // Add return path to CAS login button.
      $('.page.node .btn.cas-login').each(function () {
        var anchor = $(this);
        var url = anchor.attr('href');
        anchor.attr('href', url + "?returnto=" + window.location.pathname);
      });

      function addTargetBlank(anchor, url, protocol) {
        var host = window.location.host;
        var splitHref = url.split(protocol);
        var splitHrefSlash = splitHref[1].split('/');
        var externalPath = splitHrefSlash[0];

        if (host !== externalPath) {
          anchor.attr('target','_blank');
        }
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
