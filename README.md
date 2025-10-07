# Task Management API - Laravel Backend

A robust Laravel 12 API backend for a task management application, built with modern Laravel best practices and following the repository pattern.

## 🚀 Features

- **User Authentication** - Laravel Fortify with Sanctum API tokens
- **Task Management** - Full CRUD operations with date-based organization
- **Repository Pattern** - Clean architecture with interface-based data access
- **API Resources** - Consistent JSON responses with proper formatting
- **Authorization** - Policy-based access control for task ownership
- **Soft Deletes** - Safe task deletion with recovery capabilities
- **Task Reordering** - Drag-and-drop task ordering support
- **Search & Filtering** - Advanced task filtering by status, priority, and search terms
- **Pagination** - Efficient data loading for large task lists
- **Comprehensive Testing** - Pest PHP test suite with feature and unit tests

## 🛠 Tech Stack

- **Laravel 12** - Latest Laravel framework
- **PHP 8.2+** - Modern PHP features
- **Laravel Fortify** - Authentication scaffolding
- **Laravel Sanctum** - API token authentication
- **Livewire** - Dynamic frontend components
- **SQLite** - Lightweight database (development)
- **Pest PHP** - Modern testing framework
- **Laravel Pint** - Code style enforcement

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM (for frontend assets)
- SQLite (or MySQL/PostgreSQL for production)

## 🚀 Quick Start

### 1. Clone and Install Dependencies

```bash
cd backend-laravel12
composer install
npm install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

```bash
# Create SQLite database
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed with test data
php artisan db:seed
```

### 4. Start Development Server

```bash
# Using Laravel Sail (recommended)
./vendor/bin/sail up

# Or using PHP built-in server
php artisan serve
```

The API will be available at `http://localhost:8000`

## 🗄 Database Schema

### Users Table
- `id` - Primary key
- `name` - User's full name
- `email` - Unique email address
- `password` - Hashed password
- `email_verified_at` - Email verification timestamp
- `two_factor_secret` - 2FA secret (optional)
- `two_factor_recovery_codes` - 2FA recovery codes (optional)
- `two_factor_confirmed_at` - 2FA confirmation timestamp (optional)

### Tasks Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `title` - Task title/description
- `status` - Task status (pending, completed)
- `priority` - Task priority (high, medium, low)
- `date` - Task date
- `order` - Display order for drag-and-drop
- `deleted_at` - Soft delete timestamp

## 🔐 Authentication

The API uses Laravel Sanctum for token-based authentication:

### Login
```bash
POST /api/login
Content-Type: application/json

{
    "email": "matt@goteam.com",
    "password": "password"
}
```

### Response
```json
{
    "user": {
        "id": 1,
        "name": "Matt",
        "email": "matt@goteam.com"
    },
    "token": "1|abc123..."
}
```

### Using the Token
Include the token in the Authorization header:
```
Authorization: Bearer 1|abc123...
```

## 📡 API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `POST /api/register` - User registration
- `POST /api/forgot-password` - Password reset request
- `POST /api/reset-password` - Password reset

### Tasks
- `GET /api/tasks` - List user's tasks (with filters)
- `POST /api/tasks` - Create new task
- `GET /api/tasks/{id}` - Get specific task
- `PUT /api/tasks/{id}` - Update task
- `DELETE /api/tasks/{id}` - Delete task
- `PUT /api/tasks/reorder` - Reorder tasks

### Query Parameters for Task Listing
- `date` - Filter by specific date (YYYY-MM-DD)
- `status` - Filter by status (pending, completed)
- `priority` - Filter by priority (high, medium, low)
- `search` - Search in task titles
- `sort` - Sort by (priority, title, order)
- `paginate` - Enable pagination (true/false)
- `per_page` - Items per page (default: 10)
- `page` - Page number (default: 1)

## 🧪 Testing

Run the test suite using Pest PHP:

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/TaskApiTest.php

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- **Authentication Tests** - Login, logout, registration, password reset
- **Task API Tests** - CRUD operations, authorization, validation
- **Repository Tests** - Data access layer testing
- **Policy Tests** - Authorization logic testing

## 🎨 Code Style

The project follows Laravel coding standards enforced by Laravel Pint:

```bash
# Check code style
composer format:test

# Fix code style issues
composer format
```

## 🏗 Architecture

### Repository Pattern
The application follows the repository pattern for clean data access:

```php
// Interface
interface TaskRepositoryInterface
{
    public function getAllByUser(int $userId, ...): Collection;
    public function create(array $data): Task;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function reorder(array $tasks): bool;
}

// Implementation
class TaskRepository implements TaskRepositoryInterface
{
    // Eloquent implementation
}
```

### Service Provider Registration
```php
// RepositoryServiceProvider.php
public function register(): void
{
    $this->app->bind(
        TaskRepositoryInterface::class,
        TaskRepository::class
    );
}
```

## 🔒 Security Features

- **CSRF Protection** - Built-in Laravel CSRF tokens
- **SQL Injection Prevention** - Eloquent ORM with parameterized queries
- **XSS Protection** - Input sanitization and output escaping
- **Authorization Policies** - Fine-grained access control
- **Rate Limiting** - API request throttling
- **Password Hashing** - Secure password storage with bcrypt

## 📊 Seeded Data

The application comes with pre-seeded test data:

### Test Users
- **Matt** - `matt@goteam.com` / `password`
- **Test User** - `testme@goteam.inc` / `password`

### Sample Tasks
- 20 sample tasks for testing pagination and filtering
- Various statuses (pending, completed)
- Different priorities (high, medium, low)
- All tasks assigned to Matt user

## 🚀 Deployment

### Production Checklist
- [ ] Update `.env` with production database credentials
- [ ] Set `APP_ENV=production`
- [ ] Configure proper mail settings
- [ ] Set up SSL certificates
- [ ] Configure web server (Nginx/Apache)
- [ ] Set up queue workers for background jobs
- [ ] Configure file storage (S3, etc.)

### Environment Variables
```env
APP_NAME="Task Management API"
APP_ENV=production
APP_KEY=base64:your-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=your_username
DB_PASSWORD=your_password

SANCTUM_STATEFUL_DOMAINS=your-frontend-domain.com
```

## 📚 Documentation

Additional documentation is available in the `docs/` directory:

- `code-style.md` - Coding standards and conventions
- `project-details.md` - Project requirements and specifications
- `repository-pattern.md` - Repository pattern implementation guide

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the documentation in the `docs/` directory
- Review the test files for usage examples

---

**Built with ❤️ using Laravel 12**
