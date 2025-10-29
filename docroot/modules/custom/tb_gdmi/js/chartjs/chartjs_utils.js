const capitalsLabels = {
  psychological: [['Passion for', 'Diversity'], ['Self-', 'Assurance'], ['Quest for', 'Adventure']],
  intellectual: [['Cognitive', 'Complexity'], ['Global', 'Business', 'Savvy'], ['Cosmopolitan', 'Outlook']],
  social: [['Intercultural', 'Empathy'], ['Diplomacy'], ['Interpersonal', 'Impact']],
  digital: [['Digital', 'Advocacy'], ['Growth', 'Mindset'], ['Digital', 'Implementation']],
};

const globalLabels = [
  ['Passion for', 'Diversity'], ['Self-', 'Assurance'], ['Quest for', 'Adventure'],
  ['Cognitive', 'Complexity'], ['Global', 'Business', 'Savvy'], ['Cosmopolitan', 'Outlook'],
  ['Intercultural', 'Empathy'], ['Diplomacy'], ['Interpersonal', 'Impact'], 
  ['Digital', 'Advocacy'],  ['Growth', 'Mindset'], ['Digital', 'Implementation']
];

function orderingObject(items) {
  let indexedNumbers = items.map((value, index) => ({ value, index }));
  indexedNumbers.sort((a, b) => b.value - a.value);
  let indexMapping = {};
  indexedNumbers.forEach((item, newIndex) => {
    indexMapping[item.index] = newIndex;
  });

  return [indexMapping, items.sort((a, b) => b - a)];
}

function orderBasedOnIndex(indexs, values) {
  let resultNumbers = [];
  for (let key in indexs) {
    let value = indexs[key];
    resultNumbers[value] = values[key];
  }
  return resultNumbers;
}