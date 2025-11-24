function analyticsApp() {
    return {
        quizId: new URLSearchParams(window.location.search).get('quiz_id'),
        stats: {},
        questionStats: [],
        charts: [],
        
        async init() {
            if (!this.quizId) {
                Swal.fire('Error', 'ID de quiz no proporcionado', 'error');
                return;
            }
            
            await this.loadAnalytics();
        },
        
        async loadAnalytics() {
            try {
                const response = await fetch(`../api/analytics.php?quiz_id=${this.quizId}`);
                const data = await response.json();
                
                if (response.ok) {
                    this.stats = data.general_stats || {};
                    this.questionStats = data.question_stats || [];
                    
                    // Crear gráficas después de que Alpine renderice
                    this.$nextTick(() => {
                        this.createCharts();
                    });
                } else {
                    Swal.fire('Error', data.error || 'Error al cargar analíticas', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        },
        
        createCharts() {
            // Destruir gráficas anteriores
            this.charts.forEach(chart => chart.destroy());
            this.charts = [];
            
            this.questionStats.forEach(question => {
                const canvasId = `chart-${question.question_id}`;
                const canvas = document.getElementById(canvasId);
                
                if (!canvas) return;
                
                const options = question.options || [];
                const labels = options.map(opt => opt.option_text);
                const data = options.map(opt => parseInt(opt.times_selected) || 0);
                const backgroundColors = options.map(opt => opt.is_correct ? '#10b981' : '#ef4444');
                
                const ctx = canvas.getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Veces seleccionada',
                            data: data,
                            backgroundColor: backgroundColors,
                            borderColor: backgroundColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
                
                this.charts.push(chart);
            });
        },
        
        async exportToCSV() {
            try {
                // Obtener ranking
                const response = await fetch(`../api/attempts.php?quiz_id=${this.quizId}`);
                const ranking = await response.json();
                
                // Crear CSV
                let csv = 'Nombre,Puntos,Máximo Puntos,Porcentaje,Fecha Inicio,Fecha Fin\n';
                
                ranking.forEach(entry => {
                    csv += `"${entry.participant_name}",${entry.total_points},${entry.max_points},${entry.percentage},"${entry.started_at}","${entry.finished_at}"\n`;
                });
                
                // Descargar
                const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `quiz_${this.quizId}_results.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                Swal.fire('Éxito', 'CSV exportado exitosamente', 'success');
            } catch (error) {
                Swal.fire('Error', 'Error al exportar CSV', 'error');
            }
        }
    };
}


