/* !! СТРОГО ПО ТЗ: НАЗВАНИЕ БАЗЫ ДАННЫХ !! */
CREATE DATABASE IF NOT EXISTS conferences_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE conferences_db;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fio VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE premises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

/* !! СТРОГО ПО ТЗ: НАЗВАНИЯ СТАТУСОВ ЗАЯВОК !! */
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    premise_id INT NOT NULL,
    event_date DATETIME NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status ENUM('Новая', 'Мероприятие назначено', 'Мероприятие завершено') DEFAULT 'Новая',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (premise_id) REFERENCES premises(id)
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    booking_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

INSERT INTO roles (name) VALUES ('Пользователь'), ('Администратор');

/* !! СТРОГО ПО ТЗ: НАЗВАНИЯ ТИПОВ ПОМЕЩЕНИЙ ИЗ СПРАВОЧНИКА !! */
INSERT INTO premises (name) VALUES ('Аудитория'), ('Коворкинг'), ('Кинозал');

/* !! СТРОГО ПО ТЗ: ФИКСИРОВАННЫЙ ЛОГИН АДМИНИСТРАТОРА !! */
INSERT INTO users (fio, phone, email, login, password, role_id) 
VALUES ('Главный Администратор', '88005553535', 'admin@conferences.rf', 'Admin26', '$2y$10$3g0b7C0O4zG9ZlW9H16vneexyB10EshB/b7OAnZkH9sYhEaM8Lde.', 2);
