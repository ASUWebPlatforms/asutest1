(function ($) {
  const graphs = $('.horizontal-bars-graph');
  graphs.each((_, canvas) => {

    const mainColor = canvas.getAttribute('data-color');
    const capitalName = canvas.getAttribute('data-name');

    const score = (type = 'yourScore') => {
      const typesSelector = {
        yourScore: '-',
        groupMean: '-group-',
        grandMean: '-grand-mean-',
      }
      type = typesSelector[type];
      const inputs = {
        psychological: [
          canvas.getAttribute(`data${type}passion_for_diversity`) ?? 0,
          canvas.getAttribute(`data${type}self_assurance`) ?? 0,
          canvas.getAttribute(`data${type}quest_for_adventure`) ?? 0,
        ],
        intellectual: [
          canvas.getAttribute(`data${type}cognitive_complexity`) ?? 0,
          canvas.getAttribute(`data${type}global_business_savvy`) ?? 0,
          canvas.getAttribute(`data${type}cosmopolitan_outlook`) ?? 0
        ],
        social: [
          canvas.getAttribute(`data${type}intercultural_empathy`) ?? 0,
          canvas.getAttribute(`data${type}diplomacy`) ?? 0,
          canvas.getAttribute(`data${type}interpersonal_impact`) ?? 0
        ],
        digital: [
          canvas.getAttribute(`data${type}digital_advocacy`) ?? 0,
          canvas.getAttribute(`data${type}growth_mindset`) ?? 0,
          canvas.getAttribute(`data${type}digital_implementation`) ?? 0
        ]
      }

      return inputs[capitalName];
    };

    let group =  canvas.getAttribute(`data-is-group`) === '1' ? {
      date: canvas.getAttribute('data-date'), 
      completed: canvas.getAttribute('data-completed'), 
      total: canvas.getAttribute('data-total'),
    } : null;

    initGdmiHorizontalBarsGraph(canvas, capitalName, mainColor, { yourScore: score(), groupMean: score('groupMean'), grandMean: score('grandMean')}, group);
  });
})(jQuery);