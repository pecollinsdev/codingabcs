// Admin quiz edit functionality
document.addEventListener('DOMContentLoaded', function() {
    const editQuizForm = document.getElementById('editQuizForm');
    const quizId = editQuizForm.dataset.quizId;
    const questionsContainer = document.getElementById('questionsTable');
    const questionModal = new bootstrap.Modal(document.getElementById('questionModal'));
    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const questionForm = document.getElementById('questionForm');
    const addQuestionBtn = document.querySelector('[data-bs-target="#questionModal"]');
    const saveQuestionBtn = document.getElementById('saveQuestionBtn');
    const questionTypeSelect = document.getElementById('questionType');
    const answersContainer = document.getElementById('answersContainer');
    const addAnswerBtn = document.getElementById('addAnswerBtn');

    // Utility function to get cookie value
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // Utility function to check if JWT token is valid
    function isTokenValid() {
        const token = getCookie('jwt_token');
        if (!token) return false;
        
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            return payload.exp > Date.now() / 1000;
        } catch (e) {
            return false;
        }
    }

    // Utility function to handle API requests with retry
    async function fetchWithRetry(url, options = {}, retries = 1) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${getCookie('jwt_token')}`,
                    ...options.headers
                },
                credentials: 'include'
            });

            if (response.status === 401 && retries > 0) {
                // Token might be expired, try to refresh
                const refreshResponse = await fetch('/codingabcs/api/public/auth/refresh', {
                    method: 'POST',
                    credentials: 'include'
                });
                
                if (refreshResponse.ok) {
                    // Retry the original request with new token
                    return fetchWithRetry(url, options, retries - 1);
                }
            }

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response;
        } catch (error) {
            if (retries > 0) {
                // Wait for 1 second before retrying
                await new Promise(resolve => setTimeout(resolve, 1000));
                return fetchWithRetry(url, options, retries - 1);
            }
            throw error;
        }
    }

    // Utility functions for modals
    function showAlert(message) {
        document.getElementById('alertMessage').textContent = message;
        alertModal.show();
    }

    function showConfirm(message, callback) {
        document.getElementById('confirmMessage').textContent = message;
        const confirmButton = document.getElementById('confirmButton');
        
        // Remove any existing event listeners
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        
        newConfirmButton.addEventListener('click', function() {
            confirmModal.hide();
            callback();
        });
        
        confirmModal.show();
    }

    // Load quiz data
    async function loadQuizData() {
        try {
            if (!isTokenValid()) {
                throw new Error('Session expired. Please log in again.');
            }

            const response = await fetchWithRetry(`/codingabcs/api/public/quizzes/${quizId}`);
            const result = await response.json();
            
            if (result.status === 'success' && result.data) {
                const quiz = result.data;
                
                // Fill in form fields
                document.getElementById('title').value = quiz.title || '';
                document.getElementById('description').value = quiz.description || '';
                document.getElementById('category').value = quiz.category || '';
                document.getElementById('level').value = quiz.level || 'beginner';
                document.getElementById('status').value = quiz.is_active ? '1' : '0';

                // Load questions
                await loadQuestions();
            } else {
                throw new Error('Invalid quiz data received');
            }
        } catch (error) {
            if (error.message === 'Session expired. Please log in again.') {
                window.location.href = '/codingabcs/client/public/login';
            } else {
                showAlert('Failed to load quiz data. Please try again.');
            }
        }
    }

    // Load questions
    async function loadQuestions() {
        try {
            if (!isTokenValid()) {
                throw new Error('Session expired. Please log in again.');
            }

            const response = await fetchWithRetry(`/codingabcs/api/public/quizzes/${quizId}/questions`);
            const result = await response.json();
            
            if (result.status === 'success') {
                const questions = result.data?.questions || [];
                const tbody = questionsContainer.querySelector('tbody');
                tbody.innerHTML = '';

                if (questions.length > 0) {
                    questions.forEach((question, index) => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td>${index + 1}</td>
                            <td>${question.question_text}</td>
                            <td class="text-capitalize">${question.category || 'General'}</td>
                            <td class="text-capitalize">${question.type.replace('_', ' ')}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-primary edit-question" data-question='${JSON.stringify(question)}'>
                                        <i class="fas fa-edit"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-question" data-id="${question.id}">
                                        <i class="fas fa-trash"></i>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    setupQuestionActions();
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center">No questions found</td></tr>';
                }
            } else {
                throw new Error(result.message || 'Failed to load questions');
            }
        } catch (error) {
            if (error.message === 'Session expired. Please log in again.') {
                window.location.href = '/codingabcs/client/public/login';
            } else {
                const tbody = questionsContainer.querySelector('tbody');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Error loading questions</td></tr>';
            }
        }
    }

    // Setup question action buttons
    function setupQuestionActions() {
        // Edit question
        document.querySelectorAll('.edit-question').forEach(button => {
            button.addEventListener('click', function() {
                try {
                    const question = JSON.parse(this.dataset.question);
                    
                    // Reset form
                    document.getElementById('questionId').value = question.id;
                    document.getElementById('questionText').value = question.question_text;
                    document.getElementById('questionType').value = question.type;
                    
                    // Update modal title
                    document.querySelector('#questionModal .modal-title').textContent = 'Edit Question';
                    
                    // Show/hide sections based on question type
                    const answersSection = document.getElementById('answersSection');
                    const codingSection = document.getElementById('codingSection');
                    
                    if (question.type === 'multiple_choice' || question.type === 'true_false') {
                        if (answersSection) answersSection.style.display = 'block';
                        if (codingSection) codingSection.style.display = 'none';
                        loadAnswers(question);
                    } else if (question.type === 'coding') {
                        if (answersSection) answersSection.style.display = 'none';
                        if (codingSection) codingSection.style.display = 'block';
                        
                        // Set coding question fields if they exist
                        const starterCode = document.getElementById('starterCode');
                        const expectedOutput = document.getElementById('expectedOutput');
                        const language = document.getElementById('language');
                        const hiddenInput = document.getElementById('hiddenInput');
                        
                        if (starterCode) starterCode.value = question.starter_code || '';
                        if (expectedOutput) expectedOutput.value = question.expected_output || '';
                        if (language) language.value = question.language || '';
                        if (hiddenInput) hiddenInput.value = question.hidden_input || '';
                    }
                    
                    // Show modal
                    questionModal.show();
                } catch (error) {
                    alert('Failed to load question: ' + error.message);
                }
            });
        });

        // Delete question
        document.querySelectorAll('.delete-question').forEach(button => {
            button.addEventListener('click', async function() {
                const questionId = this.dataset.id;
                
                showConfirm('Are you sure you want to delete this question?', async () => {
                    try {
                        const response = await fetch(`/codingabcs/api/public/questions/${questionId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'Authorization': `Bearer ${getCookie('jwt_token')}`
                            },
                            credentials: 'include'
                        });

                        const responseData = await response.json();

                        if (!response.ok) {
                            throw new Error(responseData.message || 'Failed to delete question');
                        }

                        if (responseData.status === 'success') {
                            showAlert('Question deleted successfully!');
                            // Wait for 2 seconds before reloading to see logs
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            throw new Error(responseData.message || 'Failed to delete question');
                        }
                    } catch (error) {
                        showAlert('Failed to delete question: ' + error.message);
                    }
                });
            });
        });
    }

    // Load answers based on question type
    function loadAnswers(question) {
        // Clear existing answers
        answersContainer.innerHTML = '';
        
        if (question.answers && Array.isArray(question.answers)) {
            question.answers.forEach((answer, index) => {
                addAnswerField(
                    index,
                    answer.answer_text,
                    answer.is_correct === 1 || answer.is_correct === true
                );
            });
        } else {
            // Add at least two answer fields for new questions
            addAnswerField(0);
            addAnswerField(1);
        }

        // Enable/disable add answer button based on question type
        if (addAnswerBtn) {
            addAnswerBtn.style.display = question.type === 'true_false' ? 'none' : 'block';
        }

        // For true/false questions, set predefined options
        if (question.type === 'true_false') {
            answersContainer.innerHTML = '';
            addAnswerField(0, 'True', question.answers?.[0]?.is_correct);
            addAnswerField(1, 'False', question.answers?.[1]?.is_correct);
            
            // Disable editing of true/false text
            const answerInputs = answersContainer.querySelectorAll('input[type="text"]');
            answerInputs.forEach(input => {
                input.readOnly = true;
            });
        }
    }

    // Add answer field
    function addAnswerField(index, value = '', isCorrect = false) {
        const answerDiv = document.createElement('div');
        answerDiv.className = 'answer-field mb-3';
        answerDiv.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="correctAnswer" 
                        id="answer${index}Radio" value="${index}" ${isCorrect ? 'checked' : ''}>
                </div>
                <div class="flex-grow-1">
                    <input type="text" class="form-control" name="answers[]" 
                        placeholder="Enter answer option" value="${value}" required>
                </div>
                <button type="button" class="btn btn-danger btn-sm remove-answer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        answersContainer.appendChild(answerDiv);

        // Add event listener for remove button
        answerDiv.querySelector('.remove-answer').addEventListener('click', function() {
            answerDiv.remove();
            updateAnswerIndices();
        });
    }

    function updateAnswerIndices() {
        const radioButtons = document.querySelectorAll('input[name="correctAnswer"]');
        radioButtons.forEach((radio, index) => {
            radio.value = index;
            radio.id = `answer${index}Radio`;
        });
    }

    // Question type change handler
    questionTypeSelect.addEventListener('change', function() {
        const type = this.value;
        answersContainer.innerHTML = '';
        
        if (type === 'true_false') {
            addAnswerField(0, 'True');
            addAnswerField(1, 'False');
            
            // Disable editing of true/false text
            const answerInputs = answersContainer.querySelectorAll('input[type="text"]');
            answerInputs.forEach(input => {
                input.readOnly = true;
            });
            
            // Hide add answer button
            if (addAnswerBtn) {
                addAnswerBtn.style.display = 'none';
            }
        } else if (type === 'multiple_choice') {
            // Add minimum two answer fields for multiple choice
            addAnswerField(0);
            addAnswerField(1);
            
            // Show add answer button
            if (addAnswerBtn) {
                addAnswerBtn.style.display = 'block';
            }
        } else if (type === 'coding') {
            // Hide answers section and show coding section
            const answersSection = document.getElementById('answersSection');
            const codingSection = document.getElementById('codingSection');
            
            if (answersSection) answersSection.style.display = 'none';
            if (codingSection) codingSection.style.display = 'block';
        }
    });

    // Add answer button click handler
    if (addAnswerBtn) {
        addAnswerBtn.addEventListener('click', function() {
            const currentAnswers = answersContainer.querySelectorAll('.answer-field').length;
            addAnswerField(currentAnswers);
        });
    }

    // Handle save question button
    saveQuestionBtn.addEventListener('click', async function() {
        const questionId = document.getElementById('questionId').value;
        const questionText = document.getElementById('questionText').value;
        const questionType = document.getElementById('questionType').value;
        
        // Validate required fields
        if (!questionText.trim()) {
            showAlert('Please enter a question text');
            return;
        }

        if (!questionType) {
            showAlert('Please select a question type');
            return;
        }

        let questionData = {
            question_text: questionText.trim(),
            type: questionType
        };

        // Add question ID for updates
        if (questionId) {
            questionData.id = parseInt(questionId);
        }

        // Handle different question types
        if (questionType === 'multiple_choice' || questionType === 'true_false') {
            // Get and validate answers
            const answerInputs = answersContainer.querySelectorAll('input[type="text"]');
            const radioButtons = answersContainer.querySelectorAll('input[type="radio"]');
            
            if (answerInputs.length === 0) {
                showAlert('Please add at least one answer');
                return;
            }

            const answers = Array.from(answerInputs).map((input, index) => {
                const answerText = input.value.trim();
                if (!answerText) {
                    showAlert('Please fill in all answer fields');
                    return null;
                }
                return {
                    answer_text: answerText,
                    is_correct: radioButtons[index]?.checked || false
                };
            }).filter(answer => answer !== null);

            if (answers.length === 0) {
                showAlert('Please add at least one answer');
                return;
            }

            // Validate that at least one answer is marked as correct
            const hasCorrectAnswer = answers.some(answer => answer.is_correct);
            if (!hasCorrectAnswer) {
                showAlert('Please mark at least one answer as correct');
                return;
            }

            questionData.answers = answers;
        } else if (questionType === 'coding') {
            const starterCode = document.getElementById('starterCode').value;
            const expectedOutput = document.getElementById('expectedOutput').value;
            const language = document.getElementById('language').value;
            const hiddenInput = document.getElementById('hiddenInput').value;

            if (!starterCode.trim()) {
                showAlert('Please enter starter code');
                return;
            }

            if (!expectedOutput.trim()) {
                showAlert('Please enter expected output');
                return;
            }

            if (!language) {
                showAlert('Please select a programming language');
                return;
            }

            questionData.starter_code = starterCode;
            questionData.expected_output = expectedOutput;
            questionData.language = language;
            questionData.hidden_input = hiddenInput || ''; // Ensure hidden_input is always set, even if empty
        }

        try {
            let url, method;
            if (questionId) {
                // Update existing question
                url = `/codingabcs/api/public/questions/${questionId}`;
                method = 'PATCH';
            } else {
                // Create new question
                url = `/codingabcs/api/public/quizzes/${quizId}/questions`;
                method = 'POST';
                questionData.quiz_id = parseInt(quizId);
            }

            const response = await fetchWithRetry(url, {
                method: method,
                body: JSON.stringify(questionData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save question');
            }

            const result = await response.json();
            if (result.status === 'success') {
                showAlert('Question saved successfully!');
                questionModal.hide();
                await loadQuestions();
            } else {
                throw new Error(result.message || 'Failed to save question');
            }
        } catch (error) {
            showAlert('Failed to save question: ' + error.message);
        }
    });

    // Handle add question button
    addQuestionBtn.addEventListener('click', function() {
        // Reset form
        document.getElementById('questionId').value = '';
        document.getElementById('questionText').value = '';
        document.getElementById('questionType').value = 'multiple_choice';
        document.getElementById('starterCode').value = '';
        document.getElementById('expectedOutput').value = '';
        document.getElementById('language').value = '';
        document.getElementById('hiddenInput').value = '';
        answersContainer.innerHTML = '';
        addAnswerField(0);
        addAnswerField(1);
        
        // Update modal title
        document.querySelector('#questionModal .modal-title').textContent = 'Add Question';
    });

    // Handle form submission
    editQuizForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            category: document.getElementById('category').value,
            level: document.getElementById('level').value,
            is_active: document.getElementById('status').value === '1'
        };

        try {
            const response = await fetch(`/codingabcs/api/public/quizzes/${quizId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update quiz');
            }

            showAlert('Quiz updated successfully!');
            window.location.href = '/codingabcs/client/public/admin_quizzes';
        } catch (error) {
            showAlert('Failed to update quiz: ' + error.message);
        }
    });

    // Load quiz data when page loads
    loadQuizData();
}); 