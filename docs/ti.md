# Technical Interview Questions - Backend (Laravel)

## Overview
This document contains technical interview questions for the Full Stack Developer position at GoTeam, specifically focusing on the backend Laravel implementation of the task management application. The questions are designed to assess the candidate's understanding of Laravel best practices, architecture patterns, and the specific implementation details of this project.

---

## 1. Laravel Architecture & Design Patterns

### Q1.1: Repository Pattern Implementation
**Question:** Looking at the codebase, I can see you've implemented the Repository Pattern. Can you explain why you chose this pattern and walk me through how it's implemented in this project?

**Expected Answer:**
- **Why Repository Pattern:** Decouples business logic from data access logic, makes testing easier, provides a clean interface for data operations, and allows for easy switching between different data sources
- **Implementation Details:**
  - `TaskRepositoryInterface` defines the contract
  - `EloquentTaskRepository` implements the interface using Eloquent ORM
  - `RepositoryServiceProvider` binds the interface to implementation
  - Controller uses dependency injection to get the repository instance
- **Benefits in this project:** Easy to mock for testing, clean separation of concerns, consistent data access patterns

**Follow-up:** How would you test the repository methods, and what would you do if you needed to switch from Eloquent to a different ORM?

### Q1.2: Service Provider Registration
**Question:** I notice you have a `RepositoryServiceProvider`. Can you explain what this does and why it's necessary?

**Expected Answer:**
- Registers the repository interface binding in the service container
- Allows dependency injection to work properly
- Keeps the application configuration organized
- Makes it easy to swap implementations (e.g., for testing with mock repositories)

---

## 2. API Design & RESTful Conventions

### Q2.1: Resource Controller Design
**Question:** The `TaskController` extends Laravel's base `Controller` and implements resourceful routes. Can you explain the RESTful conventions you've followed and why you chose this approach?

**Expected Answer:**
- **RESTful Routes:** GET `/api/tasks` (index), POST `/api/tasks` (store), GET `/api/tasks/{id}` (show), PUT/PATCH `/api/tasks/{id}` (update), DELETE `/api/tasks/{id}` (destroy)
- **Benefits:** Predictable URL structure, follows HTTP verb conventions, easy to understand and maintain
- **Custom Route:** Added `PUT /api/tasks/reorder` for the drag-and-drop functionality
- **Consistent Response Format:** Using `TaskResource` for consistent JSON structure

### Q2.2: API Response Structure
**Question:** I see you're using `TaskResource` for API responses. Can you explain the benefits of this approach and show me how it works?

**Expected Answer:**
- **Consistent Data Transformation:** All task data goes through the same transformation logic
- **API Versioning:** Easy to change response format without affecting controllers
- **Data Hiding:** Can exclude sensitive fields or add computed properties
- **Collection Resources:** `TaskResource::collection()` handles arrays of tasks
- **Future-Proofing:** Easy to add new fields or change existing ones

---

## 3. Authentication & Authorization

### Q3.1: Laravel Sanctum Implementation
**Question:** The project uses Laravel Sanctum for authentication. Can you explain how this is implemented and why you chose Sanctum over other authentication methods?

**Expected Answer:**
- **Why Sanctum:** Lightweight, API-focused, supports SPA and mobile apps, token-based authentication
- **Implementation:**
  - `auth:sanctum` middleware protects routes
  - Tokens created with 30-minute expiration
  - `currentAccessToken()` method for token management
  - Token refresh functionality for active users
- **Security Features:** Token expiration, ability to revoke tokens, secure token storage

### Q3.2: Authorization with Policies
**Question:** I see you have a `TaskPolicy` class. Can you explain how authorization works in this application and why you chose policies over gates?

**Expected Answer:**
- **Policy Benefits:** Organized by model, easier to test, cleaner than gates for model-specific authorization
- **Implementation:**
  - `view`, `update`, `delete` methods check if user owns the task
  - `$this->authorize()` calls in controller methods
  - Automatic policy resolution based on model type
- **Security:** Prevents users from accessing/modifying other users' tasks
- **Testability:** Easy to unit test policy methods

---

## 4. Data Validation & Request Handling

