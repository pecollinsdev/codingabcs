<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\QuestionModel;
use App\Models\AnswerModel;

/**
 * Controller for managing quiz questions and their answers
 * 
 * Handles CRUD operations for questions of various types
 */
class QuestionController extends Controller
{
    /**
     * @var QuestionModel Question model instance
     */
    private QuestionModel $questionModel;
    
    /**
     * @var AnswerModel Answer model instance
     */
    private AnswerModel $answerModel;

    /**
     * Constructor for the QuestionController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->questionModel = new QuestionModel();
        $this->answerModel = new AnswerModel();
    }

    /**
     * Get all questions for a specific quiz
     * 
     * Endpoint: GET /quizzes/{quiz_id}/questions
     * Access: Authentication required
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with questions
     */
    public function index(Request $request): void
    {
        if (!$request->hasUser()) {
            $this->respondUnauthorized('Authentication required');
            return;
        }

        $quizId = (int)$request->getParam('quiz_id');
        if (!$quizId) {
            $this->respondError('Quiz ID is required', 400);
            return;
        }

        $questions = $this->questionModel->getByQuizId($quizId);
        
        foreach ($questions as &$q) {
            if ($q['type'] === 'multiple_choice') {
                $answers = $this->answerModel->getByQuestionId($q['id']);
                $q['answers'] = $answers;
                
                // Find the correct answer ID
                foreach ($answers as $answer) {
                    if ($answer['is_correct']) {
                        $q['correct_answer_id'] = $answer['id'];
                        break;
                    }
                }
            }
        }
        
        $this->respond(['questions' => $questions]);
    }

    /**
     * Get a specific question by ID
     * 
     * Endpoint: GET /questions/{id}
     * Returns: Question details including answers for multiple choice questions
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with question data
     */
    public function show(Request $request): void
    {
        $id = (int)$request->getParam('id');
        $question = $this->questionModel->getById($id);

        if (!$question) {
            $this->respondNotFound('Question not found');
            return;
        }

        if ($question['type'] === 'multiple_choice') {
            $question['answers'] = $this->answerModel->getByQuestionId($id);
        }

        $this->respond($question);
    }

    /**
     * Create a new question for a quiz
     * 
     * Endpoint: POST /quizzes/{quiz_id}/questions
     * Access: Admin only
     * Required fields:
     *   - question_text: The text of the question
     *   - type: Question type ('multiple_choice' or 'coding')
     * Additional fields for coding questions:
     *   - starter_code: Initial code provided to the user
     *   - language: Programming language
     *   - expected_output: Expected output for verification
     * Additional fields for multiple choice:
     *   - answers: Array of answer options
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with creation status
     */
    public function store(Request $request): void
    {
        if (!$this->user()) {
            $this->respondUnauthorized();
            return;
        }
        if ($this->user()['role'] !== 'admin') {
            $this->respondForbidden();
            return;
        }

        $quizId = (int)$request->getParam('quiz_id');
        $data = $request->getData();

        // Validate base fields
        if (empty($data['question_text']) || empty($data['type'])) {
            $this->respondValidationError(['question_text' => 'Required', 'type' => 'Required']);
            return;
        }

        // Validate based on type
        if ($data['type'] === 'coding') {
            foreach (['starter_code', 'language', 'expected_output'] as $field) {
                if (empty($data[$field])) {
                    $this->respondValidationError(["$field" => 'Required for coding questions']);
                    return;
                }
            }
        }

        // Create the question
        $questionId = $this->questionModel->create(array_merge($data, ['quiz_id' => $quizId]));
        if (!$questionId) {
            $this->respondError($this->questionModel->getLastError());
            return;
        }

        // Handle answers if it's an MCQ
        if ($data['type'] === 'multiple_choice' && !empty($data['answers'])) {
            $errors = $this->answerModel->createBatch($questionId, $data['answers']);
            if (!empty($errors)) {
                $this->questionModel->delete($questionId); // Rollback
                $this->respondValidationError(['answers' => $errors]);
                return;
            }
        }

        $this->respondCreated(['message' => 'Question created', 'question_id' => $questionId]);
    }

    /**
     * Update an existing question
     * 
     * Endpoint: PATCH /questions/{id}
     * Access: Admin only
     * Required fields:
     *   - id: Question ID
     * Optional fields:
     *   - question_text: Updated question text
     *   - type: Question type
     *   - answers: Array of updated answers (for multiple choice)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with update status
     */
    public function update(Request $request): void
    {
        if (!$this->user()) {
            $this->respondUnauthorized();
            return;
        }
        if ($this->user()['role'] !== 'admin') {
            $this->respondForbidden();
            return;
        }

        $data = $request->getData();
        if (!isset($data['id'])) {
            $this->respondError('Question ID is required', 400);
            return;
        }

        $id = (int)$data['id'];
        $question = $this->questionModel->getById($id);
        if (!$question) {
            $this->respondNotFound('Question not found');
            return;
        }

        // Add quiz_id to the update data
        $data['quiz_id'] = $question['quiz_id'];

        $updated = $this->questionModel->update($id, $data);
        if (!$updated) {
            $this->respondError($this->questionModel->getLastError());
            return;
        }

        // Only update answers if it's a multiple choice question
        if (
            $question['type'] === 'multiple_choice' &&
            !empty($data['answers']) &&
            is_array($data['answers'])
        ) {
            $this->answerModel->deleteByQuestionId($id);
            $this->answerModel->createBatch($id, $data['answers']);
        }

        $this->respond(['message' => 'Question updated']);
    }

    /**
     * Delete a question and its associated answers
     * 
     * Endpoint: DELETE /questions/{id}
     * Access: Admin only
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response with deletion status
     */
    public function destroy(Request $request): void
    {
        if (!$this->user()) {
            $this->respondUnauthorized();
            return;
        }
        if ($this->user()['role'] !== 'admin') {
            $this->respondForbidden();
            return;
        }

        // Get the question ID from the route parameters
        $id = (int)$request->getParam('question_id');
        if ($id <= 0) {
            $this->respondError('Invalid question ID');
            return;
        }

        // First delete all associated answers
        $answerResult = $this->answerModel->deleteByQuestionId($id);

        // Then delete the question
        $success = $this->questionModel->delete($id);
        
        if (!$success) {
            $this->respondError('Failed to delete question');
            return;
        }

        $this->respond(['message' => 'Question deleted']);
    }
}
