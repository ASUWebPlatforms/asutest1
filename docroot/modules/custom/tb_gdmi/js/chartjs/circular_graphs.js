//--- Spider circular graph.
const gdmiSpiderCircularGraph = {
  id: 'gdmiSpiderCircularGraph',
  afterDatasetsDraw: function(chart, args, pluginOptions) {

    if (!pluginOptions.display) {
      return;
    }

    const { ctx, data } = chart;
    const xCenter = chart.getDatasetMeta(0).data[0].x;
    const yCenter = chart.getDatasetMeta(0).data[0].y;
    const radius = chart.scales.r.getDistanceFromCenterForValue(chart.scales.r.max) + 90;

    chart.getDatasetMeta(0).data.forEach((dataItem, index) => {
      const startAngle = dataItem.startAngle;
      const endAngle = dataItem.endAngle;
      const centerAngle = (startAngle + endAngle) / 2;

      const xCoor = xCenter + radius * Math.cos(centerAngle);
      const yCoor = yCenter + radius * Math.sin(centerAngle);

      ctx.save();
      ctx.translate(xCoor, yCoor);
      ctx.font = '900 18px Public Sans';
      ctx.textBaseline = 'middle';
      ctx.textAlign = 'center'
      ctx.fillStyle = pluginOptions.labelColors[index];
      if (Array.isArray(data.labels[index])) {
        data.labels[index].forEach((el, index) => {
          ctx.fillText(el, 0, 0 + (20 * index));
        });
      } else {
        ctx.fillText(data.labels[index], 0, 0);
      }
      ctx.restore();

    });
  },
  afterDraw: function(chart, args, pluginOptions) {

    const radius = chart.scales.r.getDistanceFromCenterForValue(chart.scales.r.max);
    const scale = chart.scales.r;
    const ctx = chart.ctx;
    
    ctx.save();

    const xCenter = chart.getDatasetMeta(0).data[0].x;
    const yCenter = chart.getDatasetMeta(0).data[0].y;
    
    ctx.translate(0, 0);
    chart.getDatasetMeta(0).data.forEach((dataItem, index) => {
      const startAngle = dataItem.startAngle;
      const xCoor = xCenter + radius * Math.cos(startAngle);
      const yCoor = yCenter + radius * Math.sin(startAngle);
      ctx.strokeStyle = '#002E5F';
      let lineWidth = 3;
      lineWidth = index % 3 === 0 ? 8 : lineWidth;
      lineWidth = index === 3 ? 20 : lineWidth;
      ctx.lineWidth = lineWidth;
      ctx.beginPath();
      ctx.moveTo(xCenter, yCenter);
      ctx.lineTo(xCoor, yCoor);
      ctx.stroke();
    });

    ctx.translate(scale.xCenter , scale.yCenter);
    ctx.font = '900 14px Public Sans';
    ctx.fillStyle = 'white';
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    const diameter = radius * 2;
    ctx.fillText('1', diameter * 0.01, 0);
    ctx.fillText('2', diameter *  0.125, 0);
    ctx.fillText('3', diameter * 0.25, 0);
    ctx.fillText('4', diameter * 0.375, 0);
    ctx.fillText('5', diameter * 0.49, 0);

    ctx.restore();
  },
};

