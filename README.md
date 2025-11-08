# SmartEnergyMeter

SmartEnergyMeter is a lightweight PHP/MySQL backend and HTML dashboard for monitoring an ESP32 based energy metering device built with an ACS7012 and ZMPT101B sensor. The firmware running on the device already delivers calibrated voltage, current, power and cumulative energy readings — this project focuses on receiving those measurements, persisting them and presenting meaningful summaries.

## Features

- Secure ingestion endpoint with per-device API keys.
- Normalised readings table storing live samples with cumulative kWh totals.
- Automatic daily and monthly aggregation tables capturing consumption (kWh) and cost in the same transaction as each reading.
- Tariff aware cost tracking (energy rate + optional fixed monthly charge).
- Modern responsive dashboard with live tiles, charts and cost tables.

## Database overview

The `smart_energy.sql` dump provisions the schema and seed data. Key tables:

| Table | Purpose |
|-------|---------|
| `devices` | Registered metering devices and API keys. |
| `readings` | Raw time-series samples (voltage, current, power, kWh delta and running total). |
| `daily_usage` | Aggregated per-day consumption and cost derived from readings. |
| `monthly_usage` | Aggregated per-month consumption, energy cost and fixed tariff charge. |
| `tariffs` / `device_tariff` | Tariff catalogue and device-to-tariff mapping. |

Daily and monthly tables are updated atomically by the ingestion endpoint whenever a positive energy delta is received. This keeps queries trivial and avoids re-aggregating large histories for dashboards.

## Running locally

1. Import `smart_energy.sql` into a MariaDB/MySQL instance.
2. Adjust the connection credentials in `db.php` if required.
3. Serve the PHP endpoints (for example with Apache, Nginx+PHP-FPM or the built-in PHP server).
4. Point the ESP32 firmware at `ingest.php` with the configured `device_id` and `api_key`.
5. Open `index.html` in a browser to see live measurements and cost summaries.

## API endpoints

| Endpoint | Description |
|----------|-------------|
| `ingest.php` | Receives JSON payloads from the device, validates credentials, stores the reading, and updates daily/monthly aggregations within a transaction. |
| `latest.php` | Returns the most recent reading for the specified `device_id`. |
| `history.php` | Returns the last N minutes of readings (voltage, current, power, delta kWh and total kWh). |
| `daily.php` | Returns daily consumption and cost rows sourced from `daily_usage`. |
| `monthly.php` | Returns monthly consumption and cost (variable + fixed) sourced from `monthly_usage`. |

All endpoints require a `device_id` query parameter except `ingest.php`, which accepts JSON body fields documented in `SmartEnergyMeter.ino`.

---

The firmware responsible for sensor measurements is intentionally untouched; only the backend data model and analytics were updated to provide professional-grade reporting.
