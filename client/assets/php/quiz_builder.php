<?php
/**
 * Quiz Builder Utility
 * 
 * A utility class for generating consistent quiz components across the application.
 * Usage:
 *   $quizBuilder = new QuizBuilder();
 *   echo $quizBuilder->build([
 *       'title'           => 'Quiz Title',
 *       'quiz_id'         => 1,
 *       'questions'       => $questionsArray,
 *       'timeLimit'       => 300,
 *       'showTimer'       => true,
 *       'showProgress'    => true,
 *       'classes'         => 'quiz-fullscreen',
 *       'showExitButton'  => true,
 *       'currentQuestion' => 2
 *   ]);
 */

class QuizBuilder
{
    private $quizDefaults = [
        'title'           => '',
        'quiz_id'         => 0,
        'questions'       => [],
        'timeLimit'       => 0,
        'showTimer'       => true,
        'showProgress'    => true,
        'classes'         => '',
        'formId'          => 'quizForm',
        'formClasses'     => 'question-form',
        'submitText'      => 'Next',
        'submitClasses'   => 'btn-primary',
        'showExitButton'  => true,
        'currentQuestion' => 0
    ];

    public function build(array $options = []): string
    {
        $options = array_merge($this->quizDefaults, $options);

        $html  = '<div class="quiz-container ' . htmlspecialchars($options['classes']) . '">';
        $html .= $this->buildHeader($options);
        $html .= $this->buildForm($options);
        $html .= $this->buildNavigation();
        $html .= $this->buildExitModal();
        $html .= '</div>';

        return $html;
    }

    private function buildHeader(array $o): string
    {
        $h  = '<div class="quiz-header">';
        $h .=   '<div class="quiz-header-content">';
        $h .=     '<div class="question-counter" id="questionCounter">Question 1/' . count($o['questions']) . '</div>';
        $h .=     '<h2 id="quizTitle" class="quiz-title">' . htmlspecialchars($o['title']) . '</h2>';
        if ($o['showTimer']) {
            $h .=   '<div id="timer" class="timer">00:00</div>';
        }
        $h .=   '</div>';
        if ($o['showProgress']) {
            $h .=   '<div class="progress">'
                 .     '<div class="progress-bar" role="progressbar" style="width: 0%" '
                 .         'aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>'
                 .   '</div>';
        }
        $h .= '</div>';

        return $h;
    }

    private function buildForm(array $o): string
    {
        $f  = '<form id="' . htmlspecialchars($o['formId']) . '" class="' . htmlspecialchars($o['formClasses']) . '" novalidate>';
        // Hidden inputs
        $f .= '<input type="hidden" id="questionId" name="question_id">';
        $f .= '<input type="hidden" id="questionType" name="question_type">';
        $f .= '<input type="hidden" id="languageSelect" name="language" value="">';

        // Containers for dynamic content
        $f .= '<div class="question-content">'
            .   '<h3 class="question-text" id="questionContent"></h3>'
            . '</div>';
        $f .= '<div id="multipleChoiceOptions" class="options-container" style="display: none;"></div>';
        
        // Updated editor container structure
        $f .= '<div id="codingEditor" class="editor-container" style="display: none;">'
            .   '<div class="editor-main">'
            .     '<div class="editor-section">'
            .       '<div class="editor-header">'
            .         '<span id="editorLanguage" class="language-badge">JavaScript</span>'
            .       '</div>'
            .       '<div id="editor"></div>'
            .     '</div>'
            .     '<div class="right-section">'
            .       '<div class="output-section">'
            .         '<div class="output-header">'
            .           '<h4>Output</h4>'
            .         '</div>'
            .         '<div class="output-container">'
            .           '<pre class="output-content"></pre>'
            .         '</div>'
            .       '</div>'
            .     '</div>'
            .   '</div>'
            .   '<div class="editor-footer">'
            .     '<button type="button" id="runCode" class="btn btn-primary">'
            .       '<i class="fas fa-play"></i> Run Code'
            .     '</button>'
            .   '</div>'
            . '</div>';

        $f .= '</form>';

        // Inject initial quizData for JS hydration
        if (!empty($o['questions'])) {
            $sessionKey   = 'quiz_progress_' . (int)$o['quiz_id'];
            $sessionData  = $_SESSION[$sessionKey] ?? [];
            $savedAnswers = $sessionData['answers'] ?? [];
            
            // Normalize answers into object
            $answersObj   = new \stdClass();
            if (is_array($savedAnswers)) {
                foreach ($savedAnswers as $idx => $ans) {
                    if ($ans !== null && $ans !== '') {
                        $answersObj->$idx = $ans;
                    }
                }
            }
            
            $current = $sessionData['current_question'] ?? $o['currentQuestion'] ?? 0;

            $data = json_encode([
                'quiz_id'         => $o['quiz_id'],
                'questions'       => $o['questions'],
                'currentQuestion' => $current,
                'answers'         => $answersObj,
                'timeLimit'       => $o['timeLimit']
            ], JSON_UNESCAPED_SLASHES);

            $f .= "<script>
                window.quizData = {$data};
                console.log('Initial quiz data:', window.quizData);
            </script>";
        }

        return $f;
    }

    private function buildNavigation(): string
    {
        return '<div class="navigation-buttons">'
             .   '<div class="nav-left">'
             .     '<button type="button" id="exitQuizBtn" class="btn btn-exit"><i class="fas fa-sign-out-alt"></i> Exit Quiz</button>'
             .     '<button type="button" id="prevQuestion" class="btn btn-secondary" disabled>Previous</button>'
             .   '</div>'
             .   '<div class="nav-right">'
             .     '<button type="button" id="nextQuestion" class="btn btn-primary">Next</button>'
             .     '<button type="button" id="submitQuiz" class="btn btn-success" style="display: none;">Submit Quiz</button>'
             .   '</div>'
             . '</div>';
    }

    private function buildExitModal(): string
    {
        return '
        <div class="modal fade" id="exitQuizModal" tabindex="-1" aria-labelledby="exitQuizModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exitQuizModalLabel">Exit Quiz</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to exit the quiz? Your progress will be saved.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger">Exit Quiz</button>
                    </div>
                </div>
            </div>
        </div>';
    }

    public function addMonacoScripts(): string
    {
        return <<<'EOT'
<script>
if (!window.monacoLoaded) {
  const s = document.createElement('script');
  s.src = 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.47.0/min/vs/loader.js';
  s.onload = () => {
    require.config({ 
      paths: { 
        'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.47.0/min/vs'
      }
    });
    require(['vs/editor/editor.main'], () => {
      window.monacoLoaded = true;
      const event = new Event('monacoLoaded');
      window.dispatchEvent(event);
    });
  };
  document.head.appendChild(s);
}
</script>
EOT;
    }
}
