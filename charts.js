document.addEventListener('DOMContentLoaded', function () {
    console.log('CHARTS JS LOADED');

    const expenseLabels = Array.isArray(window.expenseLabels) ? window.expenseLabels : [];
    const expenseData = Array.isArray(window.expenseData) ? window.expenseData : [];
    const incomeLabels = Array.isArray(window.incomeLabels) ? window.incomeLabels : [];
    const incomeData = Array.isArray(window.incomeData) ? window.incomeData : [];

    const expCanvas = document.getElementById('expenseChart');
    if (expCanvas) {
        const parent = expCanvas.parentNode;
        if (expenseLabels.length > 0 && expenseData.length > 0) {
            const expCtx = expCanvas.getContext('2d');
            new Chart(expCtx, {
                type: 'pie',
                data: {
                    labels: expenseLabels,
                    datasets: [{
                        data: expenseData,
                        backgroundColor: [
                            '#ff6384',
                            '#36a2eb',
                            '#cc65fe',
                            '#ffce56',
                            '#2ec4b6',
                            '#e71d36'
                        ]
                    }]
                },
                options: {
                    maintainAspectRatio: false
                }
            });
        } else {
            parent.innerHTML = "<p class='text-muted text-center'>Brak wydatków</p>";
        }
    }

    const incCanvas = document.getElementById('incomeChart');
    if (incCanvas) {
        const parent = incCanvas.parentNode;
        if (incomeLabels.length > 0 && incomeData.length > 0) {
            const incCtx = incCanvas.getContext('2d');
            new Chart(incCtx, {
                type: 'pie',
                data: {
                    labels: incomeLabels,
                    datasets: [{
                        data: incomeData,
                        backgroundColor: [
                            '#22c55e',
                            '#16a34a',
                            '#4ade80',
                            '#86efac',
                            '#bbf7d0',
                            '#15803d'
                        ]
                    }]
                },
                options: {
                    maintainAspectRatio: false
                }
            });
        } else {
            parent.innerHTML = "<p class='text-muted text-center'>Brak przychodów</p>";
        }
    }

    console.log('expense:', expenseLabels, expenseData);
    console.log('income:', incomeLabels, incomeData);
});