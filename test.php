<?php
require_once 'config.php';

// Убедимся, что таблицы существуют
createTablesIfNotExist($pdo);

session_start();

// Настройка обработки ошибок PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Получение и валидация параметров
$test_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$username = isset($_GET['username']) ? $_GET['username'] : '';
$first_name = isset($_GET['first_name']) ? $_GET['first_name'] : '';
$last_name = isset($_GET['last_name']) ? $_GET['last_name'] : '';

// Проверка обязательных параметров
if (!$test_id || !$user_id) {
    http_response_code(400);
    die("Ошибка: Недостаточно данных для прохождения теста.");
}

try {
    // Проверка, проходил ли пользователь уже этот тест
    $stmt = $pdo->prepare("SELECT * FROM user_test_attempts WHERE user_id = ? AND test_id = ?");
    $stmt->execute([$user_id, $test_id]);
    $attempt = $stmt->fetch();

    if ($attempt) {
        http_response_code(403);
        die("Вы уже проходили этот тест. Повторное прохождение невозможно.");
    }

    // Получение информации о тесте
    $stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
    $stmt->execute([$test_id]);
    $test = $stmt->fetch();

    if (!$test) {
        http_response_code(404);
        die("Тест не найден.");
    }

    // Получение вопросов теста
    $stmt = $pdo->prepare("SELECT q.id, q.question_text, q.image_url, a.id AS answer_id, a.answer_text, a.is_correct 
                           FROM questions q 
                           LEFT JOIN answers a ON q.id = a.question_id 
                           WHERE q.test_id = ?
                           ORDER BY q.id, a.id");
    $stmt->execute([$test_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($results)) {
        http_response_code(404);
        die("Вопросы для теста не найдены.");
    }

    $questions = [];
    foreach ($results as $row) {
        if (!isset($questions[$row['id']])) {
            $questions[$row['id']] = [
                'id' => $row['id'],
                'text' => $row['question_text'],
                'image_url' => $row['image_url'],
                'answers' => []
            ];
        }
        if ($row['answer_id']) {
            $questions[$row['id']]['answers'][] = [
                'id' => $row['answer_id'],
                'text' => $row['answer_text'],
                'is_correct' => $row['is_correct']
            ];
        }
    }

    // Обработка отправки теста
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
        // Повторная проверка на наличие попытки прохождения теста
        $stmt = $pdo->prepare("SELECT * FROM user_test_attempts WHERE user_id = ? AND test_id = ?");
        $stmt->execute([$user_id, $test_id]);
        $attempt = $stmt->fetch();

        if ($attempt) {
            http_response_code(403);
            die("Вы уже проходили этот тест. Повторное прохождение невозможно.");
        }

        $start_time = $_POST['start_time'];
        $end_time = time();
        $completion_time = $end_time - $start_time;
        $user_answers = $_POST['answers'] ?? [];

        $correct_answers = 0;
        foreach ($questions as $question) {
            $question_id = $question['id'];
            $user_answer = $user_answers[$question_id] ?? null;
            foreach ($question['answers'] as $answer) {
                if ($answer['id'] == $user_answer && $answer['is_correct']) {
                    $correct_answers++;
                    break;
                }
            }
        }

        try {
            // Проверяем наличие всех необходимых данных
            if (empty($user_answers)) {
                throw new Exception("Не получены ответы на вопросы");
            }

            // Начинаем транзакцию
            $pdo->beginTransaction();

            try {
                // Сначала записываем попытку прохождения теста
                $stmt = $pdo->prepare("INSERT INTO user_test_attempts (user_id, test_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $test_id]);

                // Затем записываем результаты теста
                $stmt = $pdo->prepare("INSERT INTO test_results 
                    (test_id, user_id, username, first_name, last_name, start_time, end_time, completion_time, correct_answers, total_questions) 
                    VALUES (?, ?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?, ?, ?)");
                
                $params = [
                    $test_id,
                    $user_id,
                    $username,
                    $first_name,
                    $last_name,
                    $start_time,
                    $end_time,
                    $completion_time,
                    $correct_answers,
                    count($questions)
                ];
                
                $stmt->execute($params);
                $result_id = $pdo->lastInsertId();

                // Если всё успешно, фиксируем транзакцию
                $pdo->commit();

                // Сохраняем ID результата в сессии
                $_SESSION['test_completed'] = true;
                $_SESSION['result_id'] = $result_id;

                // Перенаправление на страницу с результатами
                header("Location: test_results.php?test_id=$test_id&user_id=$user_id&result_id=$result_id");
                exit;

            } catch (PDOException $e) {
                // В случае ошибки откатываем транзакцию
                $pdo->rollBack();
                error_log("Database error while saving test results: " . $e->getMessage() . "\nSQL State: " . $e->getCode());
                throw new Exception("Не удалось сохранить результаты теста. Пожалуйста, попробуйте позже.");
            }
        } catch (Exception $e) {
            error_log("Error in test submission: " . $e->getMessage());
            echo "<div style='color: red; text-align: center; margin: 20px;'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</div>";
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Error in test.php: " . $e->getMessage());
    http_response_code(500);
    die("Произошла ошибка при загрузке теста. Пожалуйста, попробуйте позже.");
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($test['title']); ?></title>
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
        h1 {
            color: #1e90ff;
            text-align: center;
            margin-bottom: 30px;
        }
        .question {
            margin-bottom: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .question:hover {
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .answer {
            margin-bottom: 10px;
        }
        .answer label {
            display: block;
            padding: 10px;
            background-color: #e6f3ff;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .answer label:hover {
            background-color: #cce6ff;
        }
        input[type="radio"] {
            margin-right: 10px;
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
        #timer {
            font-size: 1.2em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .question-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <h1><?php echo htmlspecialchars($test['title']); ?></h1>
        
        <div id="start-screen">
            <p>У вас будет <?php echo $test['timer']; ?> секунд на прохождение теста.</p>
            <button id="start-test">Начать тест</button>
        </div>

        <form id="test-form" style="display: none;" method="POST">
            <input type="hidden" name="start_time" id="start-time">
            <div id="timer"></div>
            <?php foreach ($questions as $question_id => $question): ?>
                <div class="question" id="question-<?php echo $question_id; ?>" style="display: none;">
                    <h2><?php echo htmlspecialchars($question['text']); ?></h2>
                    <?php if (!empty($question['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($question['image_url']); ?>" alt="Изображение к вопросу" class="question-image">
                    <?php endif; ?>
                    <?php foreach ($question['answers'] as $answer): ?>
                        <div class="answer">
                            <label>
                                <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $answer['id']; ?>">
                                <?php echo htmlspecialchars($answer['text']); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <button type="button" id="next-question">Далее</button>
            <button type="submit" id="submit-test" name="submit_test" style="display: none;">Завершить тест</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startScreen = document.getElementById('start-screen');
            const testForm = document.getElementById('test-form');
            const startTestButton = document.getElementById('start-test');
            const nextQuestionButton = document.getElementById('next-question');
            const submitTestButton = document.getElementById('submit-test');
            const timerElement = document.getElementById('timer');
            const startTimeInput = document.getElementById('start-time');
            const questions = document.querySelectorAll('.question');
            let currentQuestion = 0;
            let timer;

            startTestButton.addEventListener('click', function() {
                startScreen.style.display = 'none';
                testForm.style.display = 'block';
                showQuestion(currentQuestion);
                startTimer(<?php echo $test['timer']; ?>);
                startTimeInput.value = Math.floor(Date.now() / 1000);
            });

            nextQuestionButton.addEventListener('click', function() {
                if (currentQuestion < questions.length - 1) {
                    currentQuestion++;
                    showQuestion(currentQuestion);
                }
                if (currentQuestion === questions.length - 1) {
                    nextQuestionButton.style.display = 'none';
                    submitTestButton.style.display = 'inline-block';
                }
            });

            function showQuestion(index) {
                questions.forEach(q => q.style.display = 'none');
                questions[index].style.display = 'block';
            }

            function startTimer(duration) {
                let timeLeft = duration;
                timer = setInterval(function() {
                    let minutes = Math.floor(timeLeft / 60);
                    let seconds = timeLeft % 60;
                    timerElement.textContent = `Осталось времени: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                    if (--timeLeft < 0) {
                        clearInterval(timer);
                        alert('Время истекло!');
                        testForm.submit();
                    }
                }, 1000);
            }
        });
    </script>
</body>
</html>

