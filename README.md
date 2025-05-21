# CodingABCS

A full-stack web application with a secure API backend and modern frontend client.

## Project Overview

The project consists of two main components:
- **API**: A secure PHP-based backend service
- **Client**: A modern frontend application

## Project Structure

```
codingabcs/
├── api/                # Backend API
│   ├── src/
│   │   ├── config/    # Configuration files
│   │   ├── controllers/ # API controllers
│   │   ├── core/      # Core application logic
│   │   ├── middleware/ # Custom middleware
│   │   ├── migrations/ # Database migrations
│   │   ├── models/    # Data models
│   │   ├── routes/    # Route definitions
│   │   ├── schemas/   # Data schemas
│   │   └── services/  # Business logic services
│   ├── public/        # Public assets
│   └── vendor/        # Composer dependencies
│
└── client/            # Frontend application
    ├── public/        # Static files
    ├── pages/         # Page components
    ├── components/    # Reusable components
    └── assets/        # Images, styles, etc.
```

## Prerequisites

### Backend (API)
- PHP 7.4 or higher
- Composer 2.x
- XAMPP/WAMP/MAMP (for local development)
- MySQL 5.7 or higher

### Frontend (Client)
- Modern web browser with JavaScript enabled
- No additional installation required for production use

## Installation

### Backend Setup

1. Clone the repository:
```bash
git clone [repository-url]
cd codingabcs
```

2. Install PHP dependencies:
```bash
cd api
composer install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Configure your environment variables in `.env`:
```env
DB_HOST=localhost
DB_NAME=codingabcs
DB_USER=your_username
DB_PASS=your_password
JWT_SECRET=your_jwt_secret
```

5. Set up your web server:
   - Point your web server's document root to the `api/public` directory
   - Ensure the web server has write permissions to necessary directories
   - Configure URL rewriting to direct all requests to `index.php`

### Frontend Setup

1. The frontend is a static application that can be served directly from any web server
2. Configure your web server to serve the contents of the `client` directory
3. Ensure the API endpoint is correctly configured in your environment

## Development

### Backend Development

1. Start your local development server (XAMPP/WAMP/MAMP)
2. Access the API at `http://localhost/codingabcs/api`

### Frontend Development

1. Serve the client directory using your preferred web server
2. Access the client at `http://localhost/codingabcs/client`

## Dependencies

### Backend
- vlucas/phpdotenv: ^5.6 - Environment variable management
- firebase/php-jwt: ^6.11 - JWT authentication

### Frontend
- Pure HTML, CSS, and JavaScript
- No external dependencies required

## Security

- All API endpoints are protected with JWT authentication
- Environment variables are used for sensitive configuration
- Input validation is implemented for all requests
- CORS is properly configured for secure client-server communication
- HTTPS is enforced in production

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is proprietary and confidential. All rights reserved.

## Support

For support, email dev@pecollins.com 