
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты теста</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            max-width: 600px;
            width: 90%;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
            animation: fadeIn 0.5s ease-in;
        }
        h1 {
            color: #1e90ff;
            margin-bottom: 20px;
        }
        .message {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        .icon {
            font-size: 4em;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        .test-info {
            font-style: italic;
            color: #666;
            margin-bottom: 10px;
        }
        .results {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .score {
            font-size: 1.5em;
            color: #1e90ff;
            margin: 10px 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container">
        <i class="fas fa-check-circle icon"></i>
        <h1>Тест завершен!</h1>
        <p class="message">Результаты отправлены администратору!</p>
        
    </div>
</body>
</html>

