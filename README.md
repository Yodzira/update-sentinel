# Update Sentinel

**[EN]** Every plugin/theme update gets an instant health check: does the site answer, how fast, did anything break? Verdict in the admin, 60-day journal, email the moment an update takes the site down.

**[RU]** Каждое обновление плагина или темы получает мгновенную проверку: отвечает ли сайт, как быстро, не сломалось ли что-то. Вердикт в админке, журнал за 60 дней, письмо в момент, когда обновление уложило сайт.

🔗 [**Скачать бесплатно / Download free**](https://github.com/Yodzira/update-sentinel/releases/latest/download/update-sentinel.zip)

## Как работает

1. Плагин хранит снимок версий «до»
2. Как только обновление завершается — дифф: что именно изменилось
3. Smoke-проверка сайта: HTTP 200 + время ответа
4. Вердикт: 🟢 healthy / 🟡 slow / 🔴 broken — в админке и в журнале
5. 🔴 broken → письмо с перечнем обновлённого и подсказкой отката

## Принципы

- Smoke — один запрос к себе после админ-действия, ноль нагрузки на фронте
- Работает поверх любых обновлений, включая автоматические
- TTFB-базовая линия самообучается (порог «медленно» = baseline × 2)
- Чистый uninstall: журнал, опции и крон стираются

## Установка / Install

1. Скачайте [`update-sentinel.zip`](https://github.com/Yodzira/update-sentinel/releases/latest/download/update-sentinel.zip)
2. WP-админка → **Плагины → Добавить новый → Загрузить плагин** → zip → Активировать
3. Обновляйте как обычно — вердикт появится в меню **Update Sentinel**

## Требования / Requirements

- WordPress 6.0+ (протестировано до 7.1), PHP 7.4+

## Качество / Quality

- PHPUnit (ядро): 4 теста, 13 assertions ✅ (дифф версий, вердикты smoke, пороги slow)
- Интеграция на живом WP 7.1: 11/11 (снимок, дифф, smoke живого сайта, журнал) ✅
- Официальный Plugin Checker: 0 errors (release build) ✅
- Uninstall: таблица/опции/крон стёрты ✅

## Лицензия / License

GPL-2.0-or-later (совместимо с WordPress).

💰 **[Купить Pro / Buy Pro — 2 990 ₽/год](https://yodsira.duckdns.org/buy/update-sentinel)** — лицензия на 1 сайт, 12 месяцев обновлений.
