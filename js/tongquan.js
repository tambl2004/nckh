
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6'],
                datasets: [{
                    label: 'Doanh thu',
                    data: [12000000, 19000000, 15000000, 25000000, 22000000, 30000000],
                    borderColor: 'rgb(107, 72, 255)',
                    backgroundColor: 'rgba(107, 72, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Top Products Chart
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topProductsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Laptop Dell XPS', 'MacBook Pro', 'Asus ROG', 'HP Spectre', 'Lenovo ThinkPad'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        'rgb(107, 72, 255)',
                        'rgb(255, 107, 107)',
                        'rgb(90, 60, 212)',
                        'rgb(240, 90, 90)',
                        'rgb(70, 50, 180)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 20, font: { size: 12 } }
                    }
                }
            }
        });
 