/* assets/js/dashboard.js */
/* مقداردهی نمودارها با Chart.js (در صورت وجود کتابخانه) */

document.addEventListener('DOMContentLoaded', function() {

  // ---------- نمودار فروش ۷ روزه ----------
  const salesChartCanvas = document.getElementById('salesChart');
  if (salesChartCanvas && typeof Chart !== 'undefined') {
    const ctx = salesChartCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'],
        datasets: [{
          label: 'فروش (هزار تومان)',
          data: [1200, 1900, 1500, 2200, 1800, 2900, 2100],
          borderColor: '#1A3B4C',
          backgroundColor: 'rgba(26, 59, 76, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointBackgroundColor: '#1A3B4C',
          pointRadius: 4,
          pointHoverRadius: 6
        }, {
          label: 'هزینه‌ها (هزار تومان)',
          data: [800, 1100, 900, 1300, 1000, 1400, 1100],
          borderColor: '#8B2E3A',
          backgroundColor: 'rgba(139, 46, 58, 0.1)',
          fill: true,
          tension: 0.4,
          borderWidth: 2,
          pointBackgroundColor: '#8B2E3A',
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: {
              usePointStyle: true,
              font: { family: 'Vazirmatn' }
            }
          }
        },
        scales: {
          x: {
            grid: { color: '#F3EDE2' },
            ticks: { font: { family: 'Vazirmatn' } }
          },
          y: {
            grid: { color: '#F3EDE2' },
            ticks: { font: { family: 'Vazirmatn' } }
          }
        }
      }
    });
  }

  // ---------- نمودار دایره‌ای وضعیت سفارش‌ها ----------
  const ordersChartCanvas = document.getElementById('ordersChart');
  if (ordersChartCanvas && typeof Chart !== 'undefined') {
    const ctx = ordersChartCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['تکمیل شده', 'در دست بافت', 'در انتظار', 'مرجوعی'],
        datasets: [{
          data: [45, 20, 10, 5],
          backgroundColor: ['#4A6B4A', '#C68A3C', '#1A3B4C', '#B13E3E'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              usePointStyle: true,
              font: { family: 'Vazirmatn' }
            }
          }
        }
      }
    });
  }

  // ---------- نمودار میله‌ای پرفروش‌ترین محصولات ----------
  const topProductsCanvas = document.getElementById('topProductsChart');
  if (topProductsCanvas && typeof Chart !== 'undefined') {
    const ctx = topProductsCanvas.getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['حوله حمام', 'حوله دستی', 'پارچه سنتی', 'شال گردن', 'دستمال'],
        datasets: [{
          label: 'تعداد فروش',
          data: [120, 95, 60, 40, 30],
          backgroundColor: '#1A3B4C',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { color: '#F3EDE2' },
            ticks: { font: { family: 'Vazirmatn' } }
          },
          y: {
            grid: { display: false },
            ticks: { font: { family: 'Vazirmatn' } }
          }
        }
      }
    });
  }

});