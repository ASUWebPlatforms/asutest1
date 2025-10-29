(function ($, Drupal) {
  const graphs = $('.circular-graph');
  const sWidth = window.innerWidth;
  graphs.each((index, canvas) => {
    initGdmiSpiderCircularGraph(canvas, [
      canvas.getAttribute('data-digital_advocacy'),
      canvas.getAttribute('data-digital_implementation'),
      canvas.getAttribute('data-growth_mindset'),
      canvas.getAttribute('data-intercultural_empathy'),
      canvas.getAttribute('data-interpersonal_impact'),
      canvas.getAttribute('data-diplomacy'),
      canvas.getAttribute('data-passion_for_diversity'),
      canvas.getAttribute('data-quest_for_adventure'),
      canvas.getAttribute('data-self_assurance'),
      canvas.getAttribute('data-global_business_savvy'),
      canvas.getAttribute('data-cosmopolitan_outlook'),
      canvas.getAttribute('data-cognitive_complexity'),
    ], sWidth);
  });
})(jQuery, Drupal);