<?php
require_once '../config.php';

$success_message = '';
$error_message = '';

// Обработка создания нового теста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    $title = $_POST['test_title'];
    $timer = intval($_POST['timer']);
    $questions = $_POST['questions'];
    $answers = $_POST['answers'];
    $correct_answers = $_POST['correct_answers'];
    $image_urls = $_POST['image_urls'];

    // Проверка наличия правильного ответа для каждого вопроса
    $error = false;
    foreach ($questions as $index => $question) {
        if (!isset($correct_answers[$index]) || empty($correct_answers[$index])) {
            $error_message = "Пожалуйста, выберите правильный ответ для всех вопросов.";
            $error = true;
            break;
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO tests (title, timer) VALUES (?, ?)");
        $stmt->execute([$title, $timer]);
        $test_id = $pdo->lastInsertId();

        foreach ($questions as $index => $question_text) {
            $image_url = $image_urls[$index] ?? null;
            $stmt = $pdo->prepare("INSERT INTO questions (test_id, question_text, image_url) VALUES (?, ?, ?)");
            $stmt->execute([$test_id, $question_text, $image_url]);
            $question_id = $pdo->lastInsertId();

            foreach ($answers[$index] as $answer_index => $answer_text) {
                $is_correct = in_array($answer_index, $correct_answers[$index]) ? 1 : 0;
                $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                $stmt->execute([$question_id, $answer_text, $is_correct]);
            }
        }

        $bot_username = 'hhdjsnsbot'; // Замените на имя вашего бота
        $success_message = "Тест успешно создан! Ссылка для прохождения: https://t.me/$bot_username?start=$test_id";
    }
}

// Получение списка всех тестов
$stmt = $pdo->query("SELECT * FROM tests ORDER BY created_at DESC");
$tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание теста</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f0f8ff;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .container:hover {
            box-shadow: 0 0 30px rgba(0,0,0,0.2);
        }
        h1, h2 {
            color: #1e90ff;
            text-align: center;
            margin-bottom: 30px;
        }
        form {
            margin-bottom: 40px;
        }
        input[type="text"], input[type="number"], textarea {
            width: 80%; /* Updated width */
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
            border-color: #1e90ff;
            outline: none;
        }
        button {
            background-color: #1e90ff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #187bcd;
        }
        .question {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .question:hover {
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .answer {
            margin-bottom: 15px;
        }
        .answer input[type="radio"] {
            margin-right: 10px;
        }
        .test-list {
            list-style-type: none;
            padding: 0;
        }
        .test-list li {
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .test-list li:hover {
            background-color: #e6f3ff;
        }
        .test-list a {
            color: #1e90ff;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .test-list a:hover {
            color: #187bcd;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .icon {
            margin-right: 10px;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <h1><i class="fas fa-pencil-alt icon"></i>Создание теста</h1>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle icon"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle icon"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form id="create-test-form" method="POST">
            <input type="text" name="test_title" placeholder="Название теста" required>
            <input type="number" name="timer" placeholder="Время на прохождение теста (в секундах)" required min="5" max="3600">
            <div id="questions-container"></div>
            <button type="button" id="add-question"><i class="fas fa-plus icon"></i>Добавить вопрос</button>
            <button type="submit" name="create_test"><i class="fas fa-save icon"></i>Создать тест</button>
        </form>

        <h2><i class="fas fa-list icon"></i>Все тесты</h2>
        <ul class="test-list">
            <?php foreach ($tests as $test): ?>
                <li>
                    <a href="edit_test.php?id=<?php echo $test['id']; ?>">
                        <i class="fas fa-edit icon"></i><?php echo htmlspecialchars($test['title']); ?> 
                        (создан <?php echo date('d.m.Y H:i', strtotime($test['created_at'])); ?>)
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const questionsContainer = document.getElementById('questions-container');
            const addQuestionButton = document.getElementById('add-question');
            const createTestForm = document.getElementById('create-test-form');
            let questionCount = 0;

            function addQuestion() {
                questionCount++;
                const questionDiv = document.createElement('div');
                questionDiv.className = 'question fade-in';
                questionDiv.innerHTML = `
                    <textarea name="questions[]" placeholder="Вопрос ${questionCount}" required></textarea>
                    <input type="text" name="image_urls[]" placeholder="Ссылка на изображение (не обязательно)">
                    <div class="answers-container"></div>
                    <button type="button" class="add-answer"><i class="fas fa-plus icon"></i>Добавить вариант ответа</button>
                    <button type="button" class="remove-question"><i class="fas fa-trash icon"></i>Удалить вопрос</button>
                `;
                questionsContainer.appendChild(questionDiv);

                const addAnswerButton = questionDiv.querySelector('.add-answer');
                const answersContainer = questionDiv.querySelector('.answers-container');
                let answerCount = 0;

                function addAnswer() {
                    answerCount++;
                    const answerDiv = document.createElement('div');
                    answerDiv.className = 'answer fade-in';
                    answerDiv.innerHTML = `
                        <input type="radio" name="correct_answers[${questionCount - 1}][]" value="${answerCount - 1}" required>
                        <input type="text" name="answers[${questionCount - 1}][]" placeholder="Вариант ответа ${answerCount}" required>
                        <button type="button" class="remove-answer"><i class="fas fa-times icon"></i></button>
                    `;
                    answersContainer.appendChild(answerDiv);

                    answerDiv.querySelector('.remove-answer').addEventListener('click', function() {
                        answersContainer.removeChild(answerDiv);
                    });
                }

                addAnswerButton.addEventListener('click', addAnswer);
                questionDiv.querySelector('.remove-question').addEventListener('click', function() {
                    questionsContainer.removeChild(questionDiv);
                });

                // Добавляем два варианта ответа по умолчанию
                addAnswer();
                addAnswer();
            }

            addQuestionButton.addEventListener('click', addQuestion);

            // Добавляем первый вопрос по умолчанию
            addQuestion();

            // Добавляем валидацию формы перед отправкой
            createTestForm.addEventListener('submit', function(event) {
                let isValid = true;
                const questions = document.querySelectorAll('.question');
                
                questions.forEach((question, index) => {
                    const answers = question.querySelectorAll('input[type="radio"]:checked');
                    if (answers.length === 0) {
                        isValid = false;
                        alert(`Пожалуйста, выберите правильный ответ для вопроса ${index + 1}.`);
                    }
                });

                if (!isValid) {
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>

