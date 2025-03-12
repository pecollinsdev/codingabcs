<?php
namespace App\Models;

use App\Core\Database;

/**
 * Question model handles the quiz questions and answers.
 *
 * This model is responsible for fetching the questions and answers for a given quiz.
 * It interacts with the QuizModel to get the necessary data.
 */
class QuestionModel {
    // Database instance
    private $db;

    // Constructor
    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get all questions and answers for a given quiz
    public function getQuestionsWithAnswers($quizId) {
        $sql = "SELECT q.id AS question_id, q.question_text, 
                       a.id AS answer_id, a.answer_text, a.is_correct
                FROM questions q
                JOIN answers a ON q.id = a.question_id
                WHERE q.quiz_id = ?
                ORDER BY q.id ASC";
    
        $rows = $this->db->query($sql, [$quizId])->fetchAll();
    
        $questions = [];
        foreach ($rows as $row) {
            $qId = $row['question_id'];
    
            if (!isset($questions[$qId])) {
                $questions[$qId] = [
                    'question_id'   => $qId,
                    'question_text' => $row['question_text'],
                    'answers'       => []
                ];
            }
    
            $questions[$qId]['answers'][] = [
                'answer_id'   => $row['answer_id'],
                'answer_text' => $row['answer_text'],
                'is_correct'  => $row['is_correct'] // Ensure this is included
            ];
        }
    
        return $questions;
    }
    

    // Get correct answers for a given quiz
    public function getCorrectAnswers($quizId) {
        $sql = "SELECT question_id, id FROM answers WHERE question_id IN 
                (SELECT id FROM questions WHERE quiz_id = ?) AND is_correct = 1";
        $results = $this->db->query($sql, [$quizId])->fetchAll();
    
        $correctAnswers = [];
        foreach ($results as $row) {
            $correctAnswers[$row['question_id']][] = $row['id']; // Group multiple correct answers
        }
        return $correctAnswers;
    }    
}
