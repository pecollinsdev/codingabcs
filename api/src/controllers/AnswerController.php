<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\AnswerModel;

/**
 * Controller for managing answers to quiz questions
 * 
 * Handles CRUD operations for answer entities
 */
class AnswerController extends Controller
{
    /**
     * @var AnswerModel Answer model instance
     */
    private AnswerModel $answerModel;

    /**
     * Constructor for the AnswerController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->answerModel = new AnswerModel();
    }

    /**
     * View a single answer
     * 
     * Endpoint: GET /answers/{id}
     * Access: Public
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function show(Request $request): void
    {
        $id = (int) $request->getParam('id');

        $answer = $this->answerModel->getById($id);
        if (!$answer) {
            $this->respondNotFound("Answer not found.");
            return;
        }

        $this->respond($answer);
    }

    /**
     * Update a single answer
     * 
     * Endpoint: PATCH /answers/{id}
     * Access: Admin only
     * Required fields:
     *   - answer_text: The text of the answer
     * Optional fields:
     *   - is_correct: Whether this answer is correct (default: false)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function update(Request $request): void
    {
        if (!$this->user()) { $this->respondUnauthorized(); return; }
        if ($this->user()['role'] !== 'admin') { $this->respondForbidden(); return; }

        $id = (int) $request->getParam('id');
        $data = $request->getData();

        if (empty($data['answer_text'])) {
            $this->respondValidationError(['answer_text' => 'Answer text is required']);
            return;
        }

        $isCorrect = isset($data['is_correct']) ? (bool)$data['is_correct'] : false;

        $updated = $this->answerModel->update($id, $data['answer_text'], $isCorrect);
        if (!$updated) {
            $this->respondError($this->answerModel->getLastError());
            return;
        }

        $this->respond(['message' => 'Answer updated']);
    }

    /**
     * Delete an answer
     * 
     * Endpoint: DELETE /answers/{id}
     * Access: Admin only
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function destroy(Request $request): void
    {
        if (!$this->user()) { $this->respondUnauthorized(); return; }
        if ($this->user()['role'] !== 'admin') { $this->respondForbidden(); return; }

        $id = (int) $request->getParam('id');

        $deleted = $this->answerModel->delete($id);
        if (!$deleted) {
            $this->respondError('Failed to delete answer.');
            return;
        }

        $this->respond(['message' => 'Answer deleted']);
    }

    /**
     * Create an answer for a question
     * 
     * Endpoint: POST /questions/{question_id}/answers
     * Access: Admin only
     * Required fields:
     *   - answer_text: The text of the answer
     * Optional fields:
     *   - is_correct: Whether this answer is correct (default: false)
     *
     * @param Request $request The HTTP request object
     * @return void Sends JSON response
     */
    public function store(Request $request): void
    {
        if (!$this->user()) { $this->respondUnauthorized(); return; }
        if ($this->user()['role'] !== 'admin') { $this->respondForbidden(); return; }

        $questionId = (int) $request->getParam('question_id');
        $data = $request->getData();

        if (empty($data['answer_text'])) {
            $this->respondValidationError(['answer_text' => 'Answer text is required']);
            return;
        }

        $isCorrect = isset($data['is_correct']) ? (bool)$data['is_correct'] : false;

        $id = $this->answerModel->create($questionId, $data['answer_text'], $isCorrect);
        if (!$id) {
            $this->respondError($this->answerModel->getLastError());
            return;
        }

        $this->respondCreated(['message' => 'Answer created', 'id' => $id]);
    }
}
