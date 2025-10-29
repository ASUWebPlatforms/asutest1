const gdmiHorizontalGlobalBarsGraph = {
  id: 'gdmiHorizontalGlobalBarsGraph',
  beforeInit(chart, args, options) {
    const fitValue = chart.legend.fit;
    chart.legend.fit = function fit () {
      fitValue.bind(chart.legend)();
      return this.height += 50;
    }
  },
  afterDatasetDraw(chart, args, options) {
    const {ctx, scales: {x, y}, chartArea: {left, top, width, height}} = chart;
    ctx.save();
    ctx.font = '900 16px Public Sans';
    ctx.textAlign = 'center';
    chart.data.datasets.forEach((dataset, i) => {
      let meta = chart.getDatasetMeta(i);
      dataset.data.forEach((data, j) => {
        if (data === 0 && meta.visible) {
          let color = i === 0 && options.indicators.length > 0 ? options.labelsColors[j] : dataset.backgroundColor;
          color = i === 0 && !options.indicators.length > 0 && typeof color !== 'string' ? options.labelsColors[j] : color;
          ctx.fillStyle = color;
          ctx.fillText('N/A', x.getPixelForValue(1.2), chart.getDatasetMeta(i).data[j].y);
          ctx.fillRect(x.getPixelForValue(1), chart.getDatasetMeta(i).data[j].y - 15, 15, 30);
        } else {
          ctx.fillStyle = 'white';
          ctx.fillText(data, x.getPixelForValue(data - 0.1), chart.getDatasetMeta(i).data[j].y);
        }
      });
    });
    ctx.restore();
  },
  afterDraw(chart, args, options) {
    const {ctx, chartArea: {left, top, width, height}} = chart;
    ctx.save();
    ctx.strokeStyle = options.color;
    ctx.lineWidth = options.borderWidth;
    ctx.strokeRect(left, top, width, height + 4);
    
    chart.scales.x._labelItems.forEach((dataItem, index) => {
      if (index > 0 && index < 5) {
        let x = dataItem.options.translation[0];
        let y = dataItem.options.translation[1] - 25;
        ctx.fillStyle = '#ACACAC';
        ctx.translate(x, y);
        ctx.rotate(Math.PI / 4);
        ctx.fillRect(0, 0, 8, 8);
        ctx.rotate((Math.PI / 4) * -1);
        ctx.translate(-1 * x, -1 * y);
      }

      if (index >= 0) {
        let x = dataItem.options.translation[0];
        let y = dataItem.options.translation[1] + 45;
        ctx.translate(x, y);
        ctx.font = '900 16px Public Sans';
        ctx.fillStyle = options.color;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        let label = options.ticksLabels[index];
        if (Array.isArray(label)) {
          label.forEach((text, index) => {
            ctx.fillText(text.toUpperCase(), 0, index * 18);
          });
        } else {
          ctx.fillText(label.toUpperCase(), 0, 0);
        }
        ctx.translate(-1 * x, -1 * y);
      }
    });

    if (chart.scales.y._labelItems) {
      chart.scales.y._labelItems.forEach((dataItem, i) => {
        dataItem.options.textAlign = 'center';
        dataItem.options.translation[0] = (left / 2);
        let lines = dataItem.label.length;
        let rectHeight = lines * 18 + 40;
        ctx.fillStyle = options.labelsColors[i];
        ctx.fillRect(0, dataItem.options.translation[1] - (rectHeight / 2), left - 5, rectHeight);
        ctx.font = '900 18px Public Sans';
        ctx.fillStyle = 'white';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        dataItem.label.forEach((label, index) => {
          let ofset = dataItem.label.length * 5 * (dataItem.label.length > 1);
          ctx.fillText(label, dataItem.options.translation[0], dataItem.options.translation[1] - ofset + (18 * index));
        })
      });
    }

    ctx.textAlign = 'left';
    ctx.font = '900 14px Public Sans';
    options.indicators.forEach((item, i) => {
      ctx.fillStyle = item.color;
      let x = i > 2 ? 560 : 160;
      let yOffset = i > 2 ? 80 : 180;
      let y = height + yOffset + (i  * 50);
      ctx.fillRect(x, y, 25, 25);
      ctx.fillStyle = item.color;
      ctx.fillText(item.label, x + 30, y + (25/2));
    });

    if (options.showBottomLabel) {
      ctx.translate(left + width - 300, top + height + 120);
      ctx.textAlign = 'left';
      ctx.fillStyle = '#B4B4B4';
      ctx.font = '900 16px Public Sans';
      options.resultsBottomLabel.forEach((label, index) => {
        ctx.fillText(label, 0, index * 20);
      });
    }

    ctx.restore();
  },
};

