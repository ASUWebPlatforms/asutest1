const PADDINGY = 14;
const PADDINGX = 14;
const PAGE_WIDTH = 210;
const PAGE_HEIGHT = 297;
const TEXT_MAX_WIDTH = PAGE_WIDTH - (PADDINGX * 2);
const DEFAULT_GRAPH_OPTIONS = {
  responsive: false,
  animation: {
    duration: 0,
  }
};

const COLORS = {
  blueDark: '#002E5F',
  black: '#333333',
  white: '#FFFFFF',
  psychological: '#E43D51',
  psychologicalLight: [252, 219, 219],
  social: '#E69E00',
  socialLight: [249, 232, 199],
  intellectual: '#753E96',
  intellectualLight: [230, 211, 225],
  digital: '#0179B7',
  digitalLight: [197, 224, 238],
  greenLight: [202, 228, 211],
  grayLight: '#DCDCDC',
};

function addReportsGlobalCanvas() {
  const canvas = document.createElement('canvas');
  canvas.id = 'reports-canvas';
  canvas.classList.add('d-none');
  document.body.appendChild(canvas);
}

function reportsGlobalCanvasSetSize(width, height) {
  const canvas = document.getElementById('reports-canvas');
  canvas.width = width;
  canvas.height = height;
}

function removeReportsGlobalCanvas() {
  document.body.removeChild(document.getElementById('reports-canvas'));
}

function writeText(doc, text, x, y, size, font = 'normal', weight = '400', color = COLORS.blueDark, position = 'left', maxWidth = TEXT_MAX_WIDTH) {
  doc.setFontSize(size);
  doc.setTextColor(color);
  doc.setFont('PublicSans', font, weight);
  doc.text(text + '', x, y, { maxWidth, lineHeightFactor: 1.5, align: position });
}

function addPagesHeader(doc, data) {
  let totalPages = doc.internal.getNumberOfPages();
  for (let i = 2; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setFont('PublicSans', 'bold', 600);
    doc.setFontSize(10);
    doc.setTextColor(COLORS.blueDark);
    doc.text(data.user, PADDINGX, PADDINGY);
    doc.text(data.date, PAGE_WIDTH - PADDINGX, PADDINGY, null, null, 'right');
  }
}

function addPagesFooter(doc) {
  let totalPages = doc.internal.getNumberOfPages();
  for (let i = 1; i <= totalPages; i++) {
    doc.setPage(i);
    doc.setLineWidth(0.5);
    doc.setDrawColor(COLORS.blueDark);
    doc.line(PADDINGX, PAGE_HEIGHT - PADDINGY, PAGE_WIDTH - PADDINGX, PAGE_HEIGHT - PADDINGY);
    doc.setTextColor(COLORS.blueDark);
    doc.setFont('PublicSans', 'bold', 600);
    doc.setFontSize(10);
    doc.text('globaldigitalmindset@thunderbird.asu.edu', PADDINGX, PAGE_HEIGHT - PADDINGY - 3, null, null, 'left');
    doc.text('www.globalmindset.com', PAGE_WIDTH - PADDINGX, PAGE_HEIGHT - PADDINGY - 3, null, null, 'right');
    doc.setFontSize(8);
    doc.text('© 2024 Thunderbird School of Global Management. All Rights Reserved.', PADDINGX, PAGE_HEIGHT - PADDINGY + 5, null, null, 'left');
    doc.text(`${i}/${totalPages}`, PAGE_WIDTH - PADDINGX, PAGE_HEIGHT - PADDINGY + 5, null, null, 'right');
  }
}

function  printList(doc, title, items, y, gap) {
  writeText(doc, title, PADDINGX, y, 18, 'bold', 900, COLORS.blueDark);
  for (let i = 0; i < items.length; i++) {
    let newY = y + ((i + 1) * gap);
    const isList = Array.isArray(items[i]);
    newY = isList ? newY + items[i][1] : newY;
    const text = isList ? items[i][0] : items[i];
    doc.ellipse(PADDINGX + 5, (newY - 1), 0.5, 0.5, 'F');
    writeText(doc, text, PADDINGX + 10, newY, 11, 'normal', 400, COLORS.black);
  }
}

function addQuestionLines(doc, y, gap, quantity) {
  for (let i = 0; i < quantity; i++) {
    let newY = ((i + 1) * gap) + y;
    doc.line(PADDINGX, newY, PAGE_WIDTH - PADDINGX, newY);
  }
}