### Q4.1: Form Request Validation
**Question:** You're using `TaskRequest` for validation. Can you explain the validation rules and why you chose this approach over inline validation?

**Expected Answer:**
- **Benefits:** Reusable validation logic, cleaner controllers, better organization
- **Validation Rules:**
  - `title`: required, string, max 255 characters
  - `date`: required, valid date format
  - `priority`: enum validation (high, medium, low)
  - `status`: only for updates, enum validation (pending, completed)
- **Conditional Rules:** Different rules for create vs update operations
- **Error Handling:** Automatic JSON error responses with validation messages

### Q4.2: Custom Validation Logic
**Question:** How would you add custom validation to ensure a user can't create more than 50 tasks per day?

**Expected Answer:**
- Add custom validation rule in `TaskRequest`
- Use `Rule::unique()` with additional conditions
- Or create a custom validation rule class
- Consider database constraints for additional safety
- Handle edge cases like timezone differences

---

## 5. Database Design & Migrations

### Q5.1: Database Schema Design
**Question:** Looking at the tasks table migration, can you explain the database design decisions and the indexes you've created?

**Expected Answer:**
- **Table Structure:**
  - `id`: Primary key
  - `user_id`: Foreign key with cascade delete
  - `title`: String for task description
  - `status`: Enum for pending/completed
  - `date`: Date field for task scheduling
  - `priority`: String for task priority
  - `order`: Integer for drag-and-drop ordering
- **Indexes:**
  - `user_id`: For filtering user's tasks
  - `date`: For date-based filtering
  - `status`: For status-based filtering
- **Soft Deletes:** Added later for data recovery

### Q5.2: Soft Deletes Implementation
**Question:** I see you've added soft deletes to the tasks table. Can you explain why this was added and how it affects the application?

**Expected Answer:**
- **Why Soft Deletes:** Data recovery, audit trails, user experience (accidental deletions)
- **Implementation:**
  - `SoftDeletes` trait in Task model
  - `deleted_at` column in database
  - `withTrashed()` in repository for finding deleted tasks
- **API Behavior:** Returns 200 with "already deleted" message for soft-deleted tasks
- **Security:** Still enforces ownership through policies

---

## 6. Testing Strategy

### Q6.1: Test Coverage Analysis
**Question:** Looking at the test suite, I can see comprehensive API tests. Can you explain your testing strategy and what types of tests you've implemented?

**Expected Answer:**
- **Test Types:**
  - **Feature Tests:** Full API endpoint testing with authentication
  - **Unit Tests:** Individual component testing
  - **Integration Tests:** Database interactions and business logic
- **Test Categories:**
  - **CRUD Operations:** Create, read, update, delete tasks
  - **Filtering & Sorting:** Date, status, priority, search filters
  - **Authorization:** User isolation and permission checks
  - **Validation:** Input validation and error handling
  - **Edge Cases:** Empty results, non-existent resources
  - **Pagination:** Large dataset handling

### Q6.2: Test Data Management
**Question:** How do you handle test data in your tests, and what testing tools are you using?

**Expected Answer:**
- **Factories:** `TaskFactory` and `UserFactory` for consistent test data
- **Database Refresh:** `RefreshDatabase` trait for clean test environment
- **Sanctum Testing:** `Sanctum::actingAs()` for authenticated requests
- **Pest PHP:** Modern testing framework with readable syntax
- **Test Isolation:** Each test runs independently with fresh data

---

## 7. Performance & Optimization

### Q7.1: Query Optimization
**Question:** Looking at the repository methods, how have you optimized database queries for performance?

**Expected Answer:**
- **Eager Loading:** Not needed in this simple case, but would use `with()` for relationships
- **Query Building:** Conditional query building to avoid unnecessary WHERE clauses
- **Indexes:** Proper indexing on frequently queried columns
- **Pagination:** Implemented for large datasets
- **Raw Queries:** Used `whereRaw()` for case-insensitive search

### Q7.2: N+1 Query Problem
**Question:** How would you identify and solve N+1 query problems in this application?

**Expected Answer:**
- **Identification:** Laravel Debugbar, query logging, or database monitoring
- **Solutions:**
  - Eager loading with `with()` for relationships
  - Lazy loading for large datasets
  - Query optimization
