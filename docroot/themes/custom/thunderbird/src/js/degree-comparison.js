(function($, Drupal) {
  // Initialize carousel
  Drupal.behaviors.degreeComparison = {
    attach: function (context) {
      var viewClass = 'degree-comparison';
      var view = ($(context).hasClass(viewClass)) ? $(context) : $('.' + viewClass, context);

      // Only load behavior once (or per view refresh)
      if (view.length > 0) {
        $(once('init-degree-comparison', view)).each(function(index, element) {
          var containers = view.find('.table-responsive .container');
          
          // Loop through each table and update columns
          containers.each(function(tableIndex, tableElement) {
            var table = $(tableElement).find('table');
            var tableBody = table.find('tbody tr');

            // Loop through each body row
            tableBody.each(function(rowIndex, rowElement) {
              var row = $(rowElement);
              var color = rowIndex % 4 > 1 ? 'gray' : 'white'; // white, white, gray, gray
              var type = rowIndex % 2 == 0 ? 'top' : 'bottom'

              // Update type and color of each row
              row.addClass('row-color-' + color + ' row-type-' + type);
            });

            // Check if container needs shadows
            updateScrollState(containers);
          });

          // Add toggle listener
          $('.' + viewClass + ' table td .icon').on('click', function() {
            var rowTop = $(this).closest('.row-type-top');
            var rowBottom = rowTop.next('.row-type-bottom');
            
            // Toggle class state
            rowTop.toggleClass('show');
            rowBottom.toggleClass('show');
          });

          // Scroll listener
          view.find('.table-responsive .container').scroll(function() {
            updateScrollState($(this));
          });

          // Apply shadow class according to scroll proximity
          function updateScrollState(elem) {
            var left = Math.floor(elem.scrollLeft());
            var right = Math.floor(elem.children().first().width() - (left + elem.width()));

            // Update left shadow
            if (left > 0) elem.parent().addClass('shadow-left')
            else elem.parent().removeClass('shadow-left');
            
            // Update right shadow
            if (right > 0) elem.parent().addClass('shadow-right')
            else elem.parent().removeClass('shadow-right');
          }
        });
      }
    }
  };
})(jQuery, Drupal);