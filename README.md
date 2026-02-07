# BlackOutNotify Bot

PHP bot for checking device status in Tuya and sending Telegram notifications when the `online` state changes.

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```
2. Create `.env` from the example:
   ```bash
   cp .env.example .env
   ```
3. Fill in the values in `.env`:
   - `TUYA_CLIENT_ID`
   - `TUYA_SECRET`
   - `TUYA_DEVICE_ID`
   - `TUYA_ACCESS_TOKEN`
   - `TELEGRAM_CHAT_ID`
   - `TELEGRAM_BOT_TOKEN`

Default `TUYA_API_URL`: `https://openapi.tuyaeu.com`.

## Run

```bash
php index.php
```