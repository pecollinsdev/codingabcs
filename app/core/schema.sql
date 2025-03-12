CREATE DATABASE codingabcs_db;
USE codingabcs_db;

-- Users Table (Stores user accounts)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Quizzes Table (Stores quizzes)
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Questions Table (Stores questions for each quiz)
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Answers Table (Stores possible answers for each question)
CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- User Attempts Table (Tracks each quiz attempt)
CREATE TABLE user_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    attempt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- User Answers Table (Tracks each selected answer per question per attempt)
CREATE TABLE user_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer_id INT NULL,
    is_correct BOOLEAN NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (attempt_id) REFERENCES user_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_answer_id) REFERENCES answers(id) ON DELETE CASCADE
);

-- User Performance Table (Aggregates quiz performance per user)
CREATE TABLE user_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    wrong_answers INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Leaderboard Table (Stores high scores)
CREATE TABLE leaderboards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    high_score INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Quiz Progress Table (Tracks in-progress quizzes)
CREATE TABLE quiz_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    answers JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Insert a new JavaScript quiz
INSERT INTO quizzes (title, description, category) 
VALUES ('JavaScript Basics Quiz', 'Test your knowledge on JavaScript fundamentals!', 'JavaScript');

-- Get the last inserted quiz ID
SET @quiz_id = LAST_INSERT_ID();

-- Insert questions
INSERT INTO questions (quiz_id, question_text) 
VALUES 
(@quiz_id, 'What is the correct way to declare a variable in JavaScript?'),
(@quiz_id, 'Which method is used to print something to the console?'),
(@quiz_id, 'Which of the following is NOT a JavaScript data type?');

-- Get the inserted question IDs
SET @q1 = LAST_INSERT_ID();
SET @q2 = @q1 + 1;
SET @q3 = @q1 + 2;

-- Insert answers for Question 1
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@q1, 'var myVar;', 1), -- Correct
(@q1, 'let myVar;', 1), -- Correct
(@q1, 'const myVar;', 1), -- Correct
(@q1, 'variable myVar;', 0); -- Incorrect

-- Insert answers for Question 2
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@q2, 'console.log()', 1), -- Correct
(@q2, 'print()', 0), -- Incorrect
(@q2, 'log.console()', 0), -- Incorrect
(@q2, 'write.console()', 0); -- Incorrect

-- Insert answers for Question 3
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@q3, 'String', 0), -- Incorrect
(@q3, 'Number', 0), -- Incorrect
(@q3, 'Boolean', 0), -- Incorrect
(@q3, 'Character', 1); -- Correct

-- Insert PHP Quiz
INSERT INTO quizzes (title, description, category) 
VALUES ('PHP Basics Quiz', 'Test your knowledge on PHP fundamentals!', 'PHP');

SET @php_quiz_id = LAST_INSERT_ID();

-- Insert PHP Questions
INSERT INTO questions (quiz_id, question_text) 
VALUES 
(@php_quiz_id, 'Which symbol is used to declare a variable in PHP?'),
(@php_quiz_id, 'Which function is used to output text in PHP?'),
(@php_quiz_id, 'Which of the following is NOT a PHP data type?');

SET @php_q1 = LAST_INSERT_ID();
SET @php_q2 = @php_q1 + 1;
SET @php_q3 = @php_q1 + 2;

-- Insert PHP Answers
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@php_q1, '$', 1), -- Correct
(@php_q1, '#', 0),
(@php_q1, '@', 0),
(@php_q1, '&', 0),

(@php_q2, 'echo', 1), -- Correct
(@php_q2, 'print()', 1), -- Correct
(@php_q2, 'printf()', 1), -- Correct
(@php_q2, 'display()', 0),

(@php_q3, 'String', 0),
(@php_q3, 'Number', 0),
(@php_q3, 'Boolean', 0),
(@php_q3, 'Character', 1); -- Correct


-- Insert HTML Quiz
INSERT INTO quizzes (title, description, category) 
VALUES ('HTML Basics Quiz', 'Test your knowledge on HTML fundamentals!', 'HTML');

