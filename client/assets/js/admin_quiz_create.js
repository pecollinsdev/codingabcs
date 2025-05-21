// Admin quiz creation functionality
document.addEventListener('DOMContentLoaded', function() {
    const createQuizForm = document.getElementById('createQuizForm');
    const questionsContainer = document.getElementById('questionsContainer');
    let currentQuestionIndex = 0;
    let quizId = null;
    const alertModal = new bootstrap.Modal(document.getElementById('alertModal'));

    // Utility function to show alerts
    function showAlert(message) {
        document.getElementById('alertMessage').textContent = message;
        alertModal.show();
    }

    // Handle quiz form submission
    createQuizForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Capitalize first letter of each word in category
        const categoryInput = document.getElementById('category');
        const category = categoryInput.value
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
        categoryInput.value = category;
        
        const formData = {
            title: document.getElementById('title').value,
            description: document.getElementById('description').value,
            category: category,
            level: document.getElementById('level').value,
            is_active: document.getElementById('status').value === '1',
            time_limit: document.getElementById('timeLimit').value,
            questions: []
        };

        try {
            const response = await fetch('/codingabcs/api/public/quizzes', {
                method: 'POST',
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
                throw new Error(errorData.message || 'Failed to create quiz');
            }

            const result = await response.json();
            quizId = result.data.id;
            showQuestionForm();
        } catch (error) {
            showAlert('Failed to create quiz: ' + error.message);
        }
    });

    // Show question form
    function showQuestionForm() {
        createQuizForm.style.display = 'none';
        questionsContainer.style.display = 'block';
        addQuestionForm();
    }

    // Add new question form
    function addQuestionForm() {
        const questionDiv = document.createElement('div');
        questionDiv.className = 'card mb-4';
        questionDiv.innerHTML = `
            <div class="card-body">
                <h5 class="card-title">Question ${currentQuestionIndex + 1}</h5>
                <div class="mb-3">
                    <label class="form-label">Question Type</label>
                    <select class="form-select question-type" required>
                        <option value="">Select question type</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="coding">Coding Question</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <textarea class="form-control question-text" rows="3" required></textarea>
                </div>
                <div class="answers-container"></div>
                <div class="mt-3">
                    <button type="button" class="btn btn-primary save-question">Save Question</button>
                    <button type="button" class="btn btn-success add-question">Add Another Question</button>
                    <button type="button" class="btn btn-success finish-quiz">Finish Quiz</button>
                </div>
            </div>
        `;

        questionsContainer.appendChild(questionDiv);

        // Handle question type change
        const typeSelect = questionDiv.querySelector('.question-type');
        typeSelect.addEventListener('change', function() {
            updateAnswerFields(questionDiv, this.value);
        });

        // Handle save question
        const saveBtn = questionDiv.querySelector('.save-question');
        saveBtn.addEventListener('click', async function() {
            await saveQuestion(questionDiv);
        });

        // Handle add another question
        const addBtn = questionDiv.querySelector('.add-question');
        addBtn.addEventListener('click', function() {
            currentQuestionIndex++;
            addQuestionForm();
        });

        // Handle finish quiz
        const finishBtn = questionDiv.querySelector('.finish-quiz');
        finishBtn.addEventListener('click', function() {
            window.location.href = '/codingabcs/client/public/admin_quizzes';
        });
    }

    // Update answer fields based on question type
    function updateAnswerFields(questionDiv, type) {
        const answersContainer = questionDiv.querySelector('.answers-container');
        answersContainer.innerHTML = '';

        if (type === 'multiple_choice') {
            answersContainer.innerHTML = `
                <div class="mb-3">
                    <label class="form-label">Answers</label>
                    <div class="answer-inputs">
                        <div class="input-group mb-2">
                            <input type="text" class="form-control answer-text" placeholder="Answer text">
                            <div class="input-group-text">
                                <input type="radio" name="correct_answer" value="0" class="form-check-input">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary add-answer">Add Answer</button>
                </div>
            `;

            // Handle add answer button
            const addAnswerBtn = answersContainer.querySelector('.add-answer');
            addAnswerBtn.addEventListener('click', function() {
                const answerInputs = answersContainer.querySelector('.answer-inputs');
                const newAnswer = document.createElement('div');
                newAnswer.className = 'input-group mb-2';
                newAnswer.innerHTML = `
                    <input type="text" class="form-control answer-text" placeholder="Answer text">
                    <div class="input-group-text">
                        <input type="radio" name="correct_answer" value="${answerInputs.children.length}" class="form-check-input">
                    </div>
                `;
                answerInputs.appendChild(newAnswer);
            });
        } else if (type === 'coding') {
            answersContainer.innerHTML = `
                <input type="hidden" name="type" value="coding">
                <input type="hidden" name="question_type" value="coding">
                <div class="mb-3">
                    <label class="form-label">Programming Language</label>
                    <select class="form-select language" required>
                        <option value="">Select language</option>
                        <option value="javascript">JavaScript</option>
                        <option value="python">Python</option>
                        <option value="java">Java</option>
                        <option value="php">PHP</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Starter Code</label>
                    <textarea class="form-control starter-code" rows="5" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Expected Output</label>
                    <textarea class="form-control expected-output" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Hidden Input (Optional)</label>
                    <textarea class="form-control hidden-input" rows="3" placeholder="Input that will be passed to the code but not shown to the user"></textarea>
                </div>
            `;
        }
    }

    // Save question
    async function saveQuestion(questionDiv) {
        const type = questionDiv.querySelector('.question-type').value;
        const text = questionDiv.querySelector('.question-text').value;
        
        if (!type || !text) {
            showAlert('Please fill in all required fields');
            return;
        }

        const questionData = {
            question_text: text,
            type: type,
            quiz_id: quizId
        };

        if (type === 'multiple_choice') {
            const answers = Array.from(questionDiv.querySelectorAll('.answer-text')).map(input => input.value);
            const correctAnswerIndex = questionDiv.querySelector('input[name="correct_answer"]:checked')?.value;
            
            if (answers.length < 2 || !correctAnswerIndex) {
                showAlert('Please add at least 2 answers and select the correct one');
                return;
            }

            questionData.answers = answers.map((text, index) => ({
                answer_text: text,
                is_correct: index === parseInt(correctAnswerIndex)
            }));
        } else if (type === 'coding') {
            const language = questionDiv.querySelector('.language').value;
            const starterCode = questionDiv.querySelector('.starter-code').value;
            const expectedOutput = questionDiv.querySelector('.expected-output').value;
            const hiddenInput = questionDiv.querySelector('.hidden-input').value;

            if (!language || !starterCode || !expectedOutput) {
                showAlert('Please fill in all required fields');
                return;
            }

            questionData.language = language;
            questionData.starter_code = starterCode;
            questionData.expected_output = expectedOutput;
            questionData.hidden_input = hiddenInput || ''; // Ensure hidden_input is always set, even if empty
        }

        try {
            const response = await fetch(`/codingabcs/api/public/quizzes/${quizId}/questions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify(questionData)
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to save question');
            }

            showAlert('Question saved successfully!');
        } catch (error) {
            showAlert('Failed to save question: ' + error.message);
        }
    }
}); 