function adminApp() {
    return {
        quizzes: [],
        questions: [],
        currentQuiz: null,
        showCreateQuizModal: false,
        showQuestionsModal: false,
        showQuestionForm: false,
        showModesModal: false,
        editingQuiz: null,
        editingQuestion: null,
        quizForm: {
            title: '',
            description: '',
            points_per_question: 100,
            use_time_per_question: 0
        },
        questionForm: {
            text: '',
            image_url: '',
            video_url: '',
            audio_url: '',
            time_limit_sec: null,
            allow_multiple_answers: 0,
            explanation: '',
            options: [
                {text: '', is_correct: false},
                {text: '', is_correct: false}
            ]
        },
        modesForm: {
            live: {
                enabled: true
            },
            workshop: {
                enabled: false,
                available_from: '',
                available_to: '',
                max_attempts: 1,
                show_correction_at_end: true,
                show_explanations: true,
                show_ranking: true,
                public_ranking: true,
                feedback_immediate: false
            }
        },
        
        async init() {
            await this.loadQuizzes();
        },
        
        async loadQuizzes() {
            try {
                const response = await fetch('../api/quizzes.php');
                const data = await response.json();
                this.quizzes = data;
            } catch (error) {
                Swal.fire('Error', 'No se pudieron cargar los quizzes', 'error');
            }
        },
        
        async saveQuiz() {
            try {
                const url = this.editingQuiz 
                    ? `../api/quizzes.php?id=${this.editingQuiz.id}`
                    : '../api/quizzes.php';
                
                const method = this.editingQuiz ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method: method,
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(this.quizForm)
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    Swal.fire('Éxito', data.message || 'Quiz guardado exitosamente', 'success');
                    this.showCreateQuizModal = false;
                    this.resetQuizForm();
                    await this.loadQuizzes();
                } else {
                    Swal.fire('Error', data.error || 'Error al guardar', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        },
        
        editQuiz(quiz) {
            this.editingQuiz = quiz;
            this.quizForm = {
                title: quiz.title,
                description: quiz.description || '',
                points_per_question: quiz.points_per_question,
                use_time_per_question: quiz.use_time_per_question
            };
            this.showCreateQuizModal = true;
        },
        
        resetQuizForm() {
            this.editingQuiz = null;
            this.quizForm = {
                title: '',
                description: '',
                points_per_question: 100,
                use_time_per_question: 0
            };
        },
        
        async manageQuestions(quiz) {
            this.currentQuiz = quiz;
            this.showQuestionsModal = true;
            await this.loadQuestions(quiz.id);
        },
        
        async loadQuestions(quizId) {
            try {
                const response = await fetch(`../api/questions.php?quiz_id=${quizId}`);
                const data = await response.json();
                this.questions = data;
            } catch (error) {
                Swal.fire('Error', 'No se pudieron cargar las preguntas', 'error');
            }
        },
        
        async saveQuestion() {
            try {
                if (this.questionForm.options.length < 2) {
                    Swal.fire('Error', 'Debe haber al menos 2 opciones', 'error');
                    return;
                }
                
                const hasCorrect = this.questionForm.options.some(opt => opt.is_correct);
                if (!hasCorrect) {
                    Swal.fire('Error', 'Debe haber al menos una opción correcta', 'error');
                    return;
                }
                
                const url = this.editingQuestion
                    ? `../api/questions.php?id=${this.editingQuestion.id}`
                    : '../api/questions.php';
                
                const method = this.editingQuestion ? 'PUT' : 'POST';
                
                const payload = {
                    ...this.questionForm,
                    quiz_id: this.currentQuiz.id
                };
                
                const response = await fetch(url, {
                    method: method,
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    Swal.fire('Éxito', data.message || 'Pregunta guardada exitosamente', 'success');
                    this.showQuestionForm = false;
                    this.resetQuestionForm();
                    await this.loadQuestions(this.currentQuiz.id);
                } else {
                    Swal.fire('Error', data.error || 'Error al guardar', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        },
        
        editQuestion(question) {
            this.editingQuestion = question;
            this.questionForm = {
                text: question.text,
                image_url: question.image_url || '',
                video_url: question.video_url || '',
                audio_url: question.audio_url || '',
                time_limit_sec: question.time_limit_sec,
                allow_multiple_answers: question.allow_multiple_answers ? 1 : 0,
                explanation: question.explanation || '',
                options: question.options.map(opt => ({
                    text: opt.text,
                    is_correct: opt.is_correct ? true : false
                }))
            };
            this.showQuestionForm = true;
        },
        
        async deleteQuestion(questionId) {
            const result = await Swal.fire({
                title: '¿Eliminar pregunta?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });
            
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`../api/questions.php?id=${questionId}`, {
                        method: 'DELETE'
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        Swal.fire('Éxito', 'Pregunta eliminada', 'success');
                        await this.loadQuestions(this.currentQuiz.id);
                    } else {
                        Swal.fire('Error', data.error || 'Error al eliminar', 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Error de conexión', 'error');
                }
            }
        },
        
        resetQuestionForm() {
            this.editingQuestion = null;
            this.questionForm = {
                text: '',
                image_url: '',
                video_url: '',
                audio_url: '',
                time_limit_sec: null,
                allow_multiple_answers: 0,
                explanation: '',
                options: [
                    {text: '', is_correct: false},
                    {text: '', is_correct: false}
                ]
            };
        },
        
        async startSession(quiz) {
            const {value: modeType} = await Swal.fire({
                title: 'Seleccionar modo',
                input: 'select',
                inputOptions: {
                    'live': 'Evento en Vivo',
                    'workshop': 'Modo Taller'
                },
                inputPlaceholder: 'Selecciona un modo',
                showCancelButton: true,
                confirmButtonText: 'Iniciar',
                cancelButtonText: 'Cancelar'
            });
            
            if (modeType) {
                try {
                    const response = await fetch('../api/sessions.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            quiz_id: quiz.id,
                            mode_type: modeType
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        const sessionUrl = `../presenter/index.php?session=${data.session_code}`;
                        const studentUrl = `../student/index.php?code=${data.session_code}`;
                        
                        await Swal.fire({
                            title: 'Sesión creada',
                            html: `
                                <p>Código de sesión: <strong>${data.session_code}</strong></p>
                                <div class="mt-4">
                                    <a href="${sessionUrl}" target="_blank" 
                                       class="bg-blue-500 text-white px-4 py-2 rounded block mb-2 text-center">
                                        Abrir Pantalla Presentador
                                    </a>
                                    <a href="${studentUrl}" target="_blank" 
                                       class="bg-green-500 text-white px-4 py-2 rounded block text-center">
                                        Abrir Interfaz Estudiante
                                    </a>
                                </div>
                            `,
                            icon: 'success',
                            width: 600
                        });
                    } else {
                        Swal.fire('Error', data.error || 'Error al crear sesión', 'error');
                    }
                } catch (error) {
                    Swal.fire('Error', 'Error de conexión', 'error');
                }
            }
        },
        
        async viewAnalytics(quiz) {
            window.location.href = `analytics.php?quiz_id=${quiz.id}`;
        },
        
        async configureModes(quiz) {
            this.currentQuiz = quiz;
            this.showModesModal = true;
            
            // Cargar modos existentes
            try {
                const response = await fetch(`../api/quizzes.php?id=${quiz.id}`);
                const data = await response.json();
                
                if (data && data.modes) {
                    const liveMode = data.modes.find(m => m.mode_type === 'live');
                    const workshopMode = data.modes.find(m => m.mode_type === 'workshop');
                    
                    if (liveMode) {
                        this.modesForm.live.enabled = liveMode.enabled == 1;
                    }
                    
                    if (workshopMode) {
                        this.modesForm.workshop.enabled = workshopMode.enabled == 1;
                        this.modesForm.workshop.available_from = workshopMode.available_from ? 
                            workshopMode.available_from.replace(' ', 'T').substring(0, 16) : '';
                        this.modesForm.workshop.available_to = workshopMode.available_to ? 
                            workshopMode.available_to.replace(' ', 'T').substring(0, 16) : '';
                        this.modesForm.workshop.max_attempts = workshopMode.max_attempts || 1;
                        this.modesForm.workshop.show_correction_at_end = workshopMode.show_correction_at_end == 1;
                        this.modesForm.workshop.show_explanations = workshopMode.show_explanations == 1;
                        this.modesForm.workshop.show_ranking = workshopMode.show_ranking == 1;
                        this.modesForm.workshop.public_ranking = workshopMode.public_ranking == 1;
                        this.modesForm.workshop.feedback_immediate = workshopMode.feedback_immediate == 1;
                    }
                }
            } catch (error) {
                console.error('Error al cargar modos:', error);
            }
        },
        
        async saveModes() {
            try {
                const modes = [];
                
                if (this.modesForm.live.enabled) {
                    modes.push({
                        mode_type: 'live',
                        enabled: 1
                    });
                }
                
                if (this.modesForm.workshop.enabled) {
                    modes.push({
                        mode_type: 'workshop',
                        enabled: 1,
                        available_from: this.modesForm.workshop.available_from || null,
                        available_to: this.modesForm.workshop.available_to || null,
                        max_attempts: this.modesForm.workshop.max_attempts || 1,
                        show_correction_at_end: this.modesForm.workshop.show_correction_at_end ? 1 : 0,
                        show_explanations: this.modesForm.workshop.show_explanations ? 1 : 0,
                        show_ranking: this.modesForm.workshop.show_ranking ? 1 : 0,
                        public_ranking: this.modesForm.workshop.public_ranking ? 1 : 0,
                        feedback_immediate: this.modesForm.workshop.feedback_immediate ? 1 : 0
                    });
                }
                
                // Actualizar modos en el quiz
                const response = await fetch(`../api/quiz_modes.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        quiz_id: this.currentQuiz.id,
                        modes: modes
                    })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    Swal.fire('Éxito', 'Modos configurados exitosamente', 'success');
                    this.showModesModal = false;
                } else {
                    Swal.fire('Error', data.error || 'Error al guardar modos', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        }
    };
}

