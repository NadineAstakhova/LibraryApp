# LibraryApp Backend

A RESTful API backend for a library management system built with Laravel 12 and PHP 8.3. The application follows a Domain-Driven Design (DDD) architecture pattern and uses JWT for authentication.

## Table of Contents

- [Background & Architecture Decision](#background--architecture-decision)
- [Technology Stack](#technology-stack)
- [Setup Instructions](#setup-instructions)
- [Database Structure](#database-structure)
- [API Documentation](#api-documentation)
- [API Endpoints](#api-endpoints)
- [Rate Limiting](#rate-limiting)
- [Testing](#testing)
- [Trade-offs & Design Decisions](#trade-offs--design-decisions)
- [Future Improvements](#future-improvements)

---

## Background & Architecture Decision

### Developer Experience

This project leverages my extensive experience with:
- **REST API Development** - Building scalable, well-documented APIs following REST principles
- **MySQL Database** - Designing efficient database schemas with proper indexing and relationships
- **Previous DDD Projects** - My last project utilized a DDD-like code structure, which informed the architecture decisions here

### Why DDD?

The **Domain-Driven Design (DDD)** architecture was implemented primarily due to **project requirements** that specified a DDD-like structure. While DDD might seem like overkill for a relatively simple library CRUD application, it was chosen because:

1. **Project Requirements** - The specification explicitly called for a DDD-like structure
2. **Familiarity** - Previous project experience with DDD made implementation straightforward
3. **Scalability** - The architecture allows for easy extension and modification
4. **Testability** - Clear boundaries between layers make unit testing straightforward
5. **Maintainability** - Separation of concerns makes the codebase easier to understand

> **Honest Assessment**: For a simple library application, a standard Laravel MVC structure would be sufficient and faster to develop. DDD adds complexity that may not be justified for small-scale projects. However, it demonstrates architectural knowledge and prepares the codebase for potential growth.

### Architecture Layers

```
src/app/Library/
├── Domain/              # Core business logic (Entities, Value Objects, Repository Interfaces)
├── Application/         # Use cases (Services, DTOs, Exceptions)
├── Infrastructure/      # External concerns (Eloquent Models, Repository Implementations, Mappers)
└── UserInterface/       # API layer (Controllers, Requests, Routes)
```

---

## Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.3 | Programming language |
| Laravel | 12.x | Web framework |
| MySQL | 8.0 | Database |
| JWT (tymon/jwt-auth) | 2.x | Authentication |
| Docker | - | Containerization |
| PHPUnit | 11.x | Testing |
| Swagger/OpenAPI | 3.0 | API Documentation |

---

## Setup Instructions

### Prerequisites

- Docker and Docker Compose
- Git

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd LibraryApp
   ```

2. **Build and start Docker containers**
   ```bash
   docker-compose up -d --build
   ```
   
   > **Note**: The Docker build process automatically:
   > - Installs all Composer dependencies
   > - Creates `.env` from `.env.example`
   > - Generates Laravel application key
   > - Generates JWT secret
   > - Generates Swagger/OpenAPI documentation

3. **Run database migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

4. **Seed the database (optional)**
   ```bash
   docker-compose exec app php artisan db:seed
   ```

### Accessing the Application

- **API Base URL**: `http://localhost:8000/api/v1`
- **Swagger Documentation**: `http://localhost:8000/api/documentation`

### Docker Services

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| app | library_app | 9000 (internal) | PHP-FPM application |
| nginx | library_nginx | 8000 | Web server |
| db | library_db | 3306 | MySQL database |

---

## Database Structure

### Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     users       │       │   book_rents    │       │     books       │
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │──┐    │ id (PK)         │    ┌──│ id (PK)         │
│ name            │  │    │ user_id (FK)    │────┘  │ title           │
│ email (unique)  │  └────│ book_id (FK)    │───────│ author          │
│ password        │       │ rented_at       │       │ isbn (unique)   │
│ role            │       │ due_date        │       │ genre           │
│ email_verified  │       │ returned_at     │       │ description     │
│ remember_token  │       │ status          │       │ total_copies    │
│ created_at      │       │ reading_progress│       │ available_copies│
│ updated_at      │       │ extension_count │       │ version         │
│ deleted_at      │       │ created_at      │       │ publication_year│
└─────────────────┘       │ updated_at      │       │ created_at      │
                          └─────────────────┘       │ updated_at      │
                                                    │ deleted_at      │
                                                    └─────────────────┘
```

### Tables

#### `users`
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| name | VARCHAR(255) | User's full name |
| email | VARCHAR(255) | Unique email address |
| password | VARCHAR(255) | Hashed password |
| role | ENUM('user', 'admin') | User role (default: 'user') |
| email_verified_at | TIMESTAMP | Email verification timestamp |
| remember_token | VARCHAR(100) | Remember me token |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### `books`
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| title | VARCHAR(255) | Book title |
| author | VARCHAR(255) | Author name |
| isbn | VARCHAR(255) | Unique ISBN |
| genre | VARCHAR(255) | Book genre |
| description | TEXT | Book description (nullable) |
| total_copies | INT | Total number of copies |
| available_copies | INT | Currently available copies |
| version | BIGINT | Optimistic locking version |
| publication_year | YEAR | Year of publication (nullable) |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |
| deleted_at | TIMESTAMP | Soft delete timestamp |

#### `book_rents`
| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| user_id | BIGINT | Foreign key to users |
| book_id | BIGINT | Foreign key to books |
| rented_at | TIMESTAMP | Rental start date |
| due_date | TIMESTAMP | Expected return date |
| returned_at | TIMESTAMP | Actual return date (nullable) |
| status | ENUM('active', 'returned', 'overdue') | Rental status |
| reading_progress | INT | Reading progress (0-100%) |
| extension_count | INT | Number of extensions |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

### Indexes

- `users`: email (unique)
- `books`: isbn (unique), title, author, genre, (available_copies, title)
- `book_rents`: (user_id, status), (book_id, status), due_date

---

## API Documentation

### Swagger/OpenAPI

Interactive API documentation is available via Swagger UI:

```
http://localhost:8000/api/documentation
```

The Swagger documentation provides:
- Complete endpoint descriptions
- Request/response schemas
- Authentication requirements
- Try-it-out functionality for testing endpoints

---

## API Endpoints

P.S. Find Postman collection in `LibraryApp.postman_collection.json`. Check if env vars are set correctly.

### Authentication

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/auth/register` | Register a new user | No |
| POST | `/api/v1/auth/login` | Login and get JWT token | No |
| POST | `/api/v1/auth/logout` | Logout (invalidate token) | Yes |
| POST | `/api/v1/auth/refresh` | Refresh JWT token | Yes |
| GET | `/api/v1/auth/me` | Get current user profile | Yes |
| PUT | `/api/v1/auth/password` | Update password | Yes |
| PUT | `/api/v1/auth/profile` | Update profile (name) | Yes |

### Books

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/books` | List/search books (paginated) | No |
| GET | `/api/v1/books/{id}` | Get book details | No |
| POST | `/api/v1/books` | Create a new book | Admin |
| PUT | `/api/v1/books/{id}` | Update a book | Admin |
| DELETE | `/api/v1/books/{id}` | Delete a book | Admin |

#### Book Search Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Search in title, author, ISBN |
| genre | string | Filter by genre |
| author | string | Filter by author |
| available | boolean | Filter by availability |
| page | integer | Page number |
| per_page | integer | Items per page (max: 100) |

### Book Rentals

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/rentals` | Rent a book | Yes |
| GET | `/api/v1/rentals/{id}` | Get rental details | Yes |
| POST | `/api/v1/rentals/{id}/extend` | Extend rental period | Yes |
| PATCH | `/api/v1/rentals/{id}/progress` | Update reading progress | Yes |
| POST | `/api/v1/rentals/{id}/return` | Return a book | Yes |

### Request/Response Examples

#### Register User
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600
  }
}
```

#### Rent a Book
```http
POST /api/v1/rentals
Authorization: Bearer <token>
Content-Type: application/json

{
  "book_id": 1,
  "rental_days": 14
}
```

Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "book_id": 1,
    "user_id": 1,
    "rented_at": "2026-01-19T12:00:00Z",
    "due_date": "2026-02-02T12:00:00Z",
    "status": "active",
    "reading_progress": 0
  }
}
```

---

## Rate Limiting

The API implements tiered rate limiting to prevent abuse:

| Tier | Limit | Applies To |
|------|-------|------------|
| API (General) | 60 requests/minute | Public endpoints |
| Auth | 5 requests/minute | Login/Register |
| Authenticated | 120 requests/minute | Authenticated users |
| Rentals | 10 requests/minute | Rental creation |
| Admin | 200 requests/minute | Admin operations |

---

## Testing

### Unit Tests

The project includes comprehensive unit tests for all application services:

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test suite
docker-compose exec app php artisan test --filter=BookServiceTest
docker-compose exec app php artisan test --filter=BookRentalServiceTest
docker-compose exec app php artisan test --filter=AuthServiceTest
```

### Test Coverage

| Service | Tests | Assertions |
|---------|-------|------------|
| BookService | 15 | 30+ |
| BookRentalService | 14 | 25+ |
| AuthService | 17 | 32+ |
| **Total** | **49** | **86+** |

### Test Categories

- **BookService**: CRUD operations, search, optimistic locking, active rentals validation
- **BookRentalService**: Rent, return, extend, reading progress, concurrency handling
- **AuthService**: Register, login, logout, token refresh, profile management, password update

### Integration Tests

> **Note**: Also would be cool to implement the Integration Test. They would cover:
> - Full API endpoint testing with database
> - Authentication flow testing
> - Rental workflow testing
> - Admin operations testing

---

## Trade-offs & Design Decisions

### 1. DDD Architecture

**Trade-off**: DDD adds complexity for a relatively simple CRUD application.

**Reasoning**: 
- Valued by project specifications
- Provides excellent separation of concerns
- Makes the codebase highly testable
- Prepares for future scalability

**For a simpler project**, a standard Laravel MVC structure would suffice.

### 2. JWT Authentication

**Trade-off**: JWT is stateless but requires careful token management.

**Reasoning**:
- Stateless authentication fits REST API paradigm
- No server-side session storage needed
- Easy to scale horizontally
- Token refresh mechanism implemented

**Alternative**: Laravel Sanctum would be simpler for SPA authentication.

### 3. Optimistic Locking

**Trade-off**: Adds version field and conflict handling complexity.

**Reasoning**:
- Prevents lost updates in concurrent scenarios
- Essential for book availability management
- Better user experience than pessimistic locking

### 4. Soft Deletes

**Trade-off**: Data is never truly deleted, increasing storage.

**Reasoning**:
- Maintains referential integrity for rentals
- Allows data recovery
- Audit trail preservation

### 5. Repository Pattern

**Trade-off**: Additional abstraction layer between services and database.

**Reasoning**:
- Decouples domain from infrastructure
- Enables easy testing with mocks
- Allows switching data sources without changing business logic

---

## Future Improvements

### High Priority

1. **Performance Optimizations**
    - Database query optimization
    - Eager loading improvements
    - Response compression
    - CDN for static assets

2. **Elasticsearch Integration for Books**
   - Full-text search with relevance scoring
   - Faceted search (by genre, author, year)
   - Autocomplete suggestions
   - Better performance for large catalogs

3. **Caching Layer**
   - Redis caching for frequently accessed books
   - Query result caching

4. **Integration Tests**
   - Full API endpoint testing
   - Database transaction testing
   - Authentication flow testing

### Admin Features

5. **Rental Management**
   - View all active rentals
   - Override rental periods
   - Handle overdue rentals
   - Send reminder notifications

6. **User Management**
   - List all users
   - Activate/deactivate accounts
   - Change user roles
   - View user rental history

7. **Statistics Dashboard**
   - Most popular books
   - Active rentals count
   - Overdue rentals
   - User activity metrics
   - Revenue reports (if applicable)

### Additional Improvements

8. **Security Enhancements**
    - Two-factor authentication
    - Password policies
    - Audit logging
    - IP-based rate limiting
    - Server Rate limit
