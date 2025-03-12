# Coding ABCs

## Description
Coding ABCs is an interactive coding quiz platform designed to test and enhance programming knowledge through engaging quizzes. It features user authentication, quiz selection, scoring, and performance tracking. 

## Installation
Follow these steps to set up Coding ABCs on your local development environment:

### 1. Install XAMPP
- Download and install [XAMPP](https://www.apachefriends.org/index.html).
- Start the Apache and MySQL modules from the XAMPP control panel.

### 2. Clone Repository
```bash
  git clone https://github.com/pecollinsdev/codingABCs.git
  cd coding-abcs
```

### 3. Install Dependencies
- Install Composer dependencies:
```bash
  composer init
```

- Edit composer.json
```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }
}
```

- Finish Composer install
```bash
  composer install
```

### 4. Database Configuration
- Create a `.php` file named database.php in the project app's config folder.
- Configure database connection settings in `.php`:
```php
<?php

// Database configuration
return [
    'host' => 'localhost',
    'dbname' => 'codingabcs_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8'
];
```
- Use the schema `.sql` file located in the project app's core folder to create the database.

### 5. URL Configuration
- Ensure the root folder is named "codingabcs".
- Create a `.php` file named config.php in the project app's config folder.
- Define base URL in `.php`
```php
<?php

// Automatically detect base URL
define('BASE_URL', 'http://localhost/codingabcs/public');
```
- Create a `.htaccess` file named .htaccess in the root directory of the project.
```htaccess
RewriteEngine On

# Redirect to public folder if not already inside public
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]
```
- Create a `.htaccess` file named .htaccess in the projects public folder.
```htaccess
RewriteEngine On
RewriteBase /codingabcs/public/

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

## Usage
### 1. Register/Login
- Create an account or log in using existing credentials.

### 2. Select a Quiz
- Choose from available coding quizzes based on difficulty and topic.

### 3. Answer Questions
- Answer multiple-choice or coding-based questions.

### 4. View Results
- Get instant feedback on quiz performance.

### 5. Check Leaderboard
- Compare scores with other users on the leaderboard.

## Debugging
### Logger
- In order to use the logging system you must create the database using the logger_db.sql `.sql` file located in the project app's core logger folder.
- Example of logger implementation:
```php
  $this->logger = LoggerFactory::initializeLogger();

  public function register($username, $email, $passwordHash){
        
        $success = $this->db->insert(
        "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)", 
        [$username, $email, $passwordHash]
        );

        if ($success) {
            $this->logger->info("User created successfully", [
                'email' => $email,
            ]);
        } else {
            $this->logger->error("Failed to create user", [
                'email' => $email,
            ]);
        }

        return $success;
    }

```

## Libraries Used
### Form Builder
- A custom JavaScript library for dynamic form creation.

### Validation
- Client-side and server-side validation for secure data handling.

## Built With
- **PHP MVC** - Backend architecture
- **MySQL & phpMyAdmin** - Database management
- **Bootstrap 5** - Frontend framework
- **JavaScript** - Client-side interactions
- **XAMPP** - Local server environment

## License
This project is licensed under the MIT License.

## Contact
For inquiries or contributions, contact:
- Email: contact@pecollins.com
- GitHub: [pecollinsdev](https://github.com/pecollinsdev)

---

