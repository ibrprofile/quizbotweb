<?php

$host = 'localhost';
$dbname = 'as';  // Убедитесь, что это правильное имя вашей базы данных
$username = 'as';
$password = 'Asasasas1';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для создания таблиц, если они не существуют
function createTablesIfNotExist($pdo) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS tests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            timer INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT,
            question_text TEXT NOT NULL,
            image_url VARCHAR(255),
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question_id INT,
            answer_text TEXT NOT NULL,
            is_correct BOOLEAN NOT NULL,
            FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS test_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT,
            user_id INT,
            username VARCHAR(255),
            first_name VARCHAR(255),
            last_name VARCHAR(255),
            start_time DATETIME,
            end_time DATETIME,
            completion_time INT,
            correct_answers INT,
            total_questions INT,
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS user_test_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            test_id INT,
            attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
        )"
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }
}

// Вызов функции для создания таблиц
createTablesIfNotExist($pdo);

?>

