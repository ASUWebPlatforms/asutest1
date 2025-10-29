(function ($, Drupal) {
  const graphs = $('.small-circular-graph');
  graphs.each((index, canvas) => {
    const val = canvas.getAttribute('data-value');
    const color = canvas.getAttribute('data-color');
    const capitalName = canvas.getAttribute('data-capital-name');
    const items = canvas.getAttribute('data-items').split(',');
    initGdmiSmallCircularGraph(canvas, val, items, capitalName, color);
  });
})(jQuery, Drupal);