function initGdmiSpiderCircularGraph(canvas, data, sWidth = 577, options = { responsive: true, maintainAspectRatio: false, animation : {animateRotate: false, animateScale: true}}) {
  return new Chart(canvas, {
    type: 'polarArea',
    data: {
      labels: [
        ['Digital', 'Advocacy'],
        ['Digital', 'Implementation'],
        ['Growth', 'Mindset'],
        ['Intercultural','Empathy'],
        ['Interpersonal', 'Impact'],
        'Diplomacy',
        ['Passion for', 'Diversity'], 
        ['Quest for', 'Adventure'], 
        ['Self-','Assurance'], 
        ['Global Business', 'Savvy'],
        ['Cosmopolitan','Outlook'],
        ['Cognitive','Complexity'], 
      ],
      datasets: [
        { 
          data,
          backgroundColor: [
            '#0179B7',
            '#0179B7',
            '#0179B7',
            '#E69E00',
            '#E69E00',
            '#E69E00',
            '#E43D51',
            '#E43D51',
            '#E43D51',
            '#753E96',
            '#753E96',
            '#753E96',
          ]
        },
      ],
    },
    options: {
      layout: {
        padding: sWidth > 576 ? 150 : 10,
      },
      scales: {
        r: {
          min: 1,
          max: 5,
          beginAtZero: true,
          animate: false,
          grid: {
            color: '#002E5F',
            z: 1,
            lineWidth: function (context, options) {
              return context.index === 4 ? 12 : 3;
            },
          },
          ticks: {
            display: false,
            stepSize: 1,
            z: 1,
            color: 'red',
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: false
        },
        gdmiSpiderCircularGraph: {
          display: sWidth > 576,
          labelColors: [
            '#0179B7',
            '#0179B7',
            '#0179B7',
            '#E69E00',
            '#E69E00',
            '#E69E00',
            '#F6665F',
            '#F6665F',
            '#F6665F',
            '#963E80',
            '#963E80',
            '#963E80',
          ],
        }
      },
      ...options
    },
    plugins: [gdmiSpiderCircularGraph]
  });
}

//--- Small circular graph.

const gdmiSmallCircularGraph = {
  id: 'gdmiSmallCircularGraph',
  afterDatasetsDraw(chart, args, pluginOptions) {
    const { ctx } = chart;

    ctx.save();

    const xCoor = chart.getDatasetMeta(0).data[0].x;
    const yCoor = chart.getDatasetMeta(0).data[0].y;
    const canvasWidth = chart.chartArea.right - chart.chartArea.left;

    // Center label value.
    ctx.font = '900 28px Public Sans';
    ctx.fillStyle = 'white';
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText(pluginOptions.val, xCoor, yCoor);

    // White line.
    ctx.beginPath();
    ctx.fillStyle = 'white';
    ctx.rect(30, 130, canvasWidth, 2);
    ctx.fill();

    // Capital name label.
    ctx.font = '900 18px Public Sans';
    ctx.fillStyle = 'white';
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillText('Global', (canvasWidth + 60)/2, 155);
    ctx.fillText(pluginOptions.capitalName, (canvasWidth + 60)/2, 175);
    ctx.fillText('Capital', (canvasWidth + 60)/2, 195);

    ctx.restore();
  },
  beforeDraw: function(chart, args, pluginOptions) {
    const { ctx } = chart;
    ctx.save();
    ctx.fillStyle = pluginOptions.color;
    ctx.beginPath();
    ctx.roundRect(0, 0, 168, 224, 17);
    ctx.fill();
    ctx.restore();
  }
};

function initGdmiSmallCircularGraph(canvas, val = 0, items = [0, 0, 0], capitalName, color, options = { maintainAspectRatio: false }, max = 15, width = 168, height = 224) {
  if (!Array.isArray(items) ) {
    items = Object.entries(items).map(i => i[1].average ?? 0);
  }

  const [indexs, sorted] = orderingObject(items);
  const sum = items.reduce((a, b) => parseFloat(a) + parseFloat(b), 0)
  const restval = (max - sum).toFixed(1);
  sorted.push(restval)
  canvas.width = width;
  canvas.height = height;

  return new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: orderBasedOnIndex(indexs, capitalsLabels[capitalName.toLowerCase()]),
      datasets: [{
        data: sorted,
        backgroundColor: [
          'white',
          'white',
          'white',
          'transparent'
        ],
        cutout: '70%',
        rotation: 90,
        borderWidth: 0
      }]
    },
    options: {
      spacing: 3,
      layout: {
        padding: {
          top: 10,
          bottom: 100,
          right: 30,
          left: 30
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          enabled: true,
          callbacks: {
            label: function(context, options) {
              if (context.dataIndex >= 3) {
                return null;
              }
              return context.value;
            }
          }
        },
        gdmiSmallCircularGraph: {
          val,
          capitalName,
          color
        }
      },
      ...options
    },
    plugins: [gdmiSmallCircularGraph]
  });
}
