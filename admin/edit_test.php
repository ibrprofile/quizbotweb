<?php
require_once '../config.php';

$test_id = $_GET['id'] ?? 0;

// Получение информации о тесте
$stmt = $pdo->prepare("SELECT * FROM tests WHERE id = ?");
$stmt->execute([$test_id]);
$test = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test) {
    die("Тест не найден.");
}

// Обработка обновления теста
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_test'])) {
    $title = $_POST['test_title'];
    $timer = intval($_POST['timer']);
    $questions = $_POST['questions'];
    $answers = $_POST['answers'];
    $correct_answers = $_POST['correct_answers'];
    $image_urls = $_POST['image_urls'];

    try {
        $pdo->beginTransaction();

        // Обновление названия теста и таймера
        $stmt = $pdo->prepare("UPDATE tests SET title = ?, timer = ? WHERE id = ?");
        $stmt->execute([$title, $timer, $test_id]);

        // Получение существующих вопросов
        $stmt = $pdo->prepare("SELECT id FROM questions WHERE test_id = ?");
        $stmt->execute([$test_id]);
        $existing_questions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Обработка вопросов
        foreach ($questions as $index => $question_text) {
            $question_id = $_POST['question_ids'][$index] ?? null;
            $image_url = $image_urls[$index] ?? null;

            if ($question_id && in_array($question_id, $existing_questions)) {
                // Обновление существующего вопроса
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, image_url = ? WHERE id = ?");
                $stmt->execute([$question_text, $image_url, $question_id]);
            } else {
                // Добавление нового вопроса
                $stmt = $pdo->prepare("INSERT INTO questions (test_id, question_text, image_url) VALUES (?, ?, ?)");
                $stmt->execute([$test_id, $question_text, $image_url]);
                $question_id = $pdo->lastInsertId();
            }

            // Обновление существующих ответов или добавление новых
            if (isset($answers[$question_id])) {
                foreach ($answers[$question_id] as $answer_index => $answer_text) {
                    $is_correct = isset($correct_answers[$question_id]) && $correct_answers[$question_id] == $answer_index ? 1 : 0;
                    $answer_id = $_POST['answer_ids'][$question_id][$answer_index] ?? null;

                    if ($answer_id) {
                        // Обновление существующего ответа
                        $stmt = $pdo->prepare("UPDATE answers SET answer_text = ?, is_correct = ? WHERE id = ?");
                        $stmt->execute([$answer_text, $is_correct, $answer_id]);
                    } else {
                        // Добавление нового ответа
                        $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $answer_text, $is_correct]);
                    }
                }
            }

            // Удаление ответов, которых больше нет в форме
            $stmt = $pdo->prepare("DELETE FROM answers WHERE question_id = ? AND id NOT IN (" . implode(',', array_filter($_POST['answer_ids'][$question_id] ?? [])) . ")");
            $stmt->execute([$question_id]);


            // Удаление вопроса из списка существующих
            $existing_questions = array_diff($existing_questions, [$question_id]);
        }

        // Удаление оставшихся вопросов, которые не были обновлены
        foreach ($existing_questions as $question_id) {
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$question_id]);
        }

        $pdo->commit();
        $success_message = "Тест успешно обновлен!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Ошибка при обновлении теста: " . $e->getMessage();
    }
}