function yourDetailedProfileChart(doc, data, groupInfo) {
  const datasets = [
    {
      label: 'YOUR SCORE',
      data: [
        data.user_score.psychological_capital.items.passion_for_diversity.average, 
        data.user_score.psychological_capital.items.self_assurance.average,
        data.user_score.psychological_capital.items.quest_for_adventure.average,
        data.user_score.intellectual_capital.items.cognitive_complexity.average,
        data.user_score.intellectual_capital.items.global_business_savvy.average,
        data.user_score.intellectual_capital.items.cosmopolitan_outlook.average,
        data.user_score.social_capital.items.intercultural_empathy.average,
        data.user_score.social_capital.items.diplomacy.average,
        data.user_score.social_capital.items.interpersonal_impact.average,
        data.user_score.digital_capital?.items?.digital_advocacy?.average ?? 0,
        data.user_score.digital_capital?.items?.growth_mindset?.average ?? 0,
        data.user_score.digital_capital?.items?.digital_implementation?.average ?? 0
      ],
      backgroundColor: function(context, options) {
        let color = COLORS.psychological;
        if (context.index > 2) {
           color = COLORS.social;
        }
        if (context.index > 5) {
          color = COLORS.intellectual;
        }
        if (context.index > 8) {
          color = COLORS.digital;
        }
        return color;
      },
    },
    {
      label: 'RANGE OF MIN AND MAX VALUES FOR THE GROUP',
      data: [
        data.group_mean?.means?.psychological_capital?.items?.passion_for_diversity?.average ?? 0,
        data.group_mean?.means?.psychological_capital?.items?.self_assurance?.average ?? 0,
        data.group_mean?.means?.psychological_capital?.items?.quest_for_adventure?.average ?? 0,
        data.group_mean?.means?.intellectual_capital?.items?.cognitive_complexity?.average ?? 0,
        data.group_mean?.means?.intellectual_capital?.items?.global_business_savvy?.average ?? 0,
        data.group_mean?.means?.intellectual_capital?.items?.cosmopolitan_outlook?.average ?? 0,
        data.group_mean?.means?.social_capital?.items?.intercultural_empathy?.average ?? 0,
        data.group_mean?.means?.social_capital?.items?.diplomacy?.average ?? 0,
        data.group_mean?.means?.social_capital?.items?.interpersonal_impact?.average ?? 0,
        data.group_mean?.means?.digital_capital?.items?.digital_advocacy?.average ?? 0,
        data.group_mean?.means?.digital_capital?.items?.growth_mindset?.average ?? 0,
        data.group_mean?.means?.digital_capital?.items?.digital_implementation?.average ?? 0
      ],
      backgroundColor: '#B4B4B4',
    },
  ];

  const indicators = [
    {
      color: '#B4B4B4',
      label: 'RANGE OF MIN AND MAX VALUES FOR THE GROUP'
    },
    {
      color: COLORS.psychological,
      label: 'GLOBAL PSYCHOLOGICAL CAPITAL'
    },
    {
      color: COLORS.social,
      label: 'GLOBAL SOCIAL CAPITAL'
    },
    {
      color: COLORS.intellectual,
      label: 'GLOBAL INTELLECTUAL CAPITAL'
    },
    {
      color: COLORS.digital,
      label: 'GLOBAL DIGITAL CAPITAL'
    },
  ];

  reportsGlobalCanvasSetSize(969, 1700);
  let canvas = document.getElementById('reports-canvas').getContext('2d');
  chart = initGdmiHorizontalGlobalBarsGraph(canvas, datasets, indicators, groupInfo);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG',  PADDINGX + 10, 30, PAGE_WIDTH - 50, PAGE_HEIGHT - 40, undefined, 'FAST');
  chart.destroy();
}

function groupDetailedProfileChart(doc, data, groupInfo) {
  const datasets = [
    {
      label: 'GROUP MEAN',
      data: [
        data.group_mean?.means?.psychological_capital?.items?.passion_for_diversity?.average ?? 0,
        data.group_mean?.means?.psychological_capital?.items?.self_assurance?.average ?? 0,
        data.group_mean?.means?.psychological_capital?.items?.quest_for_adventure?.average ?? 0,
        data.group_mean?.means?.intellectual_capital?.items?.cognitive_complexity?.average ?? 0,
        data.group_mean?.means?.intellectual_capital?.items?.global_business_savvy?.average ?? 0,
        data.group_mean?.means?.intellectual_capital?.items?.cosmopolitan_outlook?.average ?? 0,
        data.group_mean?.means?.social_capital?.items?.intercultural_empathy?.average ?? 0,
        data.group_mean?.means?.social_capital?.items?.diplomacy?.average ?? 0,
        data.group_mean?.means?.social_capital?.items?.interpersonal_impact?.average ?? 0,
        data.group_mean?.means?.digital_capital?.items?.digital_advocacy?.average ?? 0,
        data.group_mean?.means?.digital_capital?.items?.growth_mindset?.average ?? 0,
        data.group_mean?.means?.digital_capital?.items?.digital_implementation?.average ?? 0
      ],
      backgroundColor: '#B4B4B4',
    },
    {
      label: 'GRAND MEAN',
      data: [
        data.grand_mean?.means?.psychological_capital?.items?.passion_for_diversity?.average ?? 0,
        data.grand_mean?.means?.psychological_capital?.items?.self_assurance?.average ?? 0,
        data.grand_mean?.means?.psychological_capital?.items?.quest_for_adventure?.average ?? 0,
        data.grand_mean?.means?.intellectual_capital?.items?.cognitive_complexity?.average ?? 0,
        data.grand_mean?.means?.intellectual_capital?.items?.global_business_savvy?.average ?? 0,
        data.grand_mean?.means?.intellectual_capital?.items?.cosmopolitan_outlook?.average ?? 0,
        data.grand_mean?.means?.social_capital?.items?.intercultural_empathy?.average ?? 0,
        data.grand_mean?.means?.social_capital?.items?.diplomacy?.average ?? 0,
        data.grand_mean?.means?.social_capital?.items?.interpersonal_impact?.average ?? 0,
        data.grand_mean?.means?.digital_capital?.items?.digital_advocacy?.average ?? 0,
        data.grand_mean?.means?.digital_capital?.items?.growth_mindset?.average ?? 0,
        data.grand_mean?.means?.digital_capital?.items?.digital_implementation?.average ?? 0
      ],
      backgroundColor: '#004467',
    },
  ];

  reportsGlobalCanvasSetSize(969, 1700);
  let canvas = document.getElementById('reports-canvas').getContext('2d');
  chart = initGdmiHorizontalGlobalBarsGraph(canvas, datasets, [], groupInfo);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG',  PADDINGX + 10, 50, PAGE_WIDTH - 50, PAGE_HEIGHT - 50, undefined, 'FAST');
  chart.destroy();
}

