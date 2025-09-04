import ApexCharts from "apexcharts";
import $ from 'jquery';

(function(){
  const el = document.querySelector('#contacts-over-time-chart');
  if (!el) return;
  const colorsData = $(el).data('colors');
  const labels = $(el).data('labels') || [];
  const seriesData = $(el).data('series') || [];
  const colors = colorsData ? (''+colorsData).split(',') : ["#10c469", "#35b8e0"]; // green/blue theme

  const options = {
    series: [
      {
        name: 'Nové kontakty',
        data: seriesData.map(v => Number(v) || 0)
      }
    ],
    chart: {
      type: 'line',
      height: 299,
      zoom: { enabled: false },
      toolbar: { show: false }
    },
    stroke: {
      width: 3,
      curve: 'straight'
    },
    dataLabels: { enabled: false },
    xaxis: { categories: labels },
    colors: colors,
    tooltip: {
      shared: true,
      y: [{
        formatter: function (y) {
          if (typeof y !== 'undefined') return Math.round(y) + ' ks';
          return y;
        }
      }]
    }
  };

  const chart = new ApexCharts(el, options);
  chart.render();
})();
