-- Create the database (if it doesn't exist)
CREATE DATABASE IF NOT EXISTS logger_db;

-- Use the newly created database
USE logger_db;

-- Create the logs table to store log entries
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20) NOT NULL,         -- Log level (e.g., ERROR, INFO, DEBUG)
    message TEXT NOT NULL,              -- Log message
    context JSON DEFAULT NULL,          -- Stores additional structured data (optional)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Timestamp of the log entry
);

-- Index for faster retrieval of logs by level and date
CREATE INDEX idx_logs_level ON logs(level);
CREATE INDEX idx_logs_created_at ON logs(created_at);

-- 1. Insert quiz
INSERT INTO quizzes (title, description, category, level, is_active, created_at)
VALUES (
  'Basic PHP Quiz',
  'A beginner-friendly PHP quiz to test your understanding of basic syntax and concepts.',
  'Programming',
  'beginner',
  1,
  NOW()
);

-- 2. Get the last inserted quiz ID
SET @quiz_id = LAST_INSERT_ID();

-- 3. Insert questions
INSERT INTO questions (quiz_id, question_text, type, created_at)
VALUES
(@quiz_id, 'Which symbol is used to declare a variable in PHP?', 'multiple_choice', NOW()),
(@quiz_id, 'What function is used to output text in PHP?', 'multiple_choice', NOW());

-- 4. Get the question IDs
-- If your DBMS doesn't support multi-selects, run this step manually to fetch the IDs:
-- SELECT id FROM questions WHERE quiz_id = @quiz_id ORDER BY id ASC;

-- Assuming the questions were added sequentially, use variables:
SET @question1 = (SELECT id FROM questions WHERE quiz_id = @quiz_id ORDER BY id ASC LIMIT 1);
SET @question2 = (SELECT id FROM questions WHERE quiz_id = @quiz_id ORDER BY id ASC LIMIT 1 OFFSET 1);

-- 5. Insert answers for Question 1
INSERT INTO answers (question_id, answer_text, is_correct, created_at)
VALUES
(@question1, '&', 0, NOW()),
(@question1, '%', 0, NOW()),
(@question1, '$', 1, NOW()),
(@question1, '#', 0, NOW());

-- 6. Insert answers for Question 2
INSERT INTO answers (question_id, answer_text, is_correct, created_at)
VALUES
(@question2, 'echo', 1, NOW()),
(@question2, 'print_text', 0, NOW()),
(@question2, 'write()', 0, NOW()),
(@question2, 'output()', 0, NOW());