function drawScoresSummaryTable(doc, data) {
  
  let groupMeansX1 = 90;
  let groupMeansX2 = 145;
  let grandMeansX1 = 145;
  let grandMeansX2 = 200;

  if (!data.group_mean) {
    grandMeansX1 = groupMeansX1;
    grandMeansX2 = groupMeansX2;
  }

  // COLORS
  doc.setFillColor(...COLORS.greenLight);
  doc.rect(60, 40, 30, 25, 'F');
  writeText(doc, 'Your Scores', 75, 53, 10, 'bold', 900, COLORS.black, 'center');

  drawSummaryTableMeanRaw(doc, 15, 65, 45, COLORS.intellectual, COLORS.intellectualLight, ['Global Intellectual Capital', 'Cognitive Complexity', 'Cosmopolitan Outlook', 'Global Business Savvy'], data.user_score.intellectual_capital);
  drawSummaryTableMeanRaw(doc, 15, 112, 45, COLORS.digital, COLORS.digitalLight, ['Global Digital Capital', 'Digital Advocacy', 'Digital Implementation', 'Growth Mindset'], data.user_score.digital_capital);
  drawSummaryTableMeanRaw(doc, 15, 159, 45, COLORS.psychological, COLORS.psychologicalLight, ['Global Psychological Capital ', 'Self-Assurance', 'Quest for Adventure', 'Passion for Diversity'], data.user_score.psychological_capital);
  drawSummaryTableMeanRaw(doc, 15, 206, 45, COLORS.social, COLORS.socialLight, ['Global Social Capital', 'Intercultural Empathy', 'Diplomacy', 'Interpersonal Impact'], data.user_score.social_capital, 35);

  if (data.group_mean) {
    drawSummaryTableMeanColumn(doc, groupMeansX1, groupMeansX2, 'Group Scores', data.group_mean);
  }
  drawSummaryTableMeanColumn(doc, grandMeansX1, grandMeansX2, 'Grand Mean Scores', data.grand_mean);

  // LINES AND RECTS
  doc.setLineWidth(0.5);
  doc.rect(15, 65, 45, 188.25);
  doc.line(60, 65, 90, 65);
  doc.line(15, 112, 90, 112);
  doc.line(15, 159, 90, 159);
  doc.line(15, 206, 90, 206);
  doc.setLineWidth(1);
  doc.rect(60, 40, 29.5, 213);
}

function drawSummaryTableMeanRaw(doc, x, y, w, color, colorLight, labels, scores, maxWidth = 37) {
  doc.setFillColor(color);
  doc.rect(x, y, w, 11.75, 'F');
  doc.setFillColor(...colorLight);
  doc.rect(x, y + 11.75, w, 35.25, 'F');
  for (let i = 0; i < labels.length; i++) {
    let newY =  y + (i === 0 ? 5 : (i * 10) + 10);
    let textColor = i === 0 ? COLORS.white : COLORS.black;
    let weight = i === 0 ? 900 : 600;
    maxWidth = i === 0 ? maxWidth : TEXT_MAX_WIDTH;
    writeText(doc, labels[i], x + (w/2), newY, 10, 'bold', weight, textColor, 'center', maxWidth);
    let key = labels[i].toLowerCase().replaceAll(' ', '_').replaceAll('-', '_');
    let value = i === 0 ? scores?.average ?? 0 : scores?.items[key].average ?? 0;
    writeText(doc, value, x + 60, newY, 10, 'bold', 900, COLORS.black, 'center');
  }
}

function drawSummaryTableMeanColumn(doc, x1, x2, title, data) {
  doc.setLineWidth(0.5);
  doc.setFillColor(COLORS.grayLight);
  doc.rect(x1, 40, 55, 8, 'F');
  doc.rect(x1, 40, 55, 213);
  doc.line(x1, 65, x2, 65);
  doc.line(x1, 112, x2, 112);
  doc.line(x1, 159, x2, 159);
  doc.line(x1, 206, x2, 206);
  doc.line(x1 + 12, 48, x1 + 12, 253);
  writeText(doc, title, x1 + 27.5, 45, 10, 'bold', 600, COLORS.black, 'center');
  writeText(doc, 'Mean', x1 + 6, 63, 9, 'bold', 900, COLORS.black, 'center');
  writeText(doc, 'Percentiles', x1 + 33.5, 54, 9, 'bold', 900, COLORS.black, 'center');
  writeText(doc, 'Max', x1 + 17, 63, 9, 'bold', 600, COLORS.black, 'center');
  writeText(doc, '75th', x1 + 25, 63, 9, 'bold', 600, COLORS.black, 'center');
  writeText(doc, '50th', x1 + 34, 63, 9, 'bold', 600, COLORS.black, 'center');
  writeText(doc, '25th', x1 + 42, 63, 9, 'bold', 600, COLORS.black, 'center');
  writeText(doc, 'Min', x1 + 50, 63, 9, 'bold', 600, COLORS.black, 'center');

  let positions = [72, 119, 166, 213];
  let capitals = {
    intellectual_capital: ['cognitive_complexity', 'cosmopolitan_outlook', 'global_business_savvy'],
    digital_capital: ['digital_advocacy', 'digital_implementation', 'growth_mindset'], 
    psychological_capital: ['self_assurance', 'quest_for_adventure', 'passion_for_diversity'], 
    social_capital: ['intercultural_empathy', 'diplomacy', 'interpersonal_impact']
  };

  let keys = Object.keys(capitals);
  for (let i = 0; i < keys.length; i++) {
    let key = keys[i];
    let average = data.means[key]?.average ?? 0;
    writeText(doc, average, x1 + 6, positions[i], 9, 'bold', 900, COLORS.black, 'center');
    for (let j = 0; j < capitals[key]?.length ?? []; j++) {
      average = data.means[key]?.items[capitals[key][j]].average ?? 0;
      writeText(doc, average, x1 + 6,  (j * 10) + 13 + positions[i], 9, 'bold', 900, COLORS.black, 'center');
    }
  }

  for (let i = 0; i < keys.length; i++) {
    let key = keys[i];
    writeText(doc, (data.percentiles[key]?.max ?? 0), x1 + 17, positions[i], 9, 'bold', 600, COLORS.black, 'center');
    writeText(doc, (data.percentiles[key]?.['25'] ?? 0), x1 + 25, positions[i], 9, 'bold', 600, COLORS.black, 'center');
    writeText(doc, (data.percentiles[key]?.['50'] ?? 0), x1 + 34, positions[i], 9, 'bold', 600, COLORS.black, 'center');
    writeText(doc, (data.percentiles[key]?.['75'] ?? 0), x1 + 42, positions[i], 9, 'bold', 600, COLORS.black, 'center');
    writeText(doc, (data.percentiles[key]?.min ?? 0), x1 + 50, positions[i], 9, 'bold', 600, COLORS.black, 'center');
    for (let j = 0; j < capitals[key]?.length ?? []; j++) {
      writeText(doc, (data.percentiles[key]?.items[capitals[key][j]].max ?? 0), x1 + 17,  (j * 10) + 13 + positions[i], 9, 'bold', 600, COLORS.black, 'center');
      writeText(doc, (data.percentiles[key]?.items[capitals[key][j]]['25'] ?? 0), x1 + 25,  (j * 10) + 13 + positions[i], 9, 'bold', 600, COLORS.black, 'center');
      writeText(doc, (data.percentiles[key]?.items[capitals[key][j]]['50'] ?? 0), x1 + 34,  (j * 10) + 13 + positions[i], 9, 'bold', 600, COLORS.black, 'center');
      writeText(doc, (data.percentiles[key]?.items[capitals[key][j]]['75'] ?? 0), x1 + 42,  (j * 10) + 13 + positions[i], 9, 'bold', 600, COLORS.black, 'center');
      writeText(doc, (data.percentiles[key]?.items[capitals[key][j]].min ?? 0), x1 + 50,  (j * 10) + 13 + positions[i], 9, 'bold', 600, COLORS.black, 'center');
    }
  }

}

