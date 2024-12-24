import telebot
from telebot.types import InlineKeyboardMarkup, InlineKeyboardButton, WebAppInfo
import mysql.connector
from mysql.connector import Error
import sqlite3

BOT_TOKEN = '7872521858:AAFJzc-fBM5pzFgpn_NdURFU_A3Oz_sA47w'
WEBAPP_URL = 'https://ibrprofile.ru/2/test.php'

bot = telebot.TeleBot(BOT_TOKEN)

# Admin IDs
admins = [2006363325, 6649886905]  # Replace with actual admin Telegram IDs

# Database setup
def init_db():
    conn = sqlite3.connect('users.db')
    c = conn.cursor()
    c.execute('''CREATE TABLE IF NOT EXISTS users
                 (id INTEGER PRIMARY KEY, user_id INTEGER UNIQUE)''')
    conn.commit()
    conn.close()

def add_user(user_id):
    conn = sqlite3.connect('users.db')
    c = conn.cursor()
    c.execute("INSERT OR IGNORE INTO users (user_id) VALUES (?)", (user_id,))
    conn.commit()
    conn.close()

def get_all_users():
    conn = sqlite3.connect('users.db')
    c = conn.cursor()
    c.execute("SELECT user_id FROM users")
    users = c.fetchall()
    conn.close()
    return [user[0] for user in users]


# Initialize database
init_db()



@bot.message_handler(commands=['send'])
def send_broadcast(message):
    if message.from_user.id not in admins:
        bot.reply_to(message, "У вас нет прав для выполнения этой команды.")
        return

    # Ask for the broadcast message
    msg = bot.reply_to(message, "Введите сообщение для рассылки:")
    bot.register_next_step_handler(msg, process_broadcast_message)

def process_broadcast_message(message):
    broadcast_message = message.text
    users = get_all_users()
    
    success_count = 0
    for user_id in users:
        try:
            bot.send_message(user_id, broadcast_message)
            success_count += 1
        except Exception as e:
            print(f"Failed to send message to user {user_id}: {e}")
    
    bot.reply_to(message, f"Рассылка завершена. Успешно отправлено {success_count} из {len(users)} пользователям.")

@bot.message_handler(commands=['start'])
def send_welcome(message):
    user_id = message.from_user.id
    add_user(user_id)
    if len(message.text.split()) > 1:
        test_id = message.text.split()[1]
        markup = InlineKeyboardMarkup()
        markup.add(InlineKeyboardButton("Начать тест", web_app=WebAppInfo(url=f"{WEBAPP_URL}?id={test_id}&user_id={message.from_user.id}&username={message.from_user.username}&first_name={message.from_user.first_name}&last_name={message.from_user.last_name}")))
        bot.reply_to(message, "Нажмите на кнопку ниже, чтобы начать прохождение теста:", reply_markup=markup)
    else:
        bot.reply_to(message, "Бот для квеста!")

@bot.message_handler(func=lambda message: True)
def echo_all(message):
    bot.reply_to(message, "Используйте команду /start для начала работы с ботом.")

if __name__ == "__main__":
    bot.infinity_polling(timeout=10, long_polling_timeout=5)