(function ($) {
    Drupal.behaviors.tableActions = {
      attach: function (context, settings) {

        $(once('options-dropdowm', '.options-dropdown', context)).click(function(e){
            const element = $(this);
            const id = element.attr('data-id');
            const content = element.find('.dropdown-content');
            if (!content.hasClass('show')) {
              $('.dropdown-content').removeClass('show');
              
              let relative = $('#options-relative-' + id).get(0).getBoundingClientRect();
              let options = element.get(0).getBoundingClientRect();

              const left = (options.left - relative.left - 200) + (options.width / 2);
              const top = (options.top - relative.top) + options.height;

              content.css({'top': `${top}px`, 'left': `${left}px`});
              content.addClass('show');

            } else {
              content.removeClass('show');
            }
        });

        $(once('group-collapse-btn', '.group-collapse-btn', context)).click(function(e){
          const element = $(this);
          const group = element.closest('.manage-groups__group');
          const body = group.find('.manage-groups__group-body');
          body.toggleClass('collapse');
          element.find('svg').toggleClass('open');
          let text = body.hasClass('collapse') ? 'Open' : 'Collapse';
          element.find('span').text(text);
        });

        $(once('group-label', '.group-label', context)).click(function(e){
          // Close all others.
          const el = $(this).find('.group-label-options');
          let open = el.hasClass('collapse');
          if (open) {
            $('.group-label-options').addClass('collapse');
          }

          el.toggleClass('collapse');
        });

        // Modal
        once('btn-add-participants', document.body).forEach(element => {
          $.ajax({
            url: '/gdmi-modal',
            method: 'POST',
            data: {
              id: 'btn-add-participants',
              title: 'Add Participants',
              description: 'To add participants you will need to purchase more instances of the GDMI. Would you like to continue?',
              close_btn_text: 'Return to GDMI Hub',
              confirm_btn_text: 'Continue to Purchase',
            },
            success: function (data) {
              const modal =  document.createElement('div');
              modal.innerHTML = data.html;
              element.appendChild(modal);
            },
          });
        });

        $(once('btn-add-participants', '.btn-add-participants', context)).click(function(e){
          $('#modal-overlay-btn-add-participants').addClass('show');
          $('#modal-btn-add-participants').addClass('show');
          $('#modal-btn-add-participants').attr('data-group', $(this).attr('data-group'));
        });

        $(context).on('click', '#modal-overlay-btn-add-participants, #close-modal-btn-add-participants', function () {
          $('#modal-btn-add-participants').removeClass('show');
          $('#modal-overlay-btn-add-participants').removeClass('show');
        });

        $(context).on('click', '#confirm-modal-btn-add-participants', function (e) {
          e.preventDefault();
          const modal = $(this).closest('.gdmi-modal');
          $('.form-add-participants-' + modal.attr('data-group')).submit();
        });
      },
    };
  })(jQuery);