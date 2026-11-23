# Cargo_Trace — Maritime Shipment & Port Tracking Platform

**Cargo_Trace** is a Laravel-based maritime logistics and shipment visibility web application built to track shipment lifecycles, monitor SLA compliance across Groups A/B/C, visualize port and checkpoint routes on an interactive map, display external weather feeds, manage shipment exceptions and notifications, and expose REST API endpoints.

## Key Features

- **Shipment Lifecycle Management**: Tracks shipment states (`CREATED`, `BOOKED`, `PICKED_UP`, `IN_TRANSIT`, `DELIVERED`) with chronological tracking events (`NORMAL EVENT` vs. `EXCEPTION EVENT`), role-based access control (`admin`, `operator`, `customer`), and audit logs.
- **SLA Monitoring & Exception Handling**: Evaluates delay thresholds by SLA group (**Group A: 12h**, **Group B: 18h**, **Group C: 24h**), flags delayed shipments, and creates or updates shipment exceptions and notifications upon SLA breaches.
- **Interactive Maritime Route Map**: Plots origin ports, tracking checkpoints, and destination ports with route polylines using **Leaflet.js** and OpenStreetMap tiles.
- **External Weather Feed**: Fetches destination/port weather conditions from **Open-Meteo** through Laravel's `Http` client with caching, request timeout handling, and safe fallback messages when offline or unreachable.
- **Bearer-Token REST API**: Provides JSON endpoints for authentication (`/api/register`, `/api/login`, `/api/logout`, `/api/user`), shipments (`/api/shipments`), tracking events (`/api/shipments/{id}/tracking`), carriers (`/api/carriers`), locations (`/api/locations`), and exceptions (`/api/exceptions/{id}`).

## Architecture

- **Backend**: Laravel (MVC + Service Layer: `ShipmentLifecycleService`, `ShipmentSlaService`, `MaritimeWeatherService`)
- **Database & Testing**: Relational migrations, seeders, and PHPUnit feature tests (`RefreshDatabase`)
- **Frontend**: Blade templates, Tailwind CSS utility classes, and Leaflet.js

## Local Setup

1. Install dependencies:
   `composer install`
2. Copy the environment file and generate an application key:
   `cp .env.example .env`
   `php artisan key:generate`
3. Run migrations and seed sample data:
   `php artisan migrate --seed`
4. Start the local server:
   `php artisan serve`

## Running Tests

Run the automated test suite with:

`php artisan test`

## REST API Endpoints

- `POST /api/register` — Register a user account and receive a Bearer token
- `POST /api/login` — Authenticate with email and password to receive a Bearer token
- `POST /api/logout` — Revoke the current Bearer token (`Authorization: Bearer <token>`)
- `GET /api/user` — Fetch the authenticated user's profile
- `GET / POST /api/shipments` — List (paginated, role-filtered, searchable) or create shipments
- `GET / PUT / PATCH / DELETE /api/shipments/{id}` — Retrieve, update, or delete a shipment
- `GET / POST /api/shipments/{id}/tracking` — List or record shipment tracking events
- `GET / POST /api/carriers` & `GET / POST /api/locations` — List or create carriers and port locations
- `GET / POST /api/shipments/{id}/exceptions` & `PATCH /api/exceptions/{id}` — List, create, or update shipment exceptions