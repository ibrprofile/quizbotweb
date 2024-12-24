# Разработка Quiz Bot на базе Mini App для Telegram: от идеи до реализации


## Введение

Привет, Хабр! Сегодня я хочу поделиться с вами опытом разработки Quiz Bot - интерактивного бота для проведения тестов и викторин в Telegram. Этот проект объединяет в себе мощь Telegram Bot API, классические веб-технологии и удобство Mini App, создавая уникальный опыт для пользователей.

## Почему Quiz Bot?

В эпоху цифрового обучения и развлечений, потребность в интерактивных инструментах для проверки знаний и проведения викторин становится все более актуальной. Telegram, как одна из самых популярных платформ для общения, предоставляет идеальную среду для реализации такого проекта. Quiz Bot позволяет:

1. Создавать и проходить тесты прямо в Telegram
2. Использовать удобный интерфейс Mini App для взаимодействия с ботом
3. Администраторам легко управлять тестами и анализировать результаты

## Технический стек

Для реализации проекта мы использовали следующие технологии:

- **Backend**: PHP, MySQL
- **Frontend**: HTML, CSS, JavaScript
- **Bot**: Python (библиотека telebot)
- **Mini App**: Telegram Bot API

## Архитектура проекта

Quiz Bot состоит из нескольких ключевых компонентов:

1. **Telegram Bot**: Основной интерфейс взаимодействия с пользователем
2. **Mini App**: Веб-приложение для создания и прохождения тестов
3. **Backend API**: Обрабатывает запросы от бота и Mini App
4. **База данных**: Хранит информацию о тестах, вопросах и результатах

## Разработка Backend

Backend нашего проекта разработан на PHP с использованием MySQL в качестве базы данных. Основные компоненты включают:

- `config.php`: Конфигурация подключения к базе данных
- `test.php`: Обработка запросов на прохождение теста
- `test_results.php`: Отображение результатов теста
- `admin/index.php`: Панель администратора для управления тестами
- `admin/edit_test.php`: Интерфейс для редактирования тестов

Вот пример кода для создания таблиц в базе данных:

```php
function createTablesIfNotExist($pdo) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS tests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            timer INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        // ... другие таблицы
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }
}
```

## Разработка Frontend

Для frontend мы использовали классический подход с HTML, CSS и JavaScript. Это позволило создать легкий и быстрый интерфейс, который отлично работает в среде Telegram Mini App. Основные компоненты включают:

- Страницу прохождения теста
- Панель администратора для создания и редактирования тестов
- Компонент отображения результатов


Вот пример HTML-структуры для отображения вопроса теста:

```html
<div class="question">
    <h2 id="question-text"></h2>
    <img id="question-image" src="" alt="Question Image" style="display: none;">
    <div id="answers-container" class="answers">
        <!-- Ответы будут добавлены динамически с помощью JavaScript -->
    </div>
</div>
```

И соответствующий JavaScript для заполнения вопроса:

```javascript
function displayQuestion(question) {
    document.getElementById('question-text').textContent = question.text;
    const imageElement = document.getElementById('question-image');
    if (question.image_url) {
        imageElement.src = question.image_url;
        imageElement.style.display = 'block';
    } else {
        imageElement.style.display = 'none';
    }
    
    const answersContainer = document.getElementById('answers-container');
    answersContainer.innerHTML = '';
    question.answers.forEach((answer, index) => {
        const button = document.createElement('button');
        button.textContent = answer.text;
        button.onclick = () => selectAnswer(index);
        answersContainer.appendChild(button);
    });
}
```

## Разработка Telegram Bot

Бот разработан на Python с использованием библиотеки telebot. Он обрабатывает команды пользователя и взаимодействует с backend API. Вот пример обработки команды `/start`:

```python
@bot.message_handler(commands=['start'])
def send_welcome(message):
    user_id = message.from_user.id
    add_user(user_id)
    bot.reply_to(message, "Добро пожаловать! Выберите тест для прохождения.")
```

## Интеграция Mini App

Mini App интегрирована в бота с использованием Telegram Bot API. Это позволяет пользователям взаимодействовать с Quiz Bot через удобный веб-интерфейс прямо в Telegram.

## Проблемы и их решения

В процессе разработки мы столкнулись с несколькими проблемами:

1. **Сохранение состояния теста**: Решено использованием сессий на сервере.
2. **Ограничение времени на прохождение теста**: Реализовано с помощью JavaScript таймера на клиенте и проверки на сервере.
3. **Обработка одновременных запросов**: Решено оптимизацией запросов к базе данных и использованием транзакций.


## Результаты и планы на будущее

Quiz Bot успешно запущен и уже используется для проведения различных тестов и викторин. В будущем мы планируем:

1. Добавить поддержку различных типов вопросов (множественный выбор, открытые вопросы)
2. Реализовать систему рейтингов и достижений
3. Интегрировать аналитику для более детального анализа результатов


## Доступность кода

Весь код проекта, включая файлы сайтов и телеграм-бота, доступен на GitHub в моем репозитории: [https://github.com/ibrprofile/quizbotweb](https://github.com/ibrprofile/quizbotweb). Вы можете изучить код, предложить улучшения или даже использовать его как основу для своих проектов.

## Заключение

Разработка Quiz Bot была увлекательным путешествием в мир Telegram Bot API и Mini App. Этот проект демонстрирует, как классические веб-технологии могут быть интегрированы в популярные платформы обмена сообщениями, создавая новые возможности для обучения и развлечения.

Надеюсь, что наш опыт будет полезен другим разработчикам, желающим создать свои собственные интерактивные боты для Telegram. Если у вас есть вопросы или предложения по улучшению проекта, буду рад обсудить их в комментариях!
