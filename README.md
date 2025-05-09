# TileExpert Price & Orders API

## Описание

REST/SOAP API-сервис, предоставляющий:
- Цены на плитку с сайта tile.expert
- Группировку и пагинацию заказов
- SOAP-интерфейс для создания заказов
- Поиск по заказам через Manticore

## 🐳 Сборка и запуск

### Требования

- Docker
- docker-compose
- Make (опционально)

### Запуск

```bash
cp .env.example .env
docker-compose up --build

