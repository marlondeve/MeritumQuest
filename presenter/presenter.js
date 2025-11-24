function presenterApp() {
    return {
        sessionCode: new URLSearchParams(window.location.search).get('session') || '',
        sessionData: null,
        questions: [],
        currentState: 'waiting', // waiting, question, results, ranking
        currentQuestionIndex: 0,
        connectedParticipants: 0,
        answeredCount: 0,
        timeLeft: 0,
        timerInterval: null,
        questionResults: {},
        ranking: [],
        topParticipants: [],
        resultsChart: null,
        
        async init() {
            if (!this.sessionCode) {
                alert('Código de sesión requerido');
                return;
            }
            
            await this.loadSession();
            this.startPolling();
        },
        
        async loadSession() {
            try {
                const response = await fetch(`../api/sessions.php?code=${this.sessionCode}`);
                const data = await response.json();
                
                if (response.ok && data) {
                    this.sessionData = data;
                    this.questions = data.questions || [];
                } else {
                    alert('Sesión no encontrada');
                }
            } catch (error) {
                console.error('Error al cargar sesión:', error);
            }
        },
        
        startPolling() {
            // Polling cada 2 segundos para actualizar estadísticas
            setInterval(async () => {
                await this.updateStats();
            }, 2000);
        },
        
        async updateStats() {
            if (!this.sessionData) return;
            
            try {
                // Obtener intentos activos para contar participantes
                const response = await fetch(`../api/attempts.php?session_id=${this.sessionData.id}`);
                const data = await response.json();
                
                if (response.ok) {
                    this.connectedParticipants = data.length;
                    
                    // Contar respuestas de la pregunta actual
                    if (this.currentState === 'question' && this.currentQuestionIndex < this.questions.length) {
                        // Aquí podrías implementar lógica más específica para contar respuestas por pregunta
                        this.answeredCount = Math.min(this.connectedParticipants, Math.floor(Math.random() * this.connectedParticipants));
                    }
                }
            } catch (error) {
                console.error('Error al actualizar estadísticas:', error);
            }
        },
        
        startQuiz() {
            this.currentState = 'question';
            this.currentQuestionIndex = 0;
            this.startTimer();
        },
        
        get currentQuestion() {
            return this.questions[this.currentQuestionIndex] || {};
        },
        
        startTimer() {
            this.stopTimer();
            
            if (this.currentQuestion.time_limit_sec) {
                this.timeLeft = this.currentQuestion.time_limit_sec;
                
                this.timerInterval = setInterval(() => {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        this.closeQuestion();
                    }
                }, 1000);
            }
        },
        
        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },
        
        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },
        
        async closeQuestion() {
            this.stopTimer();
            this.currentState = 'results';
            
            // Cargar resultados de la pregunta
            await this.loadQuestionResults();
        },
        
        async loadQuestionResults() {
            // Simular resultados (en producción, obtendrías datos reales de la BD)
            const options = this.currentQuestion.options || [];
            const results = {};
            
            options.forEach((opt, index) => {
                results[String.fromCharCode(65 + index)] = Math.floor(Math.random() * this.connectedParticipants);
            });
            
            this.questionResults = results;
            
            // Crear gráfica
            this.$nextTick(() => {
                this.createChart();
            });
            
            // Obtener top 5 temporal
            await this.loadTopParticipants();
        },
        
        createChart() {
            const canvas = document.getElementById('resultsChart');
            if (!canvas) return;
            
            if (this.resultsChart) {
                this.resultsChart.destroy();
            }
            
            const ctx = canvas.getContext('2d');
            const labels = Object.keys(this.questionResults);
            const data = Object.values(this.questionResults);
            const options = this.currentQuestion.options || [];
            
            const backgroundColors = options.map((opt, index) => {
                return opt.is_correct ? '#10b981' : '#ef4444';
            });
            
            this.resultsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Respuestas',
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
        },
        
        getCorrectAnswer() {
            const correctOption = this.currentQuestion.options?.find(opt => opt.is_correct);
            if (correctOption) {
                const index = this.currentQuestion.options.indexOf(correctOption);
                return String.fromCharCode(65 + index) + '. ' + correctOption.text;
            }
            return 'N/A';
        },
        
        async loadTopParticipants() {
            try {
                const response = await fetch(`../api/attempts.php?session_id=${this.sessionData.id}`);
                const data = await response.json();
                
                if (response.ok) {
                    // Top 5 por puntos acumulados hasta ahora (simplificado)
                    this.topParticipants = data.slice(0, 5).map(entry => ({
                        name: entry.participant_name,
                        points: entry.total_points
                    }));
                }
            } catch (error) {
                console.error('Error al cargar top participantes:', error);
            }
        },
        
        async nextQuestion() {
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                this.currentState = 'question';
                this.answeredCount = 0;
                this.startTimer();
            } else {
                // Mostrar ranking final
                await this.showFinalRanking();
            }
        },
        
        async showFinalRanking() {
            this.currentState = 'ranking';
            
            try {
                const response = await fetch(`../api/attempts.php?session_id=${this.sessionData.id}`);
                const data = await response.json();
                
                if (response.ok) {
                    this.ranking = data;
                }
            } catch (error) {
                console.error('Error al cargar ranking:', error);
            }
        },
        
        async endSession() {
            try {
                const response = await fetch('../api/sessions.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        id: this.sessionData.id,
                        ended_at: new Date().toISOString()
                    })
                });
                
                if (response.ok) {
                    alert('Sesión finalizada');
                    window.close();
                }
            } catch (error) {
                console.error('Error al finalizar sesión:', error);
            }
        }
    };
}


