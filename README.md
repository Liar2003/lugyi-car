# Lugyi-Car Content API

A Laravel-powered backend for multimedia content delivery, supporting category/tag/cast filtering, VIP access, device management, and view statistics. Designed for integration with modern React frontends.

## Features

-   **Content Management**: CRUD and listing for multimedia content (videos, articles, etc.)
-   **Category & Tag Filtering**: Organize and retrieve content by category, tag, or cast.
-   **VIP Access Control**: Restrict premium content to VIP users; show ads to non-VIP users.
-   **Device Tracking**: Identify and manage user devices for personalized access.
-   **View Statistics**: Track and report content views and user engagement.
-   **Suggestions & Recommendations**: Provide content suggestions for users.
-   **RESTful API**: All endpoints return JSON, suitable for frontend consumption.

## Tech Stack

-   **Backend**: Laravel 12+
-   **Database**: MySQL/PostgreSQL (via Eloquent ORM)
-   **Frontend**: (Recommended) React 18+ (not included)
-   **Build Tools**: Vite, Composer, NPM

## API Endpoints

-   `GET /api/contents/search` — Search content by keyword
-   `GET /api/contents/home` — Get home page content (by category, VIP, live/sport, etc.)
-   `GET /api/contents/category/{category}` — List content by category
-   `GET /api/contents/tag/{tag}` — List content by tag
-   `GET /api/contents/cast/{cast}` — List content by cast
-   `GET /api/contents/{id}` — Get content details (with related content and view count)
-   `GET /api/contents/views/{id}` — Get view statistics for content
-   `GET /api/contents/normal` — List non-VIP content
-   `GET /api/contents/vip` — List VIP content (VIP only)
-   `GET /api/contents/upgrade-info` — Get upgrade information for non-VIP users

## Setup

1. **Clone the repository**
    ```sh
    git clone https://github.com/Liar2003/lugyi-car.git
    cd lugyi-car
    ```
2. **Install dependencies**
    ```sh
    composer install
    npm install
    ```
3. **Configure environment**

    - Copy `.env.example` to `.env` and set your database and app keys.

4. **Run migrations and seeders**

    ```sh
    php artisan migrate --seed
    ```

5. **Build frontend assets**

    ```sh
    npm run build
    ```

6. **Start the server**
    ```sh
    php artisan serve
    ```

## Testing

-   **Backend**: Run PHPUnit tests
    ```sh
    php artisan test
    ```
-   **Frontend**: (If present) Run Jest/React Testing Library tests

## Folder Structure

-   `app/Http/Controllers/Api/ContentController.php` — Main API logic
-   `app/Models/` — Eloquent models (Content, Category, Device, etc.)
-   `routes/api.php` — API route definitions
-   `database/migrations/` — Database schema
-   `database/seeders/` — Sample data
-   `resources/views/` — Blade templates (if used)
-   `public/` — Static assets

## Contributing

Pull requests and issues are welcome! Please follow PSR-12 coding standards and write tests for new features.

## License

MIT

---

**Contact:** support@example.com
