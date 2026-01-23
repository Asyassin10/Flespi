# Laravel Flespi GPS Fleet Management System

A production-ready Laravel 11 application that integrates with the Flespi telematics platform for comprehensive GPS fleet management.

## Features

- **Device Management**: List, view, and manage GPS tracking devices
- **Real-time Tracking**: Live device locations displayed on interactive maps
- **Trip Detection**: Automatic trip calculation with detailed analytics (distance, duration, route)
- **Driver Management**: Assign drivers to devices and track driver information in trips
- **Geofence Management**: Create circular and polygon geofences with entry/exit detection
- **Trip Reports**: Generate comprehensive trip reports with advanced filtering
- **Webhook Integration**: Real-time updates from Flespi via webhooks
- **Background Processing**: Queue-based synchronization for optimal performance

## Tech Stack

- **Framework**: Laravel 11
- **Database**: MySQL/SQLite
- **Real-time UI**: Livewire 3
- **Styling**: Tailwind CSS
- **Maps**: Leaflet.js
- **API**: Flespi REST API

## Architecture

### Service Layer Pattern
- `FlespiApiService`: Base HTTP client wrapper with error handling and caching
- `FlespiDeviceService`: Device operations (list, telemetry, messages)
- `FlespiTripService`: Trip/interval operations and calculators
- `FlespiGeofenceService`: Geofence CRUD and point-in-geofence detection
- `FlespiWebhookService`: Webhook processing and stream management

### Database Schema
- **devices**: Store Flespi device references with current location/status
- **drivers**: Driver information and contact details
- **driver_assignments**: Historical driver-device assignments
- **trips_cache**: Cached trip data for fast reporting
- **geofences**: Local geofence definitions with geometry

### Controllers
- `DashboardController`: Main dashboard with statistics
- `DeviceController`: Device CRUD operations
- `TripController`: Trip views and reporting
- `DriverController`: Driver management
- `GeofenceController`: Geofence CRUD
- `WebhookController`: Handle Flespi webhooks

## Installation

### Prerequisites

- PHP >= 8.2
- Composer
- Node.js & NPM
- MySQL/SQLite
- Flespi Account (Free trial available at https://flespi.io/)

### Step 1: Clone and Install Dependencies

```bash
git clone <repository-url>
cd Flespi

composer install
npm install
```

### Step 2: Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flespi_fleet
DB_USERNAME=root
DB_PASSWORD=

# Flespi Configuration
FLESPI_BASE_URL=https://flespi.io
FLESPI_TOKEN=your_flespi_token_here
FLESPI_TRIP_CALC_ID=

# Queue Configuration (for background jobs)
QUEUE_CONNECTION=database
```

### Step 3: Get Your Flespi Token

1. Go to https://flespi.io/
2. Sign up for a free account
3. Navigate to **Settings** → **Tokens**
4. Create a new token with full permissions
5. Copy the token and paste it in `.env` as `FLESPI_TOKEN`

### Step 4: Database Setup

```bash
php artisan migrate
```

### Step 5: Build Frontend Assets

```bash
npm run build
# or for development with hot reload
npm run dev
```

### Step 6: Start Queue Worker (Required for webhooks)

```bash
php artisan queue:work
```

### Step 7: Start Task Scheduler (Required for auto-sync)

The application includes automatic background synchronization that runs periodically:
- **Devices**: Synced every 2 minutes
- **Trips**: Synced every 5 minutes

To enable auto-sync, run the scheduler in a separate terminal:

```bash
php artisan schedule:work
```

This keeps your dashboard and data updated in real-time without manual sync buttons.

**For Production**: Add this cron entry to run the scheduler:
```bash
* * * * * cd /path/to/flespi && php artisan schedule:run >> /dev/null 2>&1
```

### Step 8: Run the Application

```bash
php artisan serve
```

Visit http://localhost:8000

## Artisan Commands

### Sync Devices from Flespi

```bash
php artisan flespi:sync-devices
```

Imports all devices from Flespi and updates local database with latest telemetry.

### Sync Trips from Flespi

```bash
php artisan flespi:sync-trips [--device=123] [--from=2024-01-01] [--to=2024-01-31]
```

Options:
- `--device`: Specific device ID (optional, syncs all if omitted)
- `--from`: Start date (YYYY-MM-DD)
- `--to`: End date (YYYY-MM-DD)
- `--days`: Sync last N days (default: 7)

Example:
```bash
# Sync last 30 days for all devices
php artisan flespi:sync-trips --days=30

# Sync specific date range for device 123
php artisan flespi:sync-trips --device=123 --from=2024-01-01 --to=2024-01-31
```

## API Endpoints

### Webhook Endpoint

```
POST /api/flespi/webhook
```

Configure this URL in Flespi streams to receive real-time updates for:
- Device location updates
- Trip completion events
- Geofence entry/exit events

## Troubleshooting

### Issue: "Flespi API token is not configured"

**Solution**:
1. Check `.env` file has `FLESPI_TOKEN=your_token`
2. Clear config cache: `php artisan config:clear`
3. Restart queue worker

### Issue: Devices not showing on map

**Solution**:
1. Check devices have recent messages: `php artisan flespi:sync-devices`
2. Verify device sent location data (needs GPS fix)
3. Check browser console for JavaScript errors

### Issue: Trips not appearing

**Solution**:
1. Ensure trip calculator is created (check setup wizard)
2. Devices must be assigned to calculator
3. Run manual sync: `php artisan flespi:sync-trips`
4. Check `FLESPI_TRIP_CALC_ID` in `.env`

### Issue: Webhooks not working

**Solution**:
1. Queue worker must be running: `php artisan queue:work`
2. Check webhook URL is publicly accessible (use ngrok for local dev)
3. Verify stream is created in Flespi and devices are assigned
4. Check Laravel logs: `storage/logs/laravel.log`

## Production Deployment

### Optimize Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### Supervisor Configuration

Create `/etc/supervisor/conf.d/flespi-worker.conf`:

```ini
[program:flespi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start flespi-worker:*
```

## License

This project is open-source software licensed under the MIT license.
