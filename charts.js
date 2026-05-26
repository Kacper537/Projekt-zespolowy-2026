document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('expenseChart');
    
    if (!ctx) return;

    // Pobranie danych wstrzykniętych w oknie globalnym przez PHP w pliku dashboard.php
    const labels = window.chartLabels || [];
    const dataValues = window.chartData || [];

    if (labels.length === 0) {
        ctx.parentNode.innerHTML = "<p class='text-muted mt-5 text-center'>Brak wydatków w tym miesiącu do wyświetlenia wykresu.</p>";
        return;
    }

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: dataValues,
                backgroundColor: [
                    '#ff6384',
                    '#36a2eb',
                    '#cc65fe',
                    '#ffce56',
                    '#2ec4b6',
                    '#e71d36'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});