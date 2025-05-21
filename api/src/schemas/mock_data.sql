-- Insert mock users
INSERT INTO users (username, email, password, avatar, role, is_active, last_login) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', 'user', 1, NOW()),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', 'user', 1, NOW());

-- Insert some default achievements
INSERT INTO achievements (title, description, icon, unlock_condition) VALUES
('First Steps', 'Complete your first quiz', 'fa-shoe-prints', 'Complete 1 quiz'),
('Quick Learner', 'Score 100% on any quiz', 'fa-bolt', 'Get perfect score'),
('Dedication', 'Complete 10 quizzes', 'fa-award', 'Complete 10 quizzes');

-- Insert mock quizzes
INSERT INTO quizzes (title, description, category, level, is_active, time_limit) VALUES
('Python Basics', 'Test your knowledge of Python fundamentals', 'Python', 'beginner', 1, 30),
('JavaScript Arrays', 'Master array manipulation in JavaScript', 'JavaScript', 'intermediate', 1, 45),
('Easy Python Coding', 'Simple Python coding exercises for beginners', 'Python', 'beginner', 1, 30);

-- Insert mock questions for Python Basics quiz
INSERT INTO questions (quiz_id, question_text, type, language, starter_code, hidden_input, expected_output) VALUES
(1, 'Write a function to add two numbers', 'coding', 'Python',
'def add_numbers(a, b):
    # Your code here
    pass',
'[[1, 2], [5, 7], [10, 20]]',
'[3, 12, 30]'),
(1, 'Write a function to check if a number is even', 'coding', 'Python',
'def is_even(number):
    # Your code here
    pass',
'[2, 3, 4, 5, 6]',
'[True, False, True, False, True]'),
(1, 'Write a function to print "Hello" followed by a name', 'coding', 'Python',
'def greet(name):
    # Your code here
    pass',
'["Alice", "Bob", "Charlie"]',
'["Hello Alice", "Hello Bob", "Hello Charlie"]');

-- Insert mock questions for JavaScript Arrays quiz
INSERT INTO questions (quiz_id, question_text, type, language, starter_code, hidden_input, expected_output) VALUES
(2, 'Write a function to get the length of an array', 'coding', 'JavaScript',
'function getArrayLength(arr) {
    // Your code here
}',
'[[1, 2, 3], [4, 5], [6, 7, 8, 9]]',
'[3, 2, 4]'),
(2, 'Write a function to join array elements with a separator', 'coding', 'JavaScript',
'function joinArray(arr, separator) {
    // Your code here
}',
'[[[1, 2, 3], "-"], [["a", "b", "c"], ","], [["x", "y", "z"], ""]]',
'["1-2-3", "a,b,c", "xyz"]'),
(2, 'Write a function to check if an array includes a value', 'coding', 'JavaScript',
'function arrayIncludes(arr, value) {
    // Your code here
}',
'[[[1, 2, 3], 2], [[4, 5, 6], 7], [["a", "b", "c"], "b"]]',
'[true, false, true]');

-- Insert mock questions for Easy Python Coding quiz
INSERT INTO questions (quiz_id, question_text, type, language, starter_code, hidden_input, expected_output) VALUES
(3, 'Write a function to multiply two numbers', 'coding', 'Python',
'def multiply(a, b):
    # Your code here
    pass',
'[[2, 3], [5, 4], [10, 10]]',
'[6, 20, 100]'),
(3, 'Write a function to check if a number is positive', 'coding', 'Python',
'def is_positive(number):
    # Your code here
    pass',
'[5, -3, 0, 10, -1]',
'[True, False, False, True, False]'),
(3, 'Write a function to concatenate two strings', 'coding', 'Python',
'def concatenate(str1, str2):
    # Your code here
    pass',
'[["Hello", "World"], ["Python", "Programming"], ["Code", "Challenge"]]',
'["HelloWorld", "PythonProgramming", "CodeChallenge"]');

-- Insert mock quiz attempts
INSERT INTO quiz_attempts (user_id, quiz_id, score, accuracy, time_taken, total_questions, started_at, completed_at) VALUES
(1, 1, 90.0, 0.9, 1200, 3, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 2, 85.0, 0.85, 1800, 3, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 1, 100.0, 1.0, 900, 3, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Insert mock question responses
INSERT INTO question_responses (attempt_id, question_id, answer_id, submitted_code, output, is_correct) VALUES
-- John's Python Basics responses
(1, 1, NULL, 'print(2 + 2)', '4', 1),
(1, 2, NULL, 'print("Hello" + "World")', 'HelloWorld', 1),
(1, 3, NULL, 'print(len("Python"))', '6', 1),

-- John's JavaScript Arrays responses
(2, 4, NULL, 'console.log([1, 2, 3].length)', '3', 1),
(2, 5, NULL, 'console.log([1, 2, 3].join("-"))', '1-2-3', 1),
(2, 6, NULL, 'console.log([1, 2, 3].includes(2))', 'true', 1),

-- Jane's Python Basics responses
(3, 1, NULL, 'print(2 + 2)', '4', 1),
(3, 2, NULL, 'print("Hello" + "World")', 'HelloWorld', 1),
(3, 3, NULL, 'print(len("Python"))', '6', 1);

-- Insert mock user activities
INSERT INTO user_activities (user_id, type, title, quiz_id, quiz_attempt_id) VALUES
(1, 'quiz_completed', 'Completed Python Basics', 1, 1),
(1, 'quiz_completed', 'Completed JavaScript Arrays', 2, 2),
(2, 'quiz_completed', 'Completed Python Basics', 1, 3);

-- Insert mock user achievements
INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY)), -- First Steps
(1, 2, DATE_SUB(NOW(), INTERVAL 1 DAY)), -- Quick Learner
(2, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)), -- First Steps
(2, 2, DATE_SUB(NOW(), INTERVAL 3 DAY)); -- Quick Learner