function initGdmiHorizontalGlobalBarsGraph(canvas, datasets, indicators = [], group = null) {

  // Order desc.
  const [indexs, sorted] = orderingObject(datasets[0].data);
  datasets[0].data = sorted; 
  datasets[1].data = orderBasedOnIndex(indexs, datasets[1].data);
  const sortedColors = orderBasedOnIndex(indexs, ['#E43D51', '#E43D51', '#E43D51', '#753E96', '#753E96', '#753E96', '#E69E00', '#E69E00', '#E69E00', '#0179B7', '#0179B7', '#0179B7']);
  datasets[0].backgroundColor = function (context, options) {
    return sortedColors[context.index];
  };

  return new Chart(canvas, {
    type: 'bar',
    data: {
      labels: orderBasedOnIndex(indexs, globalLabels) ,
      datasets: datasets
    },
    options: {
      responsive: false,
      animation: {
        duration: 0,
      },
      indexAxis: 'y',
      elements: {
        bar: {
          borderWidth: 0,
        },
      },
      layout: {
        padding: {
          left: 10,
          right: 75,
          bottom: 300
        }
      },
      scales: {
        x: {
          min: 1,
          max: 5,
          beginAtZero: true,
          grid: {
            color: function(context, options) {
              return context.index != 0 && context.index != 5 ? '#ACACAC' : '';
            },
          lineWidth: 2,
          },
          ticks: {
            display: true,
            stepSize: 1,
            color: 'gray',
            padding: 10,
            font: {
              size: 24,
              weight: 900,
              family: 'Public Sans'
            }
          }
        },
        y: {
          grid: {
            display: false,
          },
          ticks: {
            z: 2,
            color: 'white',
            font: {
              size: 18,
              weight: 900,
              family: 'Public Sans'
            }
          }
        }
      },
      plugins: {
        legend: {
          display: indicators.length === 0,
          position: 'bottom',
          align: 'start',
          labels: {
            boxWidth: 24, 
            boxHeight: 24,
            generateLabels: function (chart) {
              chart.legend.left = 150;
              chart.legend.top = chart.legend.top + 60;
              return chart.data.datasets.map(function (dataset, i) {
                return {
                  datasetIndex: i,
                  index: i,
                  text: dataset?.label,
                  fillStyle: dataset?.backgroundColor,
                  fontColor: dataset?.backgroundColor,
                  lineWidth: 0,
                  hidden: !chart._metasets[i]?.visible,
                };
            });
          },
            font: {
              size: 18,
              weight: 900,
              family: 'Public Sans'
            }
          }
        },
        tooltip: {
          enabled: false
        },
        gdmiHorizontalGlobalBarsGraph: {
          indicators: indicators,
          labelsColors: sortedColors,
          color: 'gray',
          borderWidth: 10,
          ticksLabels: ['Not at all', ['To a small', 'extent'], ['To a moderate', 'extent'], ['To a Large', 'extent'], ['To a Very Large', 'extent']],
          resultsBottomLabel: ['*Results as of ' + group?.date + ' (' + group?.completed  + ' of ' + group?.total , 'participants have completed the GDMI)'],
          showBottomLabel: group != null
        }
      }
    },
    plugins: [gdmiHorizontalGlobalBarsGraph]
  });
}