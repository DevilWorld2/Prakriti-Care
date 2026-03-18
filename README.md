# PrakritiCare - Ayurvedic Drug Recommendation System

A complete web-based Ayurvedic drug recommendation system built with PHP backend and HTML/CSS/JavaScript frontend.

## Features

- **User Authentication**: Secure login and registration system
- **Admin Dashboard**: Complete admin panel for user management
- **Herb Database**: Comprehensive database of Ayurvedic herbs
- **Health Assessments**: Prakriti (dosha) assessment system
- **Personalized Recommendations**: AI-powered herb recommendations
- **Contact System**: Contact form with admin management
- **Responsive Design**: Mobile-friendly interface

## System Requirements

- XAMPP (Apache, MySQL, PHP)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

## Installation & Setup

### 1. Install XAMPP
Download and install XAMPP from https://www.apachefriends.org/

### 2. Clone/Download Project
Place the project files in `C:\xampp\htdocs\Prakriti Care\`

### 3. Start XAMPP Services
- Start Apache
- Start MySQL

### 4. Setup Database
1. Open phpMyAdmin: http://localhost/phpmyadmin/
2. Create database: `prakriti_care`
3. Or run the setup script:
   - Open browser and go to: `http://localhost/Prakriti Care/api/setup.php`
   - This will automatically create the database and tables

### 5. Configure Database (Optional)
Edit `api/config/database.php` if you need to change database credentials.

### 6. Access the Application
- **Frontend**: http://localhost/Prakriti Care/Main Page.html
- **API Base URL**: http://localhost/Prakriti Care/api/

## Default Credentials

### Admin Account
- **Email**: admin@prakriti.care
- **Password**: password

*⚠️ Change the default admin password after first login!*

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/admin-login` - Admin login
- `GET /api/auth/me` - Get current user profile

### Admin
- `GET /api/admin/users` - Get all users (paginated)
- `GET /api/admin/dashboard-stats` - Get dashboard statistics

### Herbs
- `GET /api/herbs` - Get all herbs (paginated)
- `GET /api/herbs/{id}` - Get specific herb
- `GET /api/herbs/search?query={term}` - Search herbs

### Recommendations
- `GET /api/recommendations` - Get user recommendations
- `PUT /api/recommendations/{id}` - Update recommendation status

### Assessments
- `GET /api/assessments` - Get user assessments
- `POST /api/assessments` - Create new assessment

### Contact
- `POST /api/contact` - Submit contact message

### Search
- `GET /api/search?query={term}` - Search user recommendations

## Project Structure

```
Prakriti Care/
├── api/                    # Backend API
│   ├── classes/           # PHP classes
│   │   ├── Database.php
│   │   ├── JWT.php
│   │   ├── User.php
│   │   ├── Admin.php
│   │   ├── Herb.php
│   │   ├── Recommendation.php
│   │   ├── Assessment.php
│   │   └── Contact.php
│   ├── config/
│   │   └── database.php
│   ├── index.php          # Main API router
│   └── setup.php          # Database setup script
├── database/
│   └── schema.sql         # Database schema
├── images/                # Static images
├── *.html                 # Frontend pages
├── *.css                  # Stylesheets
└── README.md             # This file
```

## Security Features

- JWT token-based authentication
- Password hashing with bcrypt
- Input validation and sanitization
- CORS protection
- Session management
- SQL injection prevention

## Development

### Adding New Herbs
Use phpMyAdmin or create a script to insert new herbs into the `herbs` table.

### Customizing Assessments
Modify the `calculateDoshaScores()` method in `Assessment.php` to customize the dosha calculation logic.

### Adding New API Endpoints
1. Add the endpoint logic to `api/index.php`
2. Create or modify the corresponding class in `api/classes/`

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `api/config/database.php`

2. **API Returns 404**
   - Ensure Apache rewrite module is enabled
   - Check that the API URL is correct

3. **Login Not Working**
   - Run the setup script to ensure database is initialized
   - Check that JWT_SECRET is set in config

4. **CORS Errors**
   - The API is configured to allow all origins for development
   - For production, update the CORS settings

### Logs
Check Apache error logs in XAMPP for PHP errors.

## Production Deployment

For production deployment:

1. **Security**:
   - Change default admin password
   - Update JWT_SECRET to a random string
   - Disable error reporting
   - Use HTTPS

2. **Database**:
   - Use a production MySQL server
   - Implement database backups
   - Use connection pooling

3. **Performance**:
   - Enable PHP opcode caching
   - Implement database indexing
   - Use a CDN for static assets

## License

This project is for educational and demonstration purposes.

## Support

For issues or questions, please check the troubleshooting section or create an issue in the repository.