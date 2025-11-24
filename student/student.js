function studentApp() {
    return {
        screen: 'entry', // entry, waiting, name, quiz, results
        sessionCode: new URLSearchParams(window.location.search).get('code') || '',
        participantName: '',
        sessionData: null,
        questions: [],
        currentQuestionIndex: 0,
        answers: {},
        answered: false,
        attemptId: null,
        results: {},
        ranking: [],
        modeConfig: null,
        timeLeft: 0,
        timerInterval: null,
        
        async init() {
            if (this.sessionCode) {
                await this.joinSession();
            }
        },
        
        async joinSession() {
            if (!this.sessionCode) {
                Swal.fire('Error', 'Ingresa un código de sesión', 'error');
                return;
            }
            
            this.screen = 'waiting';
            
            try {
                const response = await fetch(`../api/sessions.php?code=${this.sessionCode}`);
                const data = await response.json();
                
                if (response.ok && data) {
                    this.sessionData = data;
                    this.questions = data.questions || [];
                    this.modeConfig = data.mode_config;
                    
                    // Si es modo taller, ir directo a nombre
                    if (data.mode_type === 'workshop') {
                        this.screen = 'name';
                    } else {
                        // Modo live: esperar
                        this.screen = 'waiting';
                        // Aquí podrías implementar polling o WebSockets
                    }
                } else {
                    Swal.fire('Error', data.error || 'Sesión no encontrada', 'error');
                    this.screen = 'entry';
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
                this.screen = 'entry';
            }
        },
        
        async startQuiz() {
            if (!this.participantName.trim()) {
                Swal.fire('Error', 'Ingresa tu nombre', 'error');
                return;
            }
            
            try {
                // Crear intento
                const response = await fetch('../api/attempts.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        session_id: this.sessionData.id,
                        quiz_id: this.sessionData.quiz_id,
                        participant_name: this.participantName
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    this.attemptId = data.id;
                    this.screen = 'quiz';
                    this.startTimer();
                } else {
                    Swal.fire('Error', data.error || 'Error al iniciar quiz', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        },
        
        get currentQuestion() {
            return this.questions[this.currentQuestionIndex] || {};
        },
        
        selectOption(optionId) {
            if (this.answered && !this.modeConfig?.feedback_immediate) return;
            
            const questionId = this.currentQuestion.id;
            
            if (this.currentQuestion.allow_multiple_answers) {
                if (!this.answers[questionId]) {
                    this.answers[questionId] = [];
                }
                const index = this.answers[questionId].indexOf(optionId);
                if (index > -1) {
                    this.answers[questionId].splice(index, 1);
                } else {
                    this.answers[questionId].push(optionId);
                }
            } else {
                this.answers[questionId] = [optionId];
                this.answered = true;
            }
            
            // Si hay feedback inmediato, mostrar resultado
            if (this.modeConfig && this.modeConfig.feedback_immediate) {
                this.showFeedback();
            }
        },
        
        getOptionClass(optionId) {
            const questionId = this.currentQuestion.id;
            const selected = this.answers[questionId] && this.answers[questionId].includes(optionId);
            
            if (!this.answered && !this.modeConfig?.feedback_immediate) {
                return selected ? 'selected border-blue-500' : 'border-gray-300';
            }
            
            if (this.modeConfig && this.modeConfig.feedback_immediate) {
                const option = this.currentQuestion.options.find(o => o.id === optionId);
                if (option) {
                    if (option.is_correct) {
                        return 'correct border-green-500';
                    } else if (selected && !option.is_correct) {
                        return 'incorrect border-red-500';
                    }
                }
            }
            
            return selected ? 'selected border-blue-500' : 'border-gray-300';
        },
        
        showFeedback() {
            // El feedback visual ya se muestra con las clases CSS
            this.answered = true;
        },
        
        startTimer() {
            if (this.currentQuestion.time_limit_sec) {
                this.timeLeft = this.currentQuestion.time_limit_sec;
                
                this.timerInterval = setInterval(() => {
                    this.timeLeft--;
                    if (this.timeLeft <= 0) {
                        clearInterval(this.timerInterval);
                        this.nextQuestion();
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
        
        previousQuestion() {
            if (this.currentQuestionIndex > 0) {
                this.stopTimer();
                this.currentQuestionIndex--;
                this.answered = !!this.answers[this.currentQuestion.id];
                this.startTimer();
            }
        },
        
        nextQuestion() {
            this.stopTimer();
            if (this.currentQuestionIndex < this.questions.length - 1) {
                this.currentQuestionIndex++;
                this.answered = !!this.answers[this.currentQuestion.id];
                this.startTimer();
            }
        },
        
        async finishQuiz() {
            const result = await Swal.fire({
                title: '¿Finalizar quiz?',
                text: 'No podrás modificar tus respuestas',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, finalizar',
                cancelButtonText: 'Cancelar'
            });
            
            if (result.isConfirmed) {
                this.stopTimer();
                await this.submitAnswers();
            }
        },
        
        async submitAnswers() {
            try {
                // Preparar respuestas
                const answersArray = [];
                for (const [questionId, optionIds] of Object.entries(this.answers)) {
                    answersArray.push({
                        question_id: parseInt(questionId),
                        option_ids: Array.isArray(optionIds) ? optionIds.map(id => parseInt(id)) : [parseInt(optionIds)]
                    });
                }
                
                const response = await fetch('../api/attempts.php', {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        attempt_id: this.attemptId,
                        answers: answersArray
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    this.results = data;
                    this.screen = 'results';
                    
                    // Cargar ranking si está habilitado
                    if (this.modeConfig && this.modeConfig.show_ranking) {
                        await this.loadRanking();
                    }
                } else {
                    Swal.fire('Error', data.error || 'Error al enviar respuestas', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        },
        
        async loadRanking() {
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
        
        reset() {
            this.screen = 'entry';
            this.sessionCode = '';
            this.participantName = '';
            this.questions = [];
            this.currentQuestionIndex = 0;
            this.answers = {};
            this.attemptId = null;
            this.results = {};
            this.ranking = [];
            this.stopTimer();
        }
    };
}


