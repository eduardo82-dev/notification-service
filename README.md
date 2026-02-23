# 🔔 Notification Service

Единый сервис уведомлений на Laravel 11 с архитектурой DDD, OUTBOX Pattern и поддержкой множества каналов.

---

## 🏗 Архитектура

```
src/app/
├── Domain/                          ← Ядро бизнес-логики (независимо от фреймворка)
│   ├── Notification/
│   │   ├── Entities/                ← Notification (агрегатный корень)
│   │   ├── ValueObjects/            ← NotificationId, Status, Priority, Type, Channel
│   │   ├── Events/                  ← Domain events: NotificationCreated, StatusChanged
│   │   ├── Repositories/            ← Interfaces: репозитории
│   │   ├── Services/                ← ChannelSenderInterface
│   │   └── Exceptions/              ← Domain exceptions
│   └── Outbox/
│       ├── Entities/                ← OutboxMessage
│       └── Repositories/            ← OutboxRepositoryInterface
│
├── Application/                     ← Use-cases (Commands / Handlers / DTOs)
│   ├── Commands/                    ← CreateNotificationCommand
│   ├── Handlers/                    ← CreateNotificationHandler
│   └── DTOs/                        ← NotificationDTO
│
├── Infrastructure/                  ← Реализации (Eloquent, RabbitMQ, каналы)
│   ├── Persistence/
│   │   ├── Models/                  ← Eloquent Models
│   │   └── Repositories/            ← Eloquent Repositories
│   ├── Messaging/
│   │   ├── ChannelDispatcher.php    ← Диспетчер каналов
│   │   └── Jobs/                    ← ProcessNotificationJob
│   ├── Channel/
│   │   ├── Email/                   ← EmailChannelSender
│   │   ├── Sms/                     ← SmsChannelSender
│   │   ├── Telegram/                ← TelegramChannelSender
│   │   └── Push/                    ← PushChannelSender
│   └── Outbox/
│       └── OutboxProcessor.php      ← OUTBOX relay
│
└── Http/                            ← Controllers, Requests, Resources
```

### OUTBOX Pattern Flow

```
HTTP Request
    │
    ▼
CreateNotificationHandler
    │
    ├─── BEGIN TRANSACTION
    │         ├── INSERT notifications
    │         └── INSERT outbox_messages (ATOMIC)
    └─── COMMIT TRANSACTION
    
    ↓ (every 1 minute via Scheduler)
    
OutboxProcessor (artisan outbox:process)
    │
    ├── SELECT unprocessed FROM outbox_messages
    ├── PUBLISH to RabbitMQ queue
    └── UPDATE outbox_messages SET processed = true
    
    ↓
    
app01-worker (queue:work rabbitmq)
    │
    └── ProcessNotificationJob
            │
            └── ChannelDispatcher
                    ├── EmailChannelSender
                    ├── SmsChannelSender
                    ├── TelegramChannelSender
                    └── PushChannelSender
```

---

## 🚀 Быстрый старт

### Требования
- Docker 29+
- make (опционально)

### 1. Клонировать и запустить

```bash
# Скопировать env
cp src/.env.example src/.env

# Полная инициализация одной командой
make setup
```

Или вручную:

```bash
docker compose up -d
docker compose exec app01-php composer install
docker compose exec app01-php php artisan key:generate
docker compose exec app01-php php artisan migrate --seed
docker compose exec app01-php php artisan l5-swagger:generate
```

### 2. Проверить работу

| URL | Описание                                        |
|-----|-------------------------------------------------|
| http://localhost:8081/api/documentation | Swagger UI                                      |
| http://localhost:8081/api/v1/health | Health check                                    |
| http://localhost:15672 | RabbitMQ Management (rabbit_user / rabbit_pass) |
| http://localhost:8025/ | Mailplit (local Email server)                   |

---

## 📡 API Endpoints

### POST `/api/v1/notifications`
Создать уведомление

```json
{
    "user_id": 15,
    "type": "order_paid",
    "channels": ["email", "sms"],
    "priority": "high",
    "payload": {
        "order_id": 1001,
        "email": "user@example.com",
        "phone": "+79001234567"
    }
}
```

**Ответ 201:**
```json
{
    "data": {
        "id": 1,
        "uuid": "550e8400-e29b-41d4-a716-446655440000",
        "user_id": 15,
        "type": "order_paid",
        "channels": ["email", "sms"],
        "payload": { "order_id": 1001 },
        "priority": "high",
        "status": "pending",
        "created_at": "2024-01-01T00:00:00+00:00",
        "updated_at": null
    }
}
```

### GET `/api/v1/notifications/{uuid}`
Получить уведомление по UUID

### GET `/api/v1/users/{userId}/notifications?limit=20&offset=0`
Список уведомлений пользователя

### GET `/api/v1/health`
Health check

---

## 🔌 Поддерживаемые каналы

| Канал | Ключи в payload | Реализация |
|-------|-----------------|------------|
| `email` | `email` или `to`, `subject` (опц.) | Laravel Mail |
| `sms` | `phone` | Stub (готов к интеграции Twilio/Vonage) |
| `telegram` | `telegram_chat_id` | HTTP API Telegram |
| `push` | `device_token` | Stub (готов к FCM/APNs) |
| `webhook` | — | Stub |

### Добавление нового канала

1. Создать класс в `app/Infrastructure/Channel/{ChannelName}/`
2. Реализовать `ChannelSenderInterface`
3. Зарегистрировать в `NotificationServiceProvider::register()`
4. Добавить в `NotificationChannel::SUPPORTED`

---

## 🐛 Xdebug

Xdebug 3 настроен на порт **9003** (IDE Key: `PHPSTORM`).

В PhpStorm: Run → Edit Configurations → PHP Remote Debug:
- IDE Key: `PHPSTORM`
- Host: `localhost`
- Port: `9003`

Управление режимом через переменную окружения:
```bash
XDEBUG_MODE=off docker compose up  # отключить
XDEBUG_MODE=debug docker compose up # включить
```

---

## 🧪 Тесты

```bash
make test
# или
docker compose exec app01-php php artisan test
docker compose exec app01-php ./vendor/bin/pest --group=unit
```

---

## 🐰 RabbitMQ

- Management UI: http://localhost:15672
- Credentials: `rabbit_user` / `rabbit_pass`
- Vhost: `notifications`
- Exchange: `notifications.direct` (direct)
- Queue: `notifications`
- Dead Letter Queue: `notifications.failed`

---

## 📦 Контейнеры

| Контейнер | Назначение | Порт |
|-----------|------------|------|
| `app01-nginx` | Web server | 8081→80 |
| `app01-php` | PHP-FPM 8.3 | — |
| `app01-mysql` | MySQL 8.0 | 33061→3306 |
| `app01-redis` | Redis 7.2 | 63791→6379 |
| `app01-rabbitmq` | RabbitMQ 3.13 | 56721→5672, 15672 |
| `app01-worker` | Queue worker | — |
| `app01-scheduler` | Cron (OUTBOX relay) | — |
