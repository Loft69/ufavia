-- UFAvia — демонстрационные данные
USE ufavia;

INSERT INTO airports (code, city, name) VALUES
('UFA', 'Уфа', 'Международный аэропорт Уфа'),
('VKO', 'Москва', 'Внуково'),
('SVO', 'Москва', 'Шереметьево'),
('DME', 'Москва', 'Домодедово'),
('AER', 'Сочи', 'Сочи (Адлер)'),
('LED', 'Санкт-Петербург', 'Пулково'),
('IST', 'Стамбул', 'Стамбул (IST)'),
('KZN', 'Казань', 'Казань'),
('EGO', 'Белгород', 'Белгород'),
('KUF', 'Самара', 'Курумоч (Самара)'),
('GOJ', 'Нижний Новгород', 'Стригино (Нижний Новгород)'),
('MRV', 'Минеральные Воды', 'Минеральные Воды')
ON DUPLICATE KEY UPDATE city = VALUES(city), name = VALUES(name);

INSERT INTO airlines (name, code, aircraft) VALUES
('Победа', 'DP', 'Boeing 737-800'),
('Аэрофлот', 'SU', 'Airbus A320neo'),
('ЮВТ Аэро', 'UT', 'Boeing 737-500'),
('S7 Airlines', 'S7', 'Airbus A320'),
('Nordwind', 'N4', 'Boeing 777-300ER'),
('Turkish Airlines', 'TK', 'Airbus A321'),
('Azimut', 'A4', 'Sukhoi Superjet 100'),
('Red Wings', 'WZ', 'Sukhoi Superjet 100')
ON DUPLICATE KEY UPDATE name = VALUES(name), aircraft = VALUES(aircraft);

-- Рейсы: даты генерируются от CURDATE() на 14 дней вперёд,
-- цены с лёгким разбросом ±10%, чтобы выдача выглядела живой.
INSERT INTO flights
    (flight_number, airline_id, from_airport_id, to_airport_id, departure_date, departure_time, arrival_time, duration_minutes, transfers, price, seats_total, seats_left, cabin, baggage)
SELECT v.flight_number, a.id, fa.id, ta.id,
       DATE_ADD(CURDATE(), INTERVAL n.d DAY),
       v.dep, v.arr, v.dur, 0,
       ROUND(v.price * (0.9 + 0.2 * RAND()), 0),
       180,
       GREATEST(1, v.seats + FLOOR(-2 + 4 * RAND())),
       'Эконом', v.bag
FROM (
    SELECT 'DP 6502' flight_number, 'DP' al, 'UFA' f, 'VKO' t, '07:20' dep, '09:25' arr, 125 dur, 4290.00 price, 4 seats, 'Ручная кладь 10 кг' baggage
    UNION ALL SELECT 'SU 1234', 'SU', 'UFA', 'SVO', '10:35', '12:45', 130, 7840.00, 7, 'Багаж 23 кг'
    UNION ALL SELECT 'UT 364', 'UT', 'UFA', 'VKO', '16:50', '19:05', 135, 5120.00, 2, 'Ручная кладь 10 кг'
    UNION ALL SELECT 'S7 3510', 'S7', 'UFA', 'DME', '12:15', '14:35', 140, 6750.00, 12, 'Багаж 23 кг'
    UNION ALL SELECT 'UT 575', 'UT', 'UFA', 'AER', '10:10', '12:55', 165, 6190.00, 9, 'Ручная кладь 10 кг'
    UNION ALL SELECT 'N4 211', 'N4', 'UFA', 'AER', '14:40', '17:25', 165, 7430.00, 18, 'Багаж 23 кг'
    UNION ALL SELECT 'SU 6408', 'SU', 'UFA', 'LED', '09:05', '11:30', 145, 5490.00, 5, 'Багаж 23 кг'
    UNION ALL SELECT 'A4 702', 'A4', 'UFA', 'LED', '18:30', '21:00', 150, 4980.00, 14, 'Ручная кладь 10 кг'
    UNION ALL SELECT 'TK 428', 'TK', 'UFA', 'IST', '06:40', '10:20', 220, 18700.00, 21, 'Багаж 23 кг'
    UNION ALL SELECT 'SU 1683', 'SU', 'UFA', 'IST', '15:55', '19:35', 220, 22150.00, 11, 'Багаж 23 кг'
    UNION ALL SELECT 'WZ 336', 'WZ', 'VKO', 'UFA', '20:10', '00:15', 185, 4650.00, 8, 'Ручная кладь 10 кг'
    UNION ALL SELECT 'UT 359', 'UT', 'VKO', 'UFA', '11:40', '15:45', 185, 5320.00, 16, 'Ручная кладь 10 кг'
    UNION ALL SELECT 'UT 576', 'UT', 'AER', 'UFA', '13:30', '17:20', 170, 6190.00, 13, 'Ручная кладь 10 кг'
    UNION ALL SELECT 'SU 6409', 'SU', 'LED', 'UFA', '12:40', '17:10', 150, 5490.00, 6, 'Багаж 23 кг'
) v
JOIN airlines a ON a.code = v.al
JOIN airports fa ON fa.code = v.f
JOIN airports ta ON ta.code = v.t
JOIN (SELECT 0 d UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
      UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
      UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13) n
WHERE NOT EXISTS (
    SELECT 1 FROM flights fl
    WHERE fl.flight_number = v.flight_number AND fl.departure_date = DATE_ADD(CURDATE(), INTERVAL n.d DAY)
);

-- демо-пользователь (пароль: demo123)
INSERT INTO users (email, password_hash, first_name, last_name, phone)
VALUES ('demo@example.com', '$2y$10$0WfJecpQ0lRnqzYQWwxku.2pByVIbv9FFhEihycD0rjvCfjZ9sS7q', 'Демо', 'Демов', '+7 900 000-00-00')
ON DUPLICATE KEY UPDATE email = VALUES(email);
