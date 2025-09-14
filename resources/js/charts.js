const ctx = document.getElementById('incomeChart').getContext('2d');

// Початкові дані
const dataSets = {
  day: {
    labels: ['00:00', '06:00', '12:00', '18:00', '24:00'],
    data: [500, 1200, 8000, 3200, 5000]
  },
  week: {
    labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Нд'],
    data: [7000, 8200, 5400, 11000, 9200, 15000, 12300]
  },
  month: {
    labels: Array.from({ length: 30 }, (_, i) => i + 1),
    data: Array.from({ length: 30 }, () => Math.floor(Math.random() * 20000) + 3000)
  },
  year: {
    labels: ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер', 'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру'],
    data: Array.from({ length: 12 }, () => Math.floor(Math.random() * 50000) + 10000)
  }
};

// Створення графіка
let incomeChart = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: dataSets.month.labels,
    datasets: [{
      label: 'Доход, грн',
      data: dataSets.month.data,
      backgroundColor: '#272727',
      borderRadius: 8,
      hoverBackgroundColor: "#FECC39",
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: "#FECC39", // Жовтий фон
        titleFont: { size: 12, family: "Wix Madefor Display", weight: 'bold' },
        bodyFont: { size: 12, family: "Wix Madefor Display" },
        titleColor: "#272727", // Чорний текст
        bodyColor: "#272727",
        displayColors: false, // Прибрати кольоровий квадратик
        xAlign: 'center',
        yAlign: 'bottom',
        callbacks: {
          title: () => "", // Видаляємо заголовок
          label: (tooltipItem) => `${tooltipItem.raw} ₴`
        }
      }
    },
    scales: {
      x: {
        ticks: {
          color: '#fff',
          font: { size: 12, family: "Wix Madefor Display" }
        },
        grid: { color: '#343434' }
      },
      y: {
        ticks: {
          color: '#fff',
          font: { size: 12, family: "Wix Madefor Display" }
        },
        grid: { color: '#343434' }
      }
    }
  }
});

// Функція оновлення графіка при зміні періоду
function updateChart(period) {
  incomeChart.data.labels = dataSets[period].labels;
  incomeChart.data.datasets[0].data = dataSets[period].data;
  incomeChart.update();

  // Активний стан кнопок
  document.querySelectorAll('.collected .buttons button').forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
}

function updateCanvasSize() {
  const canvas = document.getElementById('incomeChart');

  if (window.innerWidth <= 1024) {
    canvas.style.width = '867px';
    canvas.style.height = 'auto';
  } else {
    canvas.style.width = '';
    canvas.style.height = '';
  }
}

// Викликати функцію при завантаженні та зміні розміру вікна
window.addEventListener('load', updateCanvasSize);
window.addEventListener('resize', updateCanvasSize);