function saveReportEntity(doc, filename, sid) {
  const blob = doc.output('blob');
  const formData = new FormData();
  formData.append('pdf', blob, filename);
  formData.append('sid', sid);
  jQuery.ajax({
    url: `/dashboard/reports/save`,
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function (data) {
      document.querySelector('.ajax-progress-fullscreen').remove();
      window.location.reload();
    },
    error: function (jqXHR, textStatus) {
      console.error(textStatus);
    }
  });
}

function generateStandardReport(data, download = false) {

  const doc = new jsPDF({ compress: true });

  // COVER PAGE.
  doc.addImage('/themes/custom/thunderbird/assets/img/gdmi/reports/gdmi-new-cover-page.png', 'PNG', (PAGE_WIDTH / 2) - 75, 15, 160, 30, undefined, 'FAST');
  doc.addImage('/themes/custom/thunderbird/assets/img/gdmi/reports/gdmi-structure-globe-01.png', 'PNG', (PAGE_WIDTH / 2) - 62.5, 60, 125, 160, undefined, 'FAST');
  
  writeText(doc, data.user, 105, 235, 16, 'bold', 600, COLORS.blueDark, 'center');
  writeText(doc, data.date, 105, 242, 12, 'bold', 600, COLORS.blueDark, 'center');
  writeText(doc, 'globalmindset.com', 105, 255, 20, 'bold', 600, COLORS.blueDark, 'center');

  // PAGE 2.
  doc.addPage();
  doc.setFillColor(COLORS.black);

  writeText(doc, 'What is Global Digital Mindset?', PADDINGX, 30, 24, 'bold', 900);
  writeText(doc, 'Today’s organizations are experiencing two interrelated and mutually reinforcing mega trends: Globalization, and digital transformation. The confluence of global and digital business reflects the opportunities offered by advancements in new technologies and increasing availability of global markets. Success in such an environment requires the ability to work effectively with diverse individuals and groups, as well as the ability to work effectively in the context of digital transformation.', PADDINGX, 40, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'A global digital mindset is a set of attitudes, skills, and behaviors that enable individuals and organizations to effectively navigate and leverage the complexities of a global digital environment.', PADDINGX, 80, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'A strong global digital mindset helps improve leadership of digital transformation in organizations around the globe.', PADDINGX, 92, 11, 'normal', 400, COLORS.black);
  
  writeText(doc, 'What is the Structure of Global Digital Mindset?', PADDINGX, 120, 24, 'bold', 900);
  writeText(doc, 'Global Digital Mindset is a constellation of four critical dimensions: global intellectual capital, global digital capital, global social capital, and global psychological capital. Global Digital Mindset Inventory (GDMI) is scientifically designed to measure your global digital mindset in terms of these four dimensions.', PADDINGX, 145, 11, 'normal', 400, COLORS.black);
  doc.addImage('/themes/custom/thunderbird/assets/img/gdmi/reports/gdmi-structure-globe-01.png', 'PNG', (PAGE_WIDTH / 2) - 45, 165, 90, 110, undefined, 'FAST');
  
  // PAGE 3.
  doc.addPage();
  doc.setFillColor(COLORS.intellectual);
  doc.rect(PADDINGX, 20, 52, 6, 'F');
  writeText(doc, 'Global Intellectual Capital', PADDINGX + 1, 24, 11, 'bold', 900, COLORS.white);
  writeText(doc, 'refers to your knowledge and understanding of global business. It', PADDINGX + 53, 24, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'reflects three important elements:', PADDINGX, 30, 11, 'normal', 400, COLORS.black);
  writeText(doc, '1. Global Business Savvy:', PADDINGX, 38, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your knowledge of global industry, global strategies, international', PADDINGX + 49, 38, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'business, and global supplier options.', PADDINGX, 44, 11, 'normal', 400, COLORS.black);
  writeText(doc, '2. Cosmopolitan Outlook:', PADDINGX, 50, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your up-to-date knowledge of cultures, economic and political issues,', PADDINGX + 49, 50, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'and histories of diverse countries.', PADDINGX, 56, 11, 'normal', 400, COLORS.black);
  writeText(doc, '3. Cognitive Complexity:', PADDINGX, 62, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your mental structure and your ability to understand and communicate', PADDINGX + 49, 62, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'complex and abstract concepts.', PADDINGX, 68, 11, 'normal', 400, COLORS.black);

  doc.setFillColor(COLORS.digital);
  doc.rect(PADDINGX, 76, 43, 6, 'F');
  writeText(doc, 'Global Digital Capital', PADDINGX + 1, 80, 11, 'bold', 900, COLORS.white);
  writeText(doc, 'refers to your understanding of the logic, implications, processes, and', PADDINGX + 44, 80, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'consequences of digital transformation. It has three elements:', PADDINGX, 86, 11, 'normal', 400, COLORS.black);
  writeText(doc, '1. Digital Advocacy:', PADDINGX, 94, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your knowledge of the opportunities and challenges created by digital', PADDINGX + 38, 94, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'transformation and your ability to communicate an effective vision of digital transformation.', PADDINGX, 100, 11, 'normal', 400, COLORS.black);
  writeText(doc, '2. Digital Implementation:', PADDINGX, 107, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your knowledge of the roles played by different groups in digital', PADDINGX + 51, 107, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'transformation and your ability to mobilize the workforce to embrace digital transformation.', PADDINGX, 113, 11, 'normal', 400, COLORS.black);
  writeText(doc, '3. Growth Mindset:', PADDINGX, 121, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your belief in employees’ learning capacity and your ability to support', PADDINGX + 38, 121, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'employees’ continuous learning and creating a high trust environment.', PADDINGX, 127, 11, 'normal', 400, COLORS.black);

  doc.setFillColor(COLORS.social);
  doc.rect(PADDINGX, 136, 43, 6, 'F');
  writeText(doc, 'Global Social Capital', PADDINGX + 1, 140, 11, 'bold', 900, COLORS.white);
  writeText(doc, 'refers to your behavioral style in interacting with people from other parts of the', PADDINGX + 44, 140, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'world. It consists of three elements', PADDINGX, 146, 11, 'normal', 400, COLORS.black);
  writeText(doc, '1. Intercultural Empathy:', PADDINGX, 154, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your ability to communicate and emotionally connect with people from', PADDINGX + 48, 154, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'pother parts of the world.', PADDINGX, 160, 11, 'normal', 400, COLORS.black);
  writeText(doc, '2. Interpersonal Impact:', PADDINGX, 167, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your experience in negotiating with people in other cultures, and your ability to', PADDINGX + 47, 167, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'build personal and professional networks with people in other countries.', PADDINGX, 173, 11, 'normal', 400, COLORS.black);
  writeText(doc, '3. Diplomacy:', PADDINGX, 181, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your ability to be a good listener, to collaborate, and to create agreement among', PADDINGX + 27, 181, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'diverse and divergent views.', PADDINGX, 187, 11, 'normal', 400, COLORS.black);

  doc.setFillColor(COLORS.psychological);
  doc.rect(PADDINGX, 195, 57, 6, 'F');
  writeText(doc, 'Global Psychological Capital', PADDINGX + 1, 199, 11, 'bold', 900, COLORS.white);
  writeText(doc, 'refers to your affective frame and your emotional reaction to global', PADDINGX + 58, 199, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'digital business. It consists of three important elements:', PADDINGX, 205, 11, 'normal', 400, COLORS.black);
  writeText(doc, '1. Passion for Diversity:', PADDINGX, 213, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'The extent you enjoy traveling to, and living in different countries, and the extent', PADDINGX + 45, 213, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'to which you enjoy exploring and getting to know people in other parts of the world.', PADDINGX, 219, 11, 'normal', 400, COLORS.black);
  writeText(doc, '2. Quest for Adventure:', PADDINGX, 225, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your willingness to test and push your abilities, take risks, and deal with', PADDINGX + 45, 225, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'challenging and unpredictable situations.', PADDINGX, 231, 11, 'normal', 400, COLORS.black);
  writeText(doc, '3. Self-assurance:', PADDINGX, 237, 11, 'bold', 900, COLORS.black);
  writeText(doc, 'Your energy and self-confidence level, and your level of comfort in', PADDINGX + 36, 237, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'uncomfortable and tough situations.', PADDINGX, 243, 11, 'normal', 400, COLORS.black);
  
  // PAGE 4.   
  doc.addPage();
  writeText(doc, 'Can Global Digital Mindset be developed?', PADDINGX, 30, 24, 'bold', 900);
  writeText(doc, 'Yes!', PADDINGX, 40, 14, 'bold', 900, COLORS.black);
  writeText(doc, 'All elements of Global Digital Mindset can be developed and improved. However, research shows', PADDINGX + 12, 40, 11, 'normal', 400, COLORS.black);
  writeText(doc, "that some elements are easier to develop than others. In our work with thousands of managers and executives, we have found that global intellectual capital is relatively easier to develop since all its elements are cognitively based. Global psychological capital is a cumulative concept that evolves over the years and is influenced by your personality, life experiences, and work experiences. As a result, it takes time, new experiences, and coaching, and will take time to improve. Global social capital and global digital capital require experience and feedback. To improve, you need to engage in a wide variety of actions and experiences and achieve improvements over time.", PADDINGX, 46, 11, 'normal', 400, COLORS.black);
  writeText(doc, 'About This Report', PADDINGX, 100, 24, 'bold', 900);
  writeText(doc, "This report is designed to provide you with feedback on your approach in helping lead digital transformation, and your effectiveness in working in global environments and with people from other cultural and geographic backgrounds. The purpose of this feedback report is to help you find ways of improving your ability to lead digital transformation and work with people from diverse cultural settings. The report provides feedback on your profile of Global Digital Mindset. It is based on the Thunderbird Global Digital Mindset Inventory (GDMI), a scientifically based instrument with strong scientific properties.", PADDINGX, 110, 11, 'normal', 400, COLORS.black);

  writeText(doc, 'Table of Contents', PADDINGX, 180, 24, 'bold', 900);
  writeText(doc, '05', PADDINGX, 195, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Global Digital Mindset', PADDINGX + 20, 194, 9, 'normal', 400, COLORS.black);
  writeText(doc, '06', PADDINGX, 210, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Your Global Digital Mindset Profile', PADDINGX + 20, 209, 9, 'normal', 400, COLORS.black);
  writeText(doc, '07', PADDINGX, 225, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Your Global Intellectual Capital Profile', PADDINGX + 20, 224, 9, 'normal', 400, COLORS.black);
  writeText(doc, '08', PADDINGX, 240, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Your Global Digital Capital Profile', PADDINGX + 20, 239, 9, 'normal', 400, COLORS.black);
  writeText(doc, '09', PADDINGX, 255, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Your Global Social Capital Profile', PADDINGX + 20, 254, 9, 'normal', 400, COLORS.black);
  writeText(doc, '10', PADDINGX + 90, 195, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Your Global Psychological Capital Profile', PADDINGX + 110, 194, 9, 'normal', 400, COLORS.black);
  writeText(doc, '11', PADDINGX + 90, 210, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Your Detailed Global Digital Mindset Profile', PADDINGX + 110, 209, 9, 'normal', 400, COLORS.black);
  writeText(doc, '12-13', PADDINGX + 90, 225, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Group average scores', PADDINGX + 110, 224, 9, 'normal', 400, COLORS.black);
  writeText(doc, '14-16', PADDINGX + 90, 240, 18, 'bold', 900, COLORS.digital);
  writeText(doc, 'Personal Observations and Development Planning', PADDINGX + 110, 239, 9, 'normal', 400, COLORS.black);

  // PAGE 5.   
  doc.addPage();
  doc.addImage('/themes/custom/thunderbird/assets/img/gdmi/reports/global-digital-mindset-structure-01.png', 'PNG', (PAGE_WIDTH / 2) - 90, 50, 180, 90, undefined, 'FAST');

  // PAGE 6.
  doc.addPage();
  writeText(doc, 'Your Global Digital Mindset Profile', PAGE_WIDTH/2, 40, 24, 'bold', 900, COLORS.blueDark, 'center');

  addReportsGlobalCanvas();
  reportsGlobalCanvasSetSize(800, 800);
  let canvas = document.getElementById('reports-canvas').getContext('2d');
  
  let chart = initGdmiSpiderCircularGraph(canvas, [
    data.user_score.digital_capital?.items?.digital_advocacy?.average ?? 0,
    data.user_score.digital_capital?.items?.digital_implementation?.average ?? 0,
    data.user_score.digital_capital?.items?.growth_mindset?.average ?? 0,
    data.user_score.social_capital.items.intercultural_empathy.average,
    data.user_score.social_capital.items.interpersonal_impact.average,
    data.user_score.social_capital.items.diplomacy.average,
    data.user_score.psychological_capital.items.passion_for_diversity.average, 
    data.user_score.psychological_capital.items.quest_for_adventure.average,
    data.user_score.psychological_capital.items.self_assurance.average,
    data.user_score.intellectual_capital.items.global_business_savvy.average,
    data.user_score.intellectual_capital.items.cosmopolitan_outlook.average,
    data.user_score.intellectual_capital.items.cognitive_complexity.average,
  ], 577, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', (PAGE_WIDTH/2) - 65, (PAGE_HEIGHT/2) - 65, 130, 130, undefined, 'FAST');
  chart.destroy();

  reportsGlobalCanvasSetSize(168, 224);
  chart = initGdmiSmallCircularGraph(canvas, data.user_score.intellectual_capital.average, data.user_score.intellectual_capital.items, 'Intellectual', COLORS.intellectual, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PADDINGX + 18, 60, 25, 35, undefined, 'FAST');
  chart.destroy();
  
  chart = initGdmiSmallCircularGraph(canvas, data.user_score.digital_capital?.average, data.user_score.digital_capital?.items, 'Digital', COLORS.digital, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PAGE_WIDTH - PADDINGX - 45, 60, 25, 35, undefined, 'FAST');
  chart.destroy();

  chart = initGdmiSmallCircularGraph(canvas, data.user_score.psychological_capital.average, data.user_score.psychological_capital?.items, 'Psychological', COLORS.psychological, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PADDINGX + 18, 200, 25, 35, undefined, 'FAST');
  chart.destroy();

  chart = initGdmiSmallCircularGraph(canvas, data.user_score.social_capital.average, data.user_score.social_capital?.items, 'Social', COLORS.social, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PAGE_WIDTH - PADDINGX - 45, 200, 25, 35, undefined, 'FAST');
  chart.destroy();

  // PAGE 7.
  const groupInfo = data.group_mean ? {date: data.group_mean.date, completed: data.group_mean.participants_completed, total: data.group_mean.participants_total} : null;
  doc.addPage();
  writeText(doc, 'Your Global Intellectual Capital Profile', PAGE_WIDTH / 2, 30, 24, 'bold', 900, COLORS.blueDark, 'center');

  reportsGlobalCanvasSetSize(969, 600);
  chart = initGdmiHorizontalBarsGraph(canvas, 'intellectual', COLORS.intellectual, 
  { 
    yourScore: [
      data.user_score.intellectual_capital.items.cognitive_complexity.average,
      data.user_score.intellectual_capital.items.global_business_savvy.average,
      data.user_score.intellectual_capital.items.cosmopolitan_outlook.average
    ],
    groupMean: [
      data.group_mean?.means?.intellectual_capital?.items?.cognitive_complexity?.average ?? 0,
      data.group_mean?.means?.intellectual_capital?.items?.global_business_savvy?.average ?? 0,
      data.group_mean?.means?.intellectual_capital?.items?.cosmopolitan_outlook?.average ?? 0
    ],
    grandMean: [
      data.grand_mean?.means?.intellectual_capital?.items?.cognitive_complexity?.average ?? 0,
      data.grand_mean?.means?.intellectual_capital?.items?.global_business_savvy?.average ?? 0,
      data.grand_mean?.means?.intellectual_capital?.items?.cosmopolitan_outlook?.average ?? 0
    ]
  }, groupInfo, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PADDINGX + 10, 35, 150, 90, undefined, 'FAST');
  chart.destroy();

  printList(doc, 'Global Business Savvy', [
    'Your knowledge of global industry',
    'Your knowledge of global competitive business and marketing strategies',
    'Your knowledge of how to transact business and assess risks of doing business internationally',
    'Your knowledge of supplier options in other parts of the world'
  ], 142, 8);
  
  printList(doc, 'Cosmopolitan Outlook', [
    'Your knowledge of cultures in different parts of the world',
    'Your knowledge of geography, history, and important persons of several countries',
    'Your knowledge of economic and political issues, concerns, hot topics, etc. of major regions of the world',
    ['Your up-to-date knowledge of important world events', 4]
  ], 187, 8);

  printList(doc, 'Cognitive Complexity', [
    'Your ability to grasp complex concepts quickly',
    'The strength of your analytical and problem solving skills',
    'Your ability to understand abstract ideas',
    'Your ability to take complex issues and explain the main points simply and understandably'
  ], 232, 8);

  // PAGE 8.
  doc.addPage();
  writeText(doc, 'Your Global Digital Capital Profile', PAGE_WIDTH/2, 30, 24, 'bold', 900, COLORS.blueDark, 'center');

  chart = initGdmiHorizontalBarsGraph(canvas, 'digital',COLORS.digital,
  {
    yourScore: [
      data.user_score?.digital_capital?.items?.digital_advocacy?.average ?? 0,
      data.user_score?.digital_capital?.items?.growth_mindset?.average ?? 0,
      data.user_score?.digital_capital?.items?.digital_implementation?.average ?? 0
    ],
    groupMean: [
      data.group_mean?.means?.digital_capital?.items?.digital_advocacy?.average ?? 0,
      data.group_mean?.means?.digital_capital?.items?.growth_mindset?.average ?? 0,
      data.group_mean?.means?.digital_capital?.items?.digital_implementation?.average ?? 0
    ],
    grandMean: [
      data.grand_mean?.means?.digital_capital?.items?.digital_advocacy?.average ?? 0,
      data.grand_mean?.means?.digital_capital?.items?.growth_mindset?.average ?? 0,
      data.grand_mean?.means?.digital_capital?.items?.digital_implementation?.average ?? 0
    ]
  }, groupInfo, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.1), 'PNG', PADDINGX + 25, 35, 130, 75, undefined, 'FAST');
  chart.destroy();

  printList(doc, 'Digital Advocacy', [
    'Your knowledge of the opportunities created by digital transformation',
    'Your knowledge of the impact of digital transformation on client experience',
    'Your knowledge of the impact of digital transformation on internal operations',
    'Your knowledge of the impact of digital transformation on business model',
    'Your knowledge of the impact of digital transformation on jobs in the organization',
    'Your knowledge of the basics of advanced technologies',
    'Your knowledge of how to communicate a vision of digital transformation',
  ], 120, 8);

  printList(doc, 'Digital Implementation', [
    'Your knowledge of how to manage digital transformation',
    'Your knowledge of the role of Information Technology group in digital transformation',
    'Your knowledge of the role of middle management in digital transformation',
    'Your knowledge of the potential sources of resistance to digital transformation',
    'Your knowledge of how to build employee confidence in digital transformation',
  ], 187, 8);

  printList(doc, 'Growth Mindset', [
    'Your ability to build trust',
    'Your ability to encourage continuous learning',
    'Your ability to encourage constructive debate',
    'Your ability to empower employees'
  ], 238, 8);

  // PAGE 9.
  doc.addPage();
  writeText(doc, 'Your Global Social Capital Profile', PAGE_WIDTH/2, 30, 24, 'bold', 900, COLORS.blueDark, 'center');

  chart = initGdmiHorizontalBarsGraph(canvas, 'social',COLORS.social,
  {
    yourScore: [
      data.user_score.social_capital.items.intercultural_empathy.average,
      data.user_score.social_capital.items.diplomacy.average,
      data.user_score.social_capital.items.interpersonal_impact.average
    ],
    groupMean: [
      data.group_mean?.means?.social_capital?.items?.intercultural_empathy?.average ?? 0,
      data.group_mean?.means?.social_capital?.items?.diplomacy?.average ?? 0,
      data.group_mean?.means?.social_capital?.items?.interpersonal_impact?.average ?? 0
    ],
    grandMean: [
      data.grand_mean?.means?.social_capital?.items?.intercultural_empathy?.average ?? 0,
      data.grand_mean?.means?.social_capital?.items?.diplomacy?.average ?? 0,
      data.grand_mean?.means?.social_capital?.items?.interpersonal_impact?.average ?? 0
    ]
  }, groupInfo, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.1), 'PNG', PADDINGX + 10, 35, 150, 90, undefined, 'FAST');
  chart.destroy();

  printList(doc, 'Intercultural Empathy', [
    'Your ability to work well with people from other parts of the world',
    'Your ability to understand nonverbal expressions of people from other cultures',
    'Your ability to emotionally connect to people from other cultures',
    'Your ability to engage people from other parts of the world to work together'
  ], 142, 8);

  printList(doc, 'Interpersonal Impact', [
    'Your experience in negotiating contracts/agreements in other cultures',
    'Your strong networks with people from other cultures and with influential people',
    'Your reputation as a leader',
  ], 187, 8);

  printList(doc, 'Diplomacy', [
    'Your ease of starting a conversation with a stranger',
    'Your ability to integrate diverse perspectives',
    'Your ability to listen to what others have to say',
    'Your willingness to collaborate'
  ], 225, 8);

  // PAGE 10.
  doc.addPage();
  writeText(doc, 'Your Global Psychological Capital Profile', PAGE_WIDTH/2, 30, 24, 'bold', 900, COLORS.blueDark, 'center');

  chart = initGdmiHorizontalBarsGraph(canvas, 'psychological', COLORS.psychological, 
  {
    yourScore: [
      data.user_score.psychological_capital.items.passion_for_diversity.average,
      data.user_score.psychological_capital.items.self_assurance.average,
      data.user_score.psychological_capital.items.quest_for_adventure.average
    ],
    groupMean: [
      data.group_mean?.means?.psychological_capital?.items?.passion_for_diversity?.average ?? 0,
      data.group_mean?.means?.psychological_capital?.items?.self_assurance?.average ?? 0,
      data.group_mean?.means?.psychological_capital?.items?.quest_for_adventure?.average ?? 0
    ],
    grandMean: [
      data.grand_mean?.means?.psychological_capital?.items?.passion_for_diversity?.average ?? 0,
      data.grand_mean?.means?.psychological_capital?.items?.self_assurance?.average ?? 0,
      data.grand_mean?.means?.psychological_capital?.items?.quest_for_adventure?.average ?? 0
    ]
  }, groupInfo, DEFAULT_GRAPH_OPTIONS);
  doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PADDINGX + 10, 35, 150, 90, undefined, 'FAST');
  chart.destroy();

  printList(doc, 'Passion for Diversity', [
    'Your enjoyment of exploring other parts of the world',
    'Your enjoyment of getting to know people from other parts of the world',
    'Your enjoyment of living in another country',
    'Your enjoyment of traveling'
  ], 142, 8);

  printList(doc, 'Quest for Adventure', [
    'Your interest in dealing with challenging situations',
    'Your willingness to take risk',
    'Your willingness to test one’s abilities',
    'Your enjoyment of dealing with unpredictable situations'
  ], 187, 8);

  printList(doc, 'Self-Assurance', [
    'Your energy level',
    'Your self-confidence',
    'Your wittiness in tough situations',
    'Your comfort level in uncomfortable situations'
  ], 232, 8);

  // PAGE 11.
  doc.addPage();
  writeText(doc, 'Your Detailed Global Digital Mindset Profile', PAGE_WIDTH/2, 25, 20, 'bold', 900, COLORS.blueDark, 'center');
  yourDetailedProfileChart(doc, data, groupInfo);
  
  if (data.group_mean) {
    // PAGE 12.
    doc.addPage();
    writeText(doc, 'Group Profile of Global Digital Mindset', PAGE_WIDTH/2, 34, 24, 'bold', 900, COLORS.blueDark, 'center');

    reportsGlobalCanvasSetSize(800, 800);
    chart = initGdmiSpiderCircularGraph(canvas, [
      data.group_mean.means.digital_capital?.items?.digital_advocacy?.average ?? 0,
      data.group_mean.means.digital_capital?.items?.digital_implementation?.average ?? 0,
      data.group_mean.means.digital_capital?.items?.growth_mindset?.average ?? 0,
      data.group_mean.means.social_capital.items.intercultural_empathy.average,
      data.group_mean.means.social_capital.items.interpersonal_impact.average,
      data.group_mean.means.social_capital.items.diplomacy.average,
      data.group_mean.means.psychological_capital.items.passion_for_diversity.average, 
      data.group_mean.means.psychological_capital.items.quest_for_adventure.average,
      data.group_mean.means.psychological_capital.items.self_assurance.average,
      data.group_mean.means.intellectual_capital.items.global_business_savvy.average,
      data.group_mean.means.intellectual_capital.items.cosmopolitan_outlook.average,
      data.group_mean.means.intellectual_capital.items.cognitive_complexity.average,
    ], 577, DEFAULT_GRAPH_OPTIONS);
    doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', (PAGE_WIDTH/2) - 65, (PAGE_HEIGHT/2) - 65, 130, 130, undefined, 'FAST');
    chart.destroy();

    reportsGlobalCanvasSetSize(168, 224);
    chart = initGdmiSmallCircularGraph(canvas, data.group_mean.means.intellectual_capital.average, data.group_mean.means.intellectual_capital.items, 'Intellectual', COLORS.intellectual, DEFAULT_GRAPH_OPTIONS);
    doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PADDINGX + 18, 60, 25, 35, undefined, 'FAST');
    chart.destroy();

    chart = initGdmiSmallCircularGraph(canvas, data.group_mean.means?.digital_capital?.average, data.group_mean.means?.digital_capital?.items, 'Digital', COLORS.digital, DEFAULT_GRAPH_OPTIONS);
    doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PAGE_WIDTH - PADDINGX - 45, 60, 25, 35, undefined, 'FAST');
    chart.destroy();

    chart = initGdmiSmallCircularGraph(canvas, data.group_mean.means.psychological_capital.average, data.group_mean.means.psychological_capital.items, 'Psychological', COLORS.psychological, DEFAULT_GRAPH_OPTIONS);
    doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PADDINGX + 18, 200, 25, 35, undefined, 'FAST');
    chart.destroy();

    chart = initGdmiSmallCircularGraph(canvas, data.group_mean.means.social_capital.average, data.group_mean.means.social_capital.items,'Social', COLORS.social, DEFAULT_GRAPH_OPTIONS);
    doc.addImage(chart.toBase64Image('image/png', 0.5), 'PNG', PAGE_WIDTH - PADDINGX - 45, 200, 25, 35, undefined, 'FAST');
    chart.destroy();

    // PAGE 13.
    doc.addPage();
    writeText(doc, 'Detailed Group Profile of Global Digital Mindset', PAGE_WIDTH/2, 34, 20, 'bold', 900, COLORS.blueDark, 'center');
    groupDetailedProfileChart(doc, data, groupInfo);
  }
  
  // PAGE 14.
  doc.addPage();
  writeText(doc, 'Personal Observations and Development Planning', PAGE_WIDTH/2, 30, 20, 'bold', 900, COLORS.blueDark, 'center');
  writeText(doc, '1. What is your assessment of the need for you to possess a global digital mindset? Now? Three years from now?', PADDINGX, 50, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 65, 8, 5);
  writeText(doc, '2. What are the consequences of your doing nothing to further develop your global digital mindset?', PADDINGX, 120, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 125, 8, 5);
  writeText(doc, '3. What are your areas of relative strength?', PADDINGX, 180, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 185, 8, 5);

  // PAGE 15.
  doc.addPage();
  writeText(doc, 'Personal Observations and Development Planning', PAGE_WIDTH/2, 30, 20, 'bold', 900, COLORS.blueDark, 'center');
  writeText(doc, '4. What are your areas of development?', PADDINGX, 50, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 55, 8, 9);
  writeText(doc, '5. What are your priorities over the next 6 to 9 months for further sustaining and leveraging your areas of strength?', PADDINGX, 150, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 160, 8, 9);

  // PAGE 16.
  doc.addPage();
  writeText(doc, 'Personal Observations and Development Planning', PAGE_WIDTH/2, 30, 20, 'bold', 900, COLORS.blueDark, 'center');
  writeText(doc, '6. What are your priorities over the next 6 to 9 months for improving your areas of development?', PADDINGX, 50, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 55, 8, 9);
  writeText(doc, '7. What are the top 3 to 5 steps you will take over the next 6 to 9 months?', PADDINGX, 150, 11, 'normal', 400, COLORS.black);
  addQuestionLines(doc, 160, 8, 9);

  removeReportsGlobalCanvas();

  // Header and footer.
  addPagesFooter(doc);
  addPagesHeader(doc, data);

  // PREVIEW
  // let string = doc.output('datauristring');
  // let embed = "<embed width='100%' height='100%' src='" + string + "'/>"
  // let x = window.open();
  // x.document.open();
  // x.document.write(embed);
  // x.document.close();

  const filename = `(GDMI Report) ${data.user} [${data.date}](${data.sid}).pdf`;
  if (download) {
    doc.save(filename, { returnPromise: true }).then((e) => {
      saveReportEntity(doc, filename, data.sid)
    });
  } else {
    saveReportEntity(doc, filename, data.sid)
  }
}