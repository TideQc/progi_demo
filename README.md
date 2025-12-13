# Bid Calculation Tool

This repository contains a backend (PHP using Symfony components) and a frontend (Vue 3) to calculate auction bid totals with **all pricing and fee configurations stored in the database**.

## Quick overview:
- **Backend:** PHP 8.2, Symfony HTTP components, Monolog JSON logging, PDO MySQL. Exposes POST /api/calculate
- **Frontend:** Vue 3 (Vite), responsive UI to enter price and type and see fees
- **Database:** MariaDB with dynamic fee configurations (not hardcoded)
- **Docker:** docker-compose to run all services

## Architecture Changes
**Key improvement:** All fee structures (basic buyer fee %, seller special fee %, association fees, storage fees) are now stored in the database, making the system fully configurable without code changes.

### Database Schema
- `vehicle_types`: Stores vehicle type names (common, luxury)
- `fee_configurations`: Stores all fee rules with percentage/fixed amounts, min/max bounds, and price ranges

## Run with Docker Compose

```powershell
# from repo root
docker-compose up --build
```

On first run, the database will be initialized automatically with the schema and seed data.

After startup:
- **Frontend:** http://localhost:3000
- **Backend API:** http://localhost:8000/api/calculate
- **Database:** localhost:3306 (user: `user` / pass: `pass`, db: `bidcalc`)

## API Examples

### Calculate bid for common vehicle ($1000):
```powershell
$body = @{"price"=1000;"type"="common"} | ConvertTo-Json
Invoke-WebRequest -Uri "http://localhost:8000/api/calculate" `
  -Method POST `
  -Headers @{"Content-Type"="application/json"} `
  -Body $body
```

**Response:**
```json
{
  "price": 1000,
  "type": "common",
  "fees": {
    "basic_buyer_fee": 50,
    "seller_special_fee": 20,
    "association_fee": 10,
    "storage_fee": 100
  },
  "total": 1180
}
```

## Local Development (without Docker)

To install PHP dependencies locally (if not using Docker):
```powershell
cd backend
composer install
```

To run backend unit tests (requires composer dev dependencies):
```powershell
cd backend
./vendor/bin/phpunit
```

To install frontend dependencies:
```powershell
cd frontend
npm install
npm run build
```

## Database Configuration

Update `docker-compose.yml` environment variables to change database credentials:
```yaml
DB_HOST: db
DB_USER: user
DB_PASS: pass
DB_NAME: bidcalc
```

To manually run migrations/schema init:
```powershell
docker-compose exec db mysql -u user -ppass bidcalc < backend/sql/schema.sql
```

## Modifying Fee Configurations

All fees are configurable via the database. To update fees, connect to the database and modify the `fee_configurations` table:

```sql
-- Example: Change common vehicle basic buyer fee max from 50 to 75
UPDATE fee_configurations 
SET max_amount = 75 
WHERE vehicle_type_id = (SELECT id FROM vehicle_types WHERE name = 'common')
  AND fee_type = 'basic_buyer_fee';
```

## Backend Logging

The backend logs all actions and errors in JSON format to `backend/var/log/app.log`:
```json
{"message":"calculation","level":200,...,"context":{"request":{"price":1000,"type":"common"},"result":{...}}}
```

## CORS and API Base

- The backend exposes CORS headers for browser clients. Modify `backend/public/index.php` to restrict origins if needed.
- The frontend uses `VITE_API_BASE` environment variable (default: `http://localhost:8000` in docker-compose).

## CI/CD

A GitHub Actions workflow at `.github/workflows/ci.yml` runs:
- Backend PHPUnit tests
- Frontend Vite build

## Docker Notes

- When running locally, the frontend calls the backend at `http://localhost:8000` by default.
- Inside containers, the frontend uses `http://backend:8000` (set via `VITE_API_BASE` in docker-compose).
- Database initializes automatically on first `docker-compose up` via the `sql/` volume mount.

