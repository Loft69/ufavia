-- UFAvia — схема базы данных
CREATE DATABASE IF NOT EXISTS ufavia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ufavia;

-- Города и аэропорты
CREATE TABLE IF NOT EXISTS airports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(3) NOT NULL UNIQUE,
    city VARCHAR(64) NOT NULL,
    name VARCHAR(128) NOT NULL,
    UNIQUE KEY uq_city_code (city, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Авиакомпании
CREATE TABLE IF NOT EXISTS airlines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    code VARCHAR(3) NOT NULL UNIQUE,
    aircraft VARCHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Рейсы
CREATE TABLE IF NOT EXISTS flights (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    flight_number VARCHAR(10) NOT NULL,
    airline_id INT UNSIGNED NOT NULL,
    from_airport_id INT UNSIGNED NOT NULL,
    to_airport_id INT UNSIGNED NOT NULL,
    departure_date DATE NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    transfers TINYINT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(10, 2) NOT NULL,
    seats_total SMALLINT UNSIGNED NOT NULL DEFAULT 180,
    seats_left SMALLINT UNSIGNED NOT NULL DEFAULT 180,
    cabin VARCHAR(32) NOT NULL DEFAULT 'Эконом',
    baggage VARCHAR(64) NOT NULL DEFAULT 'Ручная кладь 10 кг',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (airline_id) REFERENCES airlines(id),
    FOREIGN KEY (from_airport_id) REFERENCES airports(id),
    FOREIGN KEY (to_airport_id) REFERENCES airports(id),
    INDEX idx_route_date (from_airport_id, to_airport_id, departure_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Пользователи
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(64) NOT NULL,
    last_name VARCHAR(64) NOT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    citizenship VARCHAR(64) DEFAULT 'Россия',
    document_type VARCHAR(64) DEFAULT 'Паспорт РФ',
    document_number VARCHAR(32) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заказы
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    flight_id INT UNSIGNED NOT NULL,
    ticket_number VARCHAR(20) NOT NULL UNIQUE,
    passenger_first_name VARCHAR(64) NOT NULL,
    passenger_last_name VARCHAR(64) NOT NULL,
    passenger_birth_date DATE DEFAULT NULL,
    passenger_document VARCHAR(64) DEFAULT NULL,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(32) DEFAULT NULL,
    extras JSON DEFAULT NULL,
    payment_method VARCHAR(32) NOT NULL DEFAULT 'card',
    ticket_price DECIMAL(10, 2) NOT NULL,
    service_fee DECIMAL(10, 2) NOT NULL DEFAULT 300,
    extras_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'paid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (flight_id) REFERENCES flights(id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