- **Example:** If tasks had categories, would use `Task::with('category')->get()`

---

## 8. Error Handling & Logging

### Q8.1: Error Response Strategy
**Question:** How do you handle errors in the API, and what's your approach to error responses?

**Expected Answer:**
- **HTTP Status Codes:** 200, 201, 404, 422, 403, 500
- **Consistent Format:** JSON responses with message and optional error codes
- **Validation Errors:** 422 with detailed field errors
- **Authorization Errors:** 403 for forbidden access
- **Not Found:** 404 with descriptive messages
- **Custom Error Codes:** Like 'TASK_NOT_FOUND' for better frontend handling

### Q8.2: Logging Strategy
**Question:** I see some logging in the AuthController. What's your logging strategy for this application?

**Expected Answer:**
- **Log Levels:** Error for exceptions, info for important events
- **Context:** Include relevant data like user ID, task ID
- **Security:** Don't log sensitive data like passwords
- **Monitoring:** Use structured logging for better analysis
- **Example:** Password reset email failures are logged with error details

---

## 9. Security Considerations

### Q9.1: Input Sanitization
**Question:** How do you ensure the application is secure against common vulnerabilities?

**Expected Answer:**
- **Validation:** Server-side validation for all inputs
- **SQL Injection:** Eloquent ORM prevents SQL injection
- **XSS:** Input sanitization and proper output encoding
- **CSRF:** Sanctum handles CSRF for SPA
- **Mass Assignment:** `$fillable` arrays in models
- **Authentication:** Secure token handling with expiration

### Q9.2: Data Privacy
**Question:** How do you ensure user data privacy and isolation?

**Expected Answer:**
- **User Isolation:** All queries filtered by `user_id`
- **Authorization:** Policies ensure users can only access their data
- **Soft Deletes:** Data recovery without exposing to other users
- **Token Security:** Secure token generation and storage
- **Password Security:** Proper hashing with Laravel's Hash facade

---

## 10. Code Quality & Best Practices

### Q10.1: Code Organization
**Question:** How have you organized the codebase to maintain clean, readable code?

**Expected Answer:**
- **MVC Pattern:** Clear separation of concerns
- **Repository Pattern:** Data access abstraction
- **Resource Classes:** Consistent API responses
- **Form Requests:** Validation logic separation
- **Policies:** Authorization logic organization
- **Service Providers:** Dependency injection configuration

### Q10.2: Laravel Conventions
**Question:** How well do you follow Laravel conventions in this project?

**Expected Answer:**
- **Naming:** PSR-4 autoloading, descriptive class names
- **Directory Structure:** Standard Laravel directory organization
- **Artisan Commands:** Used for migrations, factories, etc.
- **Configuration:** Proper use of config files
- **Middleware:** Appropriate use of middleware for authentication
- **Blade Components:** (If used) Proper component organization

---

## 11. Advanced Laravel Features

### Q11.1: Eloquent Relationships
**Question:** The Task model has a relationship with User. Can you explain how this is implemented and what other relationships might be useful?

**Expected Answer:**
- **Current:** `belongsTo(User::class)` relationship
- **Benefits:** Easy access to user data, automatic foreign key handling
- **Other Relationships:** Could add categories, tags, comments, etc.
- **Eager Loading:** Would use `with('user')` to prevent N+1 queries

### Q11.2: Model Events & Observers
**Question:** How would you implement automatic task ordering when a new task is created?

**Expected Answer:**
- **Model Events:** Use `creating` or `created` event
- **Observer Pattern:** Create TaskObserver for complex logic
- **Service Classes:** Use services for business logic
- **Example:** Auto-assign order based on existing tasks for the same date

---

## 12. Deployment & DevOps

### Q12.1: Environment Configuration
**Question:** How would you configure this application for different environments (local, staging, production)?

**Expected Answer:**
- **Environment Files:** `.env` files for each environment
- **Configuration:** Use `config()` helper for environment-specific settings
- **Database:** Different databases for each environment
- **Caching:** Redis for production, file cache for local
- **Logging:** Different log levels and handlers
- **Security:** Different encryption keys and CORS settings

### Q12.2: Laravel Sail Usage
**Question:** The project uses Laravel Sail. Can you explain how this benefits development and what services are included?

