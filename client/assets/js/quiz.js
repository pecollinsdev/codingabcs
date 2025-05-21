// Base paths
const API_BASE = '/codingabcs/api/public';
const CLIENT_BASE = window.location.origin + '/codingabcs/client/public';

// Return Authorization header if JWT cookie exists
function authHeaders() {
  const token = document.cookie
    .split('; ')
    .find(c => c.startsWith('jwt_token='))
    ?.split('=')[1];
  return token ? { Authorization: `Bearer ${token}` } : {};
}

class Quiz {
  constructor() {
    this.quizId = this.getQuizId();
    if (!this.quizId) return console.error('Invalid quiz ID');

    // Timestamp for timeTaken calculation
    this.startTime = Date.now();

    this.questions = [];
    this.currentIndex = 0;
    this.answers = {};
    this.timeLeft = 0;
    this.isSubmitting = false;
    this.exitModal = null;
    this.editor = null; // Initialize editor as null
    this.editorInitialized = false; // Track if editor is initialized

    this.cacheDom();
    this.setupListeners();
    this.init().catch(e => console.error('Init error:', e));
  }

  cacheDom() {
    this.dom = {
      title: document.getElementById('quizTitle'),
      counter: document.getElementById('questionCounter'),
      progressBar: document.querySelector('.progress-bar'),
      form: document.getElementById('quizForm'),
      options: document.getElementById('multipleChoiceOptions'),
      editorContainer: document.getElementById('codingEditor'),
      runBtn: document.getElementById('runCode'),
      submitBtn: document.getElementById('submitQuiz'),
      prevBtn: document.getElementById('prevQuestion'),
      nextBtn: document.getElementById('nextQuestion'),
      exitModal: document.getElementById('exitQuizModal'),
    };
  }

  setupListeners() {
    this.dom.form?.addEventListener('submit', e => e.preventDefault());
    this.dom.prevBtn?.addEventListener('click', () => this.changeQuestion(-1));
    this.dom.nextBtn?.addEventListener('click', () => this.changeQuestion(1));
    this.dom.submitBtn?.addEventListener('click', () => this.submitQuiz());
    
    // Add change listener for multiple choice options
    this.dom.options?.addEventListener('change', () => {
      this.saveProgress();
      this.updateButtonStates();
    });
    
    // Add change listener for code editor
    if (this.editor) {
      this.editor.onDidChangeModelContent(() => {
        this.saveProgress();
        this.updateButtonStates();
      });
    }
    
    this.dom.runBtn?.addEventListener('click', () => this.runCode());

    const exitBtn = document.getElementById('exitQuizBtn');
    if (exitBtn && this.dom.exitModal) {
      // Initialize the modal once
      this.exitModal = new bootstrap.Modal(this.dom.exitModal);
      
      exitBtn.addEventListener('click', () => {
        this.saveProgress();
        this.exitModal.show();
      });
      
      const dangerBtn = this.dom.exitModal.querySelector('.btn-danger');
      dangerBtn?.addEventListener('click', () => {
        window.location.href = CLIENT_BASE + '/quizzes';
      });
    }
  }

  updateButtonStates() {
    const nextBtn = document.getElementById('nextQuestion');
    const submitBtn = document.getElementById('submitQuiz');
    const isAnswered = this.isQuestionAnswered();
    const isLastQuestion = this.currentIndex === this.questions.length - 1;

    if (nextBtn) {
      nextBtn.disabled = isLastQuestion || !isAnswered;
    }
    if (submitBtn) {
      submitBtn.style.display = (isLastQuestion && isAnswered) ? 'block' : 'none';
    }
  }

  async init() {
    await this.loadQuiz();
    await this.loadQuestions();
    await this.loadProgress();
    
    // Only save progress if we're actually starting a quiz (currentIndex > 0 or answers exist)
    if (this.currentIndex > 0 || (this.answers && Object.keys(this.answers).length > 0)) {
      await this.saveProgress();
    }
    
    // Wait for Monaco to be loaded before initializing
    if (window.monacoLoaded) {
      this.initializeEditor();
      this.display();
    } else {
      // If Monaco isn't loaded yet, wait for it
      window.addEventListener('monacoLoaded', () => {
        this.initializeEditor();
        this.display();
      });
    }
    
    if (this.timeLeft) this.startTimer();
  }

