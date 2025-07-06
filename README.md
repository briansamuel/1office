# 1Office - Laravel Modular Application

A scalable Laravel + Vue 3 modular application inspired by office management systems, featuring Work, HRM, CRM, and Warehouse modules.

## 🏗️ Architecture

This application follows a modular architecture pattern with the following structure:

```
/app
    /Modules
        /Work          # Task and project management
        /HRM           # Human resource management
        /CRM           # Customer relationship management
        /Warehouse     # Inventory and order management
    /Services          # Shared business logic
    /Repositories      # Data access layer
    /Enums            # Application enums
```

## 🚀 Features

### Work Module
- ✅ Task management with Kanban board
- ✅ Task status tracking (Todo, In Progress, In Review, Completed)
- ✅ Priority levels (Low, Medium, High, Urgent)
- ✅ Task assignment and due dates
- ✅ Real-time status updates
- ✅ Task filtering and search

### HRM Module (Planned)
- Employee management
- Attendance tracking
- Leave management
- Performance reviews

### CRM Module (Planned)
- Customer management
- Lead tracking
- Deal pipeline
- Sales reporting

### Warehouse Module (Planned)
- Product catalog
- Inventory management
- Order processing
- Stock tracking

## 🛠️ Tech Stack

**Backend:**
- Laravel 10.x (PHP 8.3+)
- MySQL/PostgreSQL
- Laravel Sanctum (API Authentication)
- Repository Pattern
- Service Layer Pattern
- Observer Pattern

**Frontend:**
- Vue 3 (Composition API)
- Vite (Build tool)
- Tailwind CSS
- Pinia (State management)
- Vue Router
- Axios (HTTP client)

## 📋 Requirements

- PHP 8.3+
- Composer
- Node.js 18+
- MySQL 8.0+ or PostgreSQL 13+

## 🔧 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd 1office
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=1office
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   # or for development
   npm run dev
   ```

9. **Start the application**
   ```bash
   php artisan serve
   ```

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

Run specific test types:
```bash
# Unit tests
php artisan test --testsuite=Unit

# Feature tests
php artisan test --testsuite=Feature
```

## 📚 API Documentation

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/user` - Get authenticated user

### Work Module
- `GET /api/work/tasks` - List tasks with filters
- `POST /api/work/tasks` - Create new task
- `GET /api/work/tasks/{id}` - Get task details
- `PUT /api/work/tasks/{id}` - Update task
- `DELETE /api/work/tasks/{id}` - Delete task
- `PATCH /api/work/tasks/{id}/status` - Update task status
- `PATCH /api/work/tasks/{id}/assign` - Assign task to user
- `GET /api/work/tasks/kanban` - Get Kanban board data
- `GET /api/work/tasks/statistics` - Get task statistics

## 🔐 Authentication & Authorization

The application uses Laravel Sanctum for API authentication with role-based access control:

- **Admin**: Full system access
- **Manager**: Module management access
- **User**: Basic task access
- **HR**: Human resources access
- **Accountant**: Financial and inventory access

## 🎨 Frontend Structure

```
/resources/js
    /components     # Reusable Vue components
    /pages         # Page components
    /stores        # Pinia stores
    /router        # Vue Router configuration
    /utils         # Utility functions
```

## 🚀 Deployment

### Using Laravel Forge
1. Connect your repository to Forge
2. Configure environment variables
3. Set up database
4. Deploy

### Using Docker
```bash
# Build and run with Docker Compose
docker-compose up -d
```

### Manual Deployment
1. Upload files to server
2. Install dependencies
3. Configure web server (Apache/Nginx)
4. Set up database
5. Configure environment
6. Build assets

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write tests
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the documentation
- Review the code examples

## 🔄 Changelog

### v1.0.0
- Initial release
- Work module with task management
- Kanban board interface
- User authentication
- Modular architecture setup