**Expected Answer:**
- **Docker Environment:** Consistent development environment
- **Services:** PHP, MySQL, Redis, MailHog, etc.
- **Benefits:** Easy setup, consistent across team, production-like environment
- **Commands:** `sail up`, `sail artisan`, `sail test`
- **Database:** Easy database management and seeding

---

## 13. Problem-Solving Scenarios

### Q13.1: Performance Issue
**Question:** If users reported that the task list is loading slowly when they have many tasks, how would you investigate and solve this?

**Expected Answer:**
- **Investigation:** Check query logs, use Laravel Debugbar, profile the application
- **Solutions:**
  - Add database indexes
  - Implement pagination
  - Use eager loading if needed
  - Add caching for frequently accessed data
  - Optimize queries
- **Monitoring:** Set up performance monitoring

### Q13.2: Data Migration
**Question:** If you needed to add a new field to tasks and migrate existing data, how would you approach this?

**Expected Answer:**
- **Migration:** Create a new migration file
- **Data Migration:** Use `update()` or raw queries for existing data
- **Rollback:** Ensure migration can be rolled back
- **Testing:** Test migration on staging environment
- **Deployment:** Use zero-downtime deployment strategies

---

## 14. Code Review Questions

### Q14.1: Code Improvement
**Question:** Looking at the `TaskController::reorder` method, how would you improve it?

**Expected Answer:**
- **Validation:** More robust validation of order values
- **Transaction:** Wrap in database transaction for consistency
- **Batch Update:** Use `upsert()` for better performance
- **Error Handling:** Better error messages and handling
- **Logging:** Add logging for audit trail

### Q14.2: Refactoring Opportunities
**Question:** If you had to refactor this codebase, what would you focus on?

**Expected Answer:**
- **Service Layer:** Extract business logic to service classes
- **Event System:** Use events for task creation/updates
- **Caching:** Add caching for frequently accessed data
- **API Versioning:** Implement API versioning strategy
- **Documentation:** Add API documentation with OpenAPI/Swagger

---

## 15. Real-World Scenarios

### Q15.1: Scaling Considerations
**Question:** If this application needed to handle 100,000+ users, what changes would you make?

**Expected Answer:**
- **Database:** Database optimization, read replicas, connection pooling
- **Caching:** Redis for session storage and caching
- **Queue System:** Background job processing
- **CDN:** For static assets
- **Load Balancing:** Multiple application servers
- **Monitoring:** Application performance monitoring

### Q15.2: Feature Extension
**Question:** If you needed to add task categories and subcategories, how would you implement this?

**Expected Answer:**
- **Database Design:** Categories table with self-referencing foreign key
- **Models:** Category model with hierarchical relationships
- **API:** New endpoints for category management
- **Validation:** Category validation rules
- **UI:** Frontend components for category selection
- **Migration:** Data migration strategy for existing tasks

---

## Evaluation Criteria

### Technical Knowledge (40%)
- Understanding of Laravel concepts and best practices
- Knowledge of PHP and object-oriented programming
- Understanding of database design and optimization
- Knowledge of testing strategies and tools

### Problem-Solving (30%)
- Ability to analyze code and identify issues
- Creative solutions to complex problems
- Understanding of trade-offs and performance implications
- Ability to think about scalability and maintainability

### Code Quality (20%)
- Understanding of clean code principles
- Knowledge of design patterns and architecture
- Ability to write maintainable and testable code
- Understanding of security best practices

### Communication (10%)
- Ability to explain technical concepts clearly
- Understanding of business requirements
- Ability to work in a team environment
- Professional communication skills

---

## Notes for Interviewers

1. **Adapt Questions:** Adjust difficulty based on candidate's experience level
2. **Code Walkthrough:** Have candidates explain specific parts of the codebase
3. **Live Coding:** Ask candidates to implement small features or fixes
4. **Scenario-Based:** Use real-world scenarios to test problem-solving skills
5. **Follow-up Questions:** Ask deeper questions based on initial responses

This interview guide covers the essential aspects of the Laravel backend implementation and should help assess candidates' technical skills, problem-solving abilities, and understanding of modern web development practices.