  initializeEditor() {
    if (this.editorInitialized) return;

    const editorElement = document.getElementById('editor');
    if (!editorElement) {
      console.error('Editor element not found');
      return;
    }

    // Get the current question's language or default to javascript
    const currentQuestion = this.questions[this.currentIndex];
    const defaultLanguage = currentQuestion?.language?.toLowerCase() || 'javascript';
    
    // Map language names to Monaco language IDs
    const languageMap = {
      'python': 'python',
      'javascript': 'javascript',
      'typescript': 'typescript',
      'java': 'java',
      'c': 'c',
      'cpp': 'cpp',
      'php': 'php',
      'ruby': 'ruby',
      'go': 'go',
      'rust': 'rust',
      'cs': 'csharp'
    };

    // Get current theme
    const isDarkTheme = document.documentElement.classList.contains('dark-theme');
    const theme = isDarkTheme ? 'vs-dark' : 'vs';

    this.editor = monaco.editor.create(editorElement, {
      value: '',
      language: languageMap[defaultLanguage] || 'javascript',
      theme: theme,
      automaticLayout: true,
      minimap: { enabled: false },
      scrollBeyondLastLine: false,
      fontSize: 14,
      lineNumbers: 'on',
      roundedSelection: false,
      scrollbar: {
        vertical: 'hidden',
        horizontal: 'hidden',
        useShadows: false,
        verticalScrollbarSize: 0,
        horizontalScrollbarSize: 0
      },
      readOnly: false,
      cursorStyle: 'line',
      autoIndent: 'full',
      formatOnPaste: true,
      formatOnType: true,
      suggestOnTriggerCharacters: true,
      quickSuggestions: {
        other: true,
        comments: true,
        strings: true
      },
      parameterHints: {
        enabled: true
      },
      wordBasedSuggestions: true,
      snippets: {
        enabled: true
      },
      // Enable general semantic highlighting and bracket pair colorization for all languages
      'bracketPairColorization.enabled': true,
      'semanticHighlighting.enabled': true,
      'editor.semanticHighlighting.enabled': true
    });

    // Add custom commands
    this.editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.Enter, () => {
      this.runCode();
    });

    // Add change listener for code changes
    this.editor.onDidChangeModelContent(() => {
      const currentCode = this.editor.getValue();
      const currentQuestion = this.questions[this.currentIndex];
      
      if (currentQuestion && currentQuestion.type === 'coding') {
        // Only save if the code is different from starter code
        if (currentCode !== currentQuestion.starter_code) {
          this.answers[currentQuestion.id] = currentCode;
          this.saveProgress();
        }
      }
    });