SET @html_quiz_id = LAST_INSERT_ID();

-- Insert HTML Questions
INSERT INTO questions (quiz_id, question_text) 
VALUES 
(@html_quiz_id, 'What does HTML stand for?'),
(@html_quiz_id, 'Which tag is used to create a hyperlink in HTML?'),
(@html_quiz_id, 'Which tag is used to define an unordered list?');

SET @html_q1 = LAST_INSERT_ID();
SET @html_q2 = @html_q1 + 1;
SET @html_q3 = @html_q1 + 2;

-- Insert HTML Answers
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@html_q1, 'Hyper Text Markup Language', 1), -- Correct
(@html_q1, 'Hyper Transfer Markup Language', 0),
(@html_q1, 'High Tech Modern Language', 0),
(@html_q1, 'Home Tool Markup Language', 0),

(@html_q2, '<a>', 1), -- Correct
(@html_q2, '<link>', 0),
(@html_q2, '<href>', 0),
(@html_q2, '<hyperlink>', 0),

(@html_q3, '<ul>', 1), -- Correct
(@html_q3, '<ol>', 0),
(@html_q3, '<list>', 0),
(@html_q3, '<li>', 0);


-- Insert CSS Quiz
INSERT INTO quizzes (title, description, category) 
VALUES ('CSS Basics Quiz', 'Test your knowledge on CSS fundamentals!', 'CSS');

SET @css_quiz_id = LAST_INSERT_ID();

-- Insert CSS Questions
INSERT INTO questions (quiz_id, question_text) 
VALUES 
(@css_quiz_id, 'What does CSS stand for?'),
(@css_quiz_id, 'Which property is used to change text color in CSS?'),
(@css_quiz_id, 'Which property is used to change the background color?');

SET @css_q1 = LAST_INSERT_ID();
SET @css_q2 = @css_q1 + 1;
SET @css_q3 = @css_q1 + 2;

-- Insert CSS Answers
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@css_q1, 'Cascading Style Sheets', 1), -- Correct
(@css_q1, 'Creative Style System', 0),
(@css_q1, 'Computer Style Sheets', 0),
(@css_q1, 'Colorful Style Sheets', 0),

(@css_q2, 'color', 1), -- Correct
(@css_q2, 'text-color', 0),
(@css_q2, 'font-color', 0),
(@css_q2, 'background-color', 0),

(@css_q3, 'background-color', 1), -- Correct
(@css_q3, 'color', 0),
(@css_q3, 'bgcolor', 0),
(@css_q3, 'background', 0);


-- Insert SQL Quiz
INSERT INTO quizzes (title, description, category) 
VALUES ('SQL Basics Quiz', 'Test your knowledge on SQL fundamentals!', 'SQL');

SET @sql_quiz_id = LAST_INSERT_ID();

-- Insert SQL Questions
INSERT INTO questions (quiz_id, question_text) 
VALUES 
(@sql_quiz_id, 'What does SQL stand for?'),
(@sql_quiz_id, 'Which SQL command is used to retrieve data?'),
(@sql_quiz_id, 'Which SQL clause is used to filter results?');

SET @sql_q1 = LAST_INSERT_ID();
SET @sql_q2 = @sql_q1 + 1;
SET @sql_q3 = @sql_q1 + 2;

-- Insert SQL Answers
INSERT INTO answers (question_id, answer_text, is_correct) 
VALUES 
(@sql_q1, 'Structured Query Language', 1), -- Correct
(@sql_q1, 'Simple Query Language', 0),
(@sql_q1, 'Standard Query Language', 0),
(@sql_q1, 'System Query Logic', 0),

(@sql_q2, 'SELECT', 1), -- Correct
(@sql_q2, 'GET', 0),
(@sql_q2, 'FETCH', 0),
(@sql_q2, 'RETRIEVE', 0),

(@sql_q3, 'WHERE', 1), -- Correct
(@sql_q3, 'FILTER', 0),
(@sql_q3, 'HAVING', 0),
(@sql_q3, 'ORDER BY', 0);