// Получение вопросов и ответов для теста
$stmt = $pdo->prepare("SELECT q.id as question_id, q.question_text, q.image_url, a.id as answer_id, a.answer_text, a.is_correct 
                     FROM questions q 
                     LEFT JOIN answers a ON q.id = a.question_id 
                     WHERE q.test_id = ?
                     ORDER BY q.id, a.id");
$stmt->execute([$test_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$questions = [];
foreach ($results as $row) {
    if (!isset($questions[$row['question_id']])) {
        $questions[$row['question_id']] = [
            'id' => $row['question_id'],
            'text' => $row['question_text'],
            'image_url' => $row['image_url'],
            'answers' => []
        ];
    }
    if ($row['answer_id']) {
        $questions[$row['question_id']]['answers'][] = [
            'id' => $row['answer_id'],
            'text' => $row['answer_text'],
            'is_correct' => $row['is_correct']
        ];
    }
}

// Получение статистики теста
$stmt = $pdo->prepare("SELECT * FROM test_results WHERE test_id = ? ORDER BY end_time DESC");
$stmt->execute([$test_id]);
$test_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование теста</title>
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
        }
        h1 {
            color: #1e90ff;
            text-align: center;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        form {
            margin-top: 20px;
        }
        input[type="text"], input[type="number"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .question {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .answer {
            margin-bottom: 10px;
        }
        button {
            background-color: #1e90ff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #187bcd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #1e90ff;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Редактирование теста: <?php echo htmlspecialchars($test['title']); ?></h1>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form id="edit-test-form" method="POST">
            <input type="text" name="test_title" value="<?php echo htmlspecialchars($test['title']); ?>" required>
            <input type="number" name="timer" value="<?php echo $test['timer']; ?>" placeholder="Время на прохождение теста (в секундах)" required min="5" max="3600">
            <div id="questions-container">
                <?php foreach ($questions as $question): ?>
                    <div class="question">
                        <input type="hidden" name="question_ids[]" value="<?php echo $question['id']; ?>">
                        <textarea name="questions[]" required><?php echo htmlspecialchars($question['text']); ?></textarea>
                        <input type="text" name="image_urls[]" value="<?php echo htmlspecialchars($question['image_url'] ?? ''); ?>" placeholder="Ссылка на изображение (не обязательно)">
                        <div class="answers-container">
                            <?php foreach ($question['answers'] as $index => $answer): ?>
                                <div class="answer">
                                    <input type="hidden" name="answer_ids[<?php echo $question['id']; ?>][]" value="<?php echo $answer['id']; ?>">
                                    <input type="radio" name="correct_answers[<?php echo $question['id']; ?>]" value="<?php echo $index; ?>" <?php echo $answer['is_correct'] ? 'checked' : ''; ?>>
                                    <input type="text" name="answers[<?php echo $question['id']; ?>][]" value="<?php echo htmlspecialchars($answer['text']); ?>" required>
                                    <button type="button" class="remove-answer"><i class="fas fa-times"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="add-answer"><i class="fas fa-plus"></i> Добавить вариант ответа</button>
                        <button type="button" class="remove-question"><i class="fas fa-trash"></i> Удалить вопрос</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-question"><i class="fas fa-plus"></i> Добавить вопрос</button>
            <button type="submit" name="update_test"><i class="fas fa-save"></i> Обновить тест</button>
        </form>

        <h2>Статистика теста</h2>
        <table>
            <thead>
                <tr>
                    <th>ID пользователя</th>
                    <th>Имя пользователя</th>
                    <th>Имя</th>
                    <th>Фамилия</th>
                    <th>Дата прохождения</th>
                    <th>Время прохождения</th>
                    <th>Правильные ответы</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($test_results as $result): ?>
                    <tr>
                        <td><?php echo $result['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($result['username']); ?></td>
                        <td><?php echo htmlspecialchars($result['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($result['last_name']); ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($result['start_time'])); ?></td>
                        <td><?php echo $result['completion_time']; ?> сек</td>
                        <td><?php echo $result['correct_answers']; ?> из <?php echo $result['total_questions']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const questionsContainer = document.getElementById('questions-container');
            const addQuestionButton = document.getElementById('add-question');
            const editTestForm = document.getElementById('edit-test-form');
            let questionCount = <?php echo count($questions); ?>;

            function addQuestion() {
                questionCount++;
                const questionDiv = document.createElement('div');
                questionDiv.className = 'question';
                questionDiv.innerHTML = `
                    <input type="hidden" name="question_ids[]" value="">
                    <textarea name="questions[]" placeholder="Вопрос ${questionCount}" required></textarea>
                    <input type="text" name="image_urls[]" placeholder="Ссылка на изображение (не обязательно)">
                    <div class="answers-container"></div>
                    <button type="button" class="add-answer"><i class="fas fa-plus"></i> Добавить вариант ответа</button>
                    <button type="button" class="remove-question"><i class="fas fa-trash"></i> Удалить вопрос</button>
                `;
                questionsContainer.appendChild(questionDiv);

                const addAnswerButton = questionDiv.querySelector('.add-answer');
                const answersContainer = questionDiv.querySelector('.answers-container');
                let answerCount = 0;

                function addAnswer() {
                    answerCount++;
                    const answerDiv = document.createElement('div');
                    answerDiv.className = 'answer';
                    answerDiv.innerHTML = `
                        <input type="hidden" name="answer_ids[${questionCount - 1}][]" value="">
                        <input type="radio" name="correct_answers[${questionCount - 1}]" value="${answerCount - 1}">
                        <input type="text" name="answers[${questionCount - 1}][]" placeholder="Вариант ответа ${answerCount}" required>
                        <button type="button" class="remove-answer"><i class="fas fa-times"></i></button>
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

            // Добавляем обработчики для существующих вопросов и ответов
            document.querySelectorAll('.add-answer').forEach(button => {
                button.addEventListener('click', function() {
                    const answersContainer = this.previousElementSibling;
                    const answerCount = answersContainer.children.length;
                    const questionIndex = Array.from(questionsContainer.children).indexOf(this.closest('.question'));

                    const answerDiv = document.createElement('div');
                    answerDiv.className = 'answer';
                    answerDiv.innerHTML = `
                        <input type="hidden" name="answer_ids[${questionIndex}][]" value="">
                        <input type="radio" name="correct_answers[${questionIndex}]" value="${answerCount}">
                        <input type="text" name="answers[${questionIndex}][]" placeholder="Новый вариант ответа" required>
                        <button type="button" class="remove-answer"><i class="fas fa-times"></i></button>
                    `;
                    answersContainer.appendChild(answerDiv);

                    answerDiv.querySelector('.remove-answer').addEventListener('click', function() {
                        answersContainer.removeChild(answerDiv);
                    });
                });
            });

            document.querySelectorAll('.remove-question').forEach(button => {
                button.addEventListener('click', function() {
                    questionsContainer.removeChild(this.closest('.question'));
                });
            });

            document.querySelectorAll('.remove-answer').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.answers-container').removeChild(this.closest('.answer'));
                });
            });

            // Добавляем валидацию формы перед отправкой
            editTestForm.addEventListener('submit', function(event) {
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