    this.editorInitialized = true;
  }

  getQuizId() {
    const match = window.location.pathname.match(/\/(\d+)(?:$|\?)/);
    return match ? parseInt(match[1], 10) : null;
  }

  async apiGet(path) {
    const res = await fetch(API_BASE + path, {
      credentials: 'include',
      headers: authHeaders()
    });
    if (!res.ok) throw new Error(`GET ${path} failed: ${res.status}`);
    const json = await res.json();
    return json.data || json;
  }

  async loadQuiz() {
    const data = await this.apiGet(`/quizzes/${this.quizId}`);
    this.dom.title.textContent = data.title;
    this.timeLeft = (data.time_limit || 0) * 60;
  }

  async loadQuestions() {
    const result = await this.apiGet(`/quizzes/${this.quizId}/questions`);
    this.questions = result.questions;
  }

  async loadProgress() {
    try {
      const data = await this.apiGet(`/quizzes/${this.quizId}/progress`);
      this.currentIndex = data.current_question || 0;
      this.answers = data.answers || {};
      this.updateUrl();
    } catch {
      // No saved progress
    }
  }

  async saveProgress() {
    // Only save progress if there are actual answers
    if (!this.answers || Object.keys(this.answers).length === 0) {
      return;
    }

    const progress = {
      current_question: this.currentIndex,
      answers: this.answers,
      last_updated: Math.floor(Date.now() / 1000)
    };

    try {
      const res = await fetch(`${API_BASE}/quizzes/${this.quizId}/progress`, {
        method: 'POST',
        credentials: 'include',
        headers: { 
          'Content-Type': 'application/json',
          ...authHeaders()
        },
        body: JSON.stringify(progress)
      });

      if (!res.ok) throw new Error('Failed to save progress');
      
      const data = await res.json();
      if (data.status === 'success') {
        // Update the URL to reflect current question
        this.updateUrl();
        // Force a refresh of the quizzes page to update the resume button
        if (window.parent && window.parent.updateQuizzes) {
          window.parent.updateQuizzes();
        }
      }
    } catch (error) {
      console.error('Error saving progress:', error);
    }
  }

  runCode() {
    const q = this.questions[this.currentIndex];
    if (!q || q.type !== 'coding') return;

    const code = this.editor?.getValue() || '';
    const runBtn = document.getElementById('runCode');
    const outputContent = document.querySelector('.output-content');
    
    if (!runBtn || !outputContent) {
      console.error('Required elements not found');
      return;
    }

    // Disable button and show loading state
    runBtn.disabled = true;
    runBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running...';
    outputContent.textContent = 'Running code...';
    outputContent.classList.remove('error');
    
    // Prepare the request payload
    const payload = {
      language: q.language?.toLowerCase() || 'javascript',
      code: code,
      hidden_input: q.hidden_input || null,
      expected_output: q.expected_output || null,
      args: []
    };

    fetch(`${API_BASE}/code/execute`, {
      method: 'POST',
      credentials: 'include',
      headers: { 
        'Content-Type': 'application/json',
        ...authHeaders()
      },
      body: JSON.stringify(payload)
    })
    .then(async res => {
      const responseText = await res.text();
      console.log('Raw response:', responseText);
      if (!res.ok) {
        throw new Error(`HTTP error! status: ${res.status}`);
      }
      return JSON.parse(responseText);
    })
    .then(data => {
      console.log('Parsed data:', data);
      
      // Check for error status in the response
      if (data.status === 'error' || (data.data && data.data.status === 'error')) {
        throw new Error(data.data?.message || data.message || 'Unknown error occurred');
      }

      // Handle nested data structure
      const result = data.data?.data;
      console.log('Result:', result);
      
      if (!result) {
        throw new Error('No execution result received');
      }

      let output = '';

      // Handle compilation errors
      if (result.compile_error) {
        output = `Compilation Error:\n${result.compile_error}`;
        outputContent.classList.add('error');
      }
      // Handle runtime errors
      else if (result.error) {
        output = `Runtime Error:\n${result.error}`;
        outputContent.classList.add('error');
      }
      // Handle successful execution
      else {
        // Always show the output
        const actualOutput = result.output?.trim();
        output = actualOutput ? `Output:\n${actualOutput}` : 'No output produced';
        
        // If there's an expected output, show the comparison result
        if (result.expected_output !== undefined && result.expected_output !== null) {
          output += '\n\nResult: ' + (result.is_correct ? '✅ Correct' : '❌ Incorrect');
          if (!result.is_correct) {
            output += '\nExpected: ' + JSON.stringify(result.expected_output);
          }
        }
      }

      console.log('Final output:', output);
      outputContent.textContent = output;
      
      // Update answers and button states after code execution
      this.answers[q.id] = code;
      this.saveProgress();
      this.updateButtonStates();
    })
    .catch(error => {
      console.error('Code execution error:', error);
      outputContent.textContent = `Error: ${error.message}`;
      outputContent.classList.add('error');
    })
    .finally(() => {
      // Reset button state
      runBtn.disabled = false;
      runBtn.innerHTML = '<i class="fas fa-play"></i> Run Code';
    });
  }

  display() {
    const q = this.questions[this.currentIndex];
    if (!q) return;

    // Update question number display
    const questionNumberEl = document.getElementById('questionCounter');
    if (questionNumberEl) {
      questionNumberEl.textContent = `Question ${this.currentIndex + 1} of ${this.questions.length}`;
    }

    // Update question text
    const questionTextEl = document.getElementById('questionContent');
    if (questionTextEl) {
      questionTextEl.innerHTML = `<h4>${q.question_text}</h4>`;
    }

    // Clear previous answer options
    const optionsContainer = document.getElementById('multipleChoiceOptions');
    const editorContainer = document.getElementById('codingEditor');
    
    if (optionsContainer) {
      optionsContainer.innerHTML = '';
    }

    if (q.type === 'multiple_choice') {
      if (optionsContainer) {
        optionsContainer.style.display = 'block';
        if (editorContainer) {
          editorContainer.style.display = 'none';
        }
        
        // Create radio buttons for each option
        q.answers.forEach((answer, index) => {
          const div = document.createElement('div');
          div.className = 'form-check mb-2';
          
          const input = document.createElement('input');
          input.type = 'radio';
          input.name = 'answer';
          input.value = answer.id;
          input.id = `opt${index}`;
          input.className = 'form-check-input';
          
          // Check if this option was previously selected
          if (this.answers[q.id] === answer.id) {
            input.checked = true;
          }
          
          const label = document.createElement('label');
          label.className = 'form-check-label';
          label.htmlFor = `opt${index}`;
          label.textContent = answer.answer_text;
          
          div.appendChild(input);
          div.appendChild(label);
          optionsContainer.appendChild(div);
        });
      }
    } else if (q.type === 'coding') {
      if (optionsContainer) {
        optionsContainer.style.display = 'none';
      }
      if (editorContainer) {
        editorContainer.style.display = 'block';
        
        // Update language badge
        const languageBadge = document.getElementById('editorLanguage');
        if (languageBadge) {
          languageBadge.textContent = q.language || 'javascript';
        }

        // Update editor content and language
        if (this.editor) {
          const currentAnswer = this.answers[q.id];
          this.editor.setValue(currentAnswer || q.starter_code || '');
          
          // Map language to Monaco language ID
          const languageMap = {
            'python': 'python',
            'javascript': 'javascript',
            'typescript': 'typescript',
            'java': 'java',
            'c': 'c',
            'cpp': 'cpp',
            'php': 'php',
            'ruby': 'ruby',
            'go': 'go',
            'rust': 'rust',
            'cs': 'csharp'
          };
          
          const monacoLanguage = languageMap[q.language?.toLowerCase()] || 'javascript';
          
          // Update editor language and configuration
          monaco.editor.setModelLanguage(this.editor.getModel(), monacoLanguage);
          this.editor.updateOptions({
            'bracketPairColorization.enabled': true,
            'semanticHighlighting.enabled': true,
            'editor.semanticHighlighting.enabled': true
          });
          
          // Force a refresh of the editor
          this.editor.layout();
        }
      }
    }

    // Update navigation buttons
    const prevBtn = document.getElementById('prevQuestion');
    if (prevBtn) {
      prevBtn.disabled = this.currentIndex === 0;
    }
    
    // Update next and submit button states
    this.updateButtonStates();
  }

  startTimer() {
    this.timerInterval = setInterval(() => {
      if (--this.timeLeft < 0) { clearInterval(this.timerInterval); this.submitQuiz(); return; }
      const m = String(Math.floor(this.timeLeft/60)).padStart(2,'0');
      const s = String(this.timeLeft%60).padStart(2,'0');
      document.getElementById('timer').textContent = `${m}:${s}`;
    }, 1000);
  }

  saveAnswer() {
    const q = this.questions[this.currentIndex];
    if (!q) return;

    if (q.type === 'multiple_choice') {
      const selected = document.querySelector('input[name="answer"]:checked');
      if (selected) {
        // Use question ID as key instead of index
        this.answers[q.id] = parseInt(selected.value);
        // Save progress immediately when an answer is selected
        this.saveProgress();
      }
    }
  }

  isQuestionAnswered() {
    const q = this.questions[this.currentIndex];
    if (!q) return false;

    if (q.type === 'multiple_choice') {
      return document.querySelector('input[name="answer"]:checked') !== null;
    } else if (q.type === 'coding') {
      // Consider the question answered if the user has run the code
      const outputContent = document.querySelector('.output-content');
      return outputContent && outputContent.textContent.trim() !== '';
    }
    return false;
  }

  changeQuestion(delta) {
    if (this.isSubmitting) return;
    
    // Don't allow proceeding to next question if current question isn't answered
    if (delta > 0 && !this.isQuestionAnswered()) {
      alert('Please answer the current question before proceeding.');
      return;
    }
    
    this.saveAnswer();
    this.currentIndex += delta;
    this.updateUrl();
    
    // Clear the output when changing questions
    const outputContent = document.querySelector('.output-content');
    if (outputContent) {
      outputContent.textContent = '';
      outputContent.classList.remove('error');
    }
    
    this.display();
    this.saveProgress();
  }

  async submitQuiz() {
    if(this.isSubmitting) return; 
    this.isSubmitting=true; 
    this.saveAnswer();
    
    // Run code for coding questions to get outputs
    const answers = await Promise.all(this.questions.map(async (q, i) => {
      if (q.type !== 'coding') {
        return {
          question_id: q.id,
          answer_id: this.answers[q.id], // Use question ID to get answer
          code: null,
          output: null
        };
      }

      const code = this.answers[q.id]; // Use question ID to get code
      if (!code) {
        return {
          question_id: q.id,
          answer_id: null,
          code: null,
          output: null
        };
      }

      try {
        // First, save the code execution result
        const response = await fetch(`${API_BASE}/code/execute`, {
          method: 'POST',
          credentials: 'include',
          headers: { 
            'Content-Type': 'application/json',
            ...authHeaders()
          },
          body: JSON.stringify({ 
            language: q.language.toLowerCase() || 'python',
            code: code,
            hidden_input: q.hidden_input || null,
            expected_output: q.expected_output || null,
            args: []
          })
        });

        if (!response.ok) {
          throw new Error('Code execution failed');
        }

        const result = await response.json();
        const executionResult = result.data?.data || {};
        
        // Format the output for display
        let formattedOutput = '';
        if (executionResult.compile_error) {
          formattedOutput = `Compilation Error:\n${executionResult.compile_error}`;
        } else if (executionResult.error) {
          formattedOutput = `Runtime Error:\n${executionResult.error}`;
        } else {
          formattedOutput = executionResult.output || '';
        }

        return {
          question_id: q.id,
          answer_id: null,
          code: code,
          output: formattedOutput,
          is_correct: executionResult.output === q.expected_output,
          execution_result: executionResult
        };
      } catch (e) {
        console.error('Code execution error:', e);
        return {
          question_id: q.id,
          answer_id: null,
          code: code,
          output: `Error: ${e.message}`,
          is_correct: false,
          execution_result: { error: e.message }
        };
      }
    }));

    const payload = {
      answers,
      score: this.calculateScore(),
      time_taken: this.calculateTimeTaken()
    };

    try {
      const res = await fetch(`${API_BASE}/quizzes/${this.quizId}/attempts`, {
        method: 'POST',
        credentials: 'include',
        headers: { 
          'Content-Type': 'application/json', 
          ...authHeaders() 
        },
        body: JSON.stringify(payload)
      });

      if(!res.ok) {
        const err = await res.json(); 
        if(res.status === 429 && err.attempt_id) {
          window.location.href = `${CLIENT_BASE}/results/${this.quizId}?attempt_id=${err.attempt_id}`;
          return;
        } 
        throw new Error(err.message || 'Submit failed');
      } 

      const json = await res.json();
      const result = json.data || json;
      
      // Clear progress after successful submission
      await fetch(`${API_BASE}/quizzes/${this.quizId}/progress`, {
        method: 'DELETE',
        credentials: 'include',
        headers: authHeaders()
      });
      
      // Redirect to results page
      window.location.href = `${CLIENT_BASE}/results/${this.quizId}?attempt_id=${result.attempt_id}`;
    } catch (error) {
      console.error('Submission error:', error);
      alert(error.message);
      this.isSubmitting = false;
    }
  }

  calculateScore() {
    const correct = this.questions.reduce((acc, q, i) => {
      if (q.type === 'multiple_choice') {
        const selectedAnswerId = this.answers[i];
        if (!selectedAnswerId) return acc;
        return acc + (selectedAnswerId === q.correct_answer_id ? 1 : 0);
      } else if (q.type === 'coding') {
        // For coding questions, check if the output matches expected output
        const answer = this.answers[i];
        if (!answer) return acc;
        return acc + (answer.is_correct ? 1 : 0);
      }
      return acc;
    }, 0);
    
    // Calculate percentage with proper rounding
    const rawScore = (correct / this.questions.length) * 100;
    return Math.round(rawScore); // Round to nearest integer
  }

  calculateTimeTaken(){return Math.floor((Date.now()-this.startTime)/1000);}  

  updateUrl(){const url=new URL(window.location);url.searchParams.set('question',this.currentIndex);window.history.replaceState({},'',url);}  
}

// Initialize quiz when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  window.quizInstance = new Quiz();
});