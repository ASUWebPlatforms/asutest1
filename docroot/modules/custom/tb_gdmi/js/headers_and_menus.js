(function ($) {
    Drupal.behaviors.headersAndMenus = {
      attach: function (context, settings) {
        const id = settings.headersAndMenusModal?.btnId;

        if (id === 'cancel-purchase-btn') {

          once(id, document.body).forEach(element => {
            $.ajax({
              url: '/gdmi-modal',
              method: 'POST',
              data: {
                id: 'cancel-purchase-btn',
                type: 'cancel-purchase',
                title: 'Cancel Purchase',
                description: 'Are you sure you would like to cancel?',
                close_btn_text: 'Return to purchase',
                confirm_btn_text: 'Cancel purchase',
              },
              success: function (data) {
                const modal =  document.createElement('div');
                modal.innerHTML = data.html;
                element.appendChild(modal);
              },
            });
          });

          $('#' + id , context).click(function(e){
              e.preventDefault();
              $('#modal-overlay-'+ id).addClass('show');
              $('#modal-'+ id).addClass('show');
          });

          $(context).on('click', '#modal-overlay-' + id + ', #close-modal-' + id, function () {
            $('#modal-'+ id).removeClass('show');
            $('#modal-overlay-'+ id).removeClass('show');
          });

        }
        
      },
    };

    // Sticky on scroll.
    const gdmiNav = $('.gdmi-headers-and-menus').get(0);
    let previousScrollPosition = window.scrollY;
    const navbarInitialPosition = gdmiNav.getBoundingClientRect().top + window.scrollY;

    window.addEventListener("scroll", function () {
      const navbarY = gdmiNav.getBoundingClientRect().top;
      const headerHeight = gdmiNav.offsetHeight;
      const offset = 28;

      // If scrolling DOWN
      if (window.scrollY > previousScrollPosition) {
        if (navbarY < (headerHeight + offset) && !gdmiNav.classList.contains('scrolled')) {
          gdmiNav.classList.add('scrolled');
        } 
      }

      // If scrolling UP
      if (window.scrollY < previousScrollPosition && window.scrollY + headerHeight  < (navbarInitialPosition)) {
        gdmiNav.classList.remove('scrolled');
      }

      previousScrollPosition = window.scrollY;
    });

  })(jQuery);