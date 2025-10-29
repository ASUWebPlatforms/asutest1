(function ($) {
    Drupal.behaviors.navigateToViewPage = {
      attach: function (context, settings) {
        if (settings.view_ajax_go_to) {
          settings.view_ajax_go_to.forEach(id => {
            $('#' + id, context).each((index, element) => {
              const pager = $(once('view-ajax-go-to', element)).closest('[class*="js-view-dom-id"]').find('.pagination');
              pager.append(getGoToItem(true));
            });
          });
        }
      }
    };

    $(document).on('input', '#ajax-go-to input', function () {
      const input = $(this);
      const newPage = input.val();
      if (newPage !== '') {
        $('#ajax-go-to a').remove();
        input.parent().append(getGoToItem(false, newPage - 1));
        const instances = Drupal.views.instances;
        let key = Object.keys(instances)[0];
        const view = instances[key];
        view.attachPagerAjax();
      }
    })

    $(document).on('keypress', '#ajax-go-to input', function (event) {
      if (event.key === "Enter" || event.keyCode === 13) {
        $(this).parent().find('.page-link').trigger('click');
      }
    })

    function getGoToItem(useCurrent = true, page) {
      const currentUrl = window.location.href;
      let url = new URL(currentUrl);
      if (!useCurrent) {
        url.searchParams.set('page', page);
        return `<a class="page-link page-link-icon" href="${ url.search }" >Go</a>`;
      }

      return `<li id="ajax-go-to" class="d-flex align-items-center ml-4 ajax-go-to">
        <label class="m-0 mr-2">Go to Page</label>
        <input class="form-control" type="number" value="${ useCurrent ? parseInt(url.searchParams.get('page')) + 1 : parseInt(url.searchParams.get('page'))}">
        <a class="page-link page-link-icon" href="${ url.search }" >Go</a>
      </li>`;
    }
  })(jQuery);
  
  