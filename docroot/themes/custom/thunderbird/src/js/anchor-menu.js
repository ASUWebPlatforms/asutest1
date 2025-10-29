(function ($, Drupal, drupalSettings) {

  Drupal.behaviors.anchorMenu = {
    attach: function (context, settings) {
      if ($(context).find('#uds-anchor-menu').length) {

        let links = $(once('anchor-menu','.webspark-anchor-link-data'));
        if (!links.length) {
          return;
        }

        let section = $('.uds-anchor-menu-wrapper h4').text().toLowerCase().trim();

        links.each(function(i, item) {
          let icon = $(item).siblings('.anchor-link-icon').html();
          let title = $(item).data('title');
          let href = $(item).attr('id');
          let data_title = title.toLowerCase();

          $('#uds-anchor-menu .nav').append('<a class="nav-link" data-ga-event="link" data-ga-action="click" data-ga-name="onclick" data-ga-type="internal link" data-ga-region="main content" data-ga-component="" data-ga-section="' + section + '" data-ga-text="' + data_title + '" href="#' + href + '">' + icon + '</span>' + title + '</a>');
        });


        $('#ws2HeaderContainer header').attr('id', 'global-header');


        // We use setTimeout to compensate header built by react 🤦
        setTimeout(function() {
          let globalHeader = document.getElementById('global-header');
          let navbar = document.getElementById('uds-anchor-menu');

          // Compensate for a fixed admin toolbar.
          let offset = $('#toolbar-administration').length ? 78 : 0;

          initializeAnchorMenu(globalHeader, navbar, offset);
        }, 100);

        $('.uds-anchor-menu').show();
      } 
    }
  };


  function initializeAnchorMenu(globalHeader, navbar, offset) {
    const isMobile = window.innerWidth < 610;
    const isMedium = window.innerWidth >= 610 && window.innerWidth < 992; 
    const anchors = navbar.getElementsByClassName('nav-link');
    const navbarInitialPosition = navbar.getBoundingClientRect().top + window.scrollY;
    const anchorTargets = new Map();
    let previousScrollPosition = window.scrollY;

    // Cache the anchor target elements by mapping them as a key/pair so don't have to
    // parse the dom on every scroll event
    for (let anchor of anchors) {
      const targetId = anchor.getAttribute('href').replace('#', '');
      const target = document.getElementById(targetId);
      anchorTargets.set(anchor, target);
    }

    window.addEventListener("scroll", function () {
      const navbarY = navbar.getBoundingClientRect().top;
      const headerHeight = globalHeader.offsetHeight;

      // If scrolling DOWN
      if (
        window.scrollY > previousScrollPosition &&
        !navbar.classList.contains('uds-anchor-menu-sticky')
      ) {
        if (navbarY > offset && navbarY < headerHeight + offset) {
          globalHeader.style.top = -(headerHeight - navbarY) + 'px';
        } else if (navbarY <= offset) {
          globalHeader.style.top = -globalHeader.offsetHeight + 'px';
          navbar.classList.add('uds-anchor-menu-sticky');
          navbar.style.top = offset + 'px';
          if (isMobile && showStickyOnMobile()) {
            navbar.classList.add('scrolled-mobile');
          }
        }
      }
      // If scrolling UP
      if (
        window.scrollY < previousScrollPosition &&
        window.scrollY < (navbarInitialPosition)
      ) {
        navbar.classList.remove('uds-anchor-menu-sticky');
        if (isMobile && showStickyOnMobile()) {
          navbar.classList.remove('scrolled-mobile');
        }
        if (globalHeader.getBoundingClientRect().top < offset) {
          const localOffset = globalHeader.getBoundingClientRect().top + navbarY + offset;
          globalHeader.style.top = (localOffset + offset < 0 ? localOffset + offset : offset) + 'px';
        }
      }

      for (let [anchor, target] of anchorTargets) {
        const offsets = navbar.offsetHeight;

        if (
          target.getBoundingClientRect().top < offsets &&
          target.getBoundingClientRect().top + target.offsetHeight > offsets
        ) {
          anchor.classList.add('active');
        } else {
          anchor.classList.remove('active');
        }
      }

      previousScrollPosition = window.scrollY;
    });

    // Set click event of anchors
    for (let [anchor, anchorTarget] of anchorTargets) {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();

        // Compensate for height of navbar so content appears below it
        let scrollBy =
          anchorTarget.getBoundingClientRect().top - navbar.offsetHeight - offset;

        // If window hasn't been scrolled, compensate for header shrinking.
        const approximateHeaderSize = 65;
        if (window.scrollY === 0) scrollBy += approximateHeaderSize;

        // If navbar hasn't been stickied yet, that means global header is still in view, so compensate for header height
        if (!navbar.classList.contains('uds-anchor-menu-sticky')) {
          if (window.scrollY > 0) scrollBy += 24;
          scrollBy -= globalHeader.offsetHeight;
        }

        // Set in mobile version
        if(isMobile && showStickyOnMobile()) {
          scrollBy = anchorTarget.getBoundingClientRect().top - navbar.offsetHeight - 180;
        }

        // Set in medium devices [610, 992]
        if(isMedium) {
          scrollBy = anchorTarget.getBoundingClientRect().top - 32;
        }

        window.scrollBy({
          top: scrollBy,
          behavior: 'smooth',
        });

        // Remove active class from other anchor in navbar, and add it to the clicked anchor
        const active = navbar.querySelector('.nav-link.active');

        if (active) active.classList.remove('active');

        e.target.classList.add('active');

      });
    }
  }

  function showStickyOnMobile() {
    // Should be shown sticky in mobile only if it contains a 'request' or 'apply' cta's
   return document.querySelectorAll('.nav .cta-apply, nav .cta-request').length > 0
  }

})(jQuery, Drupal, drupalSettings);