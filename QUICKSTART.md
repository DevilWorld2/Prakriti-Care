# PrakritiCare - Quick Start Guide

## 🚀 Getting Started

### 1. Start XAMPP
- Open XAMPP Control Panel
- Start **Apache** and **MySQL** services

### 2. Setup Database
Open your browser and go to:
```
http://localhost/Prakriti Care/api/setup.php
```
This will create the database and tables automatically.

### 3. Verify Setup
Check the health endpoint:
```
http://localhost/Prakriti Care/api/health
```

### 4. Access the Application
- **Main Website**: http://localhost/Prakriti Care/Main Page.html
- **User Login**: http://localhost/Prakriti Care/login-select.html

## 👤 Default Accounts

### Admin Account
- **Email**: admin@prakriti.care
- **Password**: password
- **Access**: http://localhost/Prakriti Care/admin-login.html

### Test User Account
Create a new account through the registration page or use the API.

## 🔧 API Testing

You can test the API endpoints using tools like Postman or curl:

### Health Check
```bash
curl http://localhost/Prakriti Care/api/health
```

### User Registration
```bash
curl -X POST http://localhost/Prakriti Care/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"fullname":"Test User","email":"test@example.com","password":"password123"}'
```

### User Login
```bash
curl -X POST http://localhost/Prakriti Care/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

## 📊 Database Structure

The system includes these main tables:
- `users` - User accounts
- `admins` - Admin accounts
- `herbs` - Ayurvedic herbs database
- `diseases` - Disease/symptom mappings
- `assessments` - Health assessments
- `recommendations` - Herb recommendations
- `contact_messages` - Contact form submissions

## 🎯 Key Features Working

✅ **User Registration & Login**
- Secure password hashing
- JWT token authentication
- Session management

✅ **Admin Dashboard**
- User management
- System statistics
- Contact message management

✅ **Herb Database**
- 10+ sample herbs
- Search functionality
- Dosha-based filtering

✅ **Health Assessments**
- Prakriti (dosha) analysis
- Symptom-based recommendations
- Personalized herb suggestions

✅ **Recommendation System**
- Dynamic herb recommendations
- Status tracking
- History management

✅ **Contact System**
- Contact form submission
- Admin message management

## 🔒 Security Features

- JWT-based authentication
- Password hashing (bcrypt)
- Input validation
- CORS protection
- SQL injection prevention
- XSS protection headers

## 🐛 Troubleshooting

### Common Issues

1. **"Could not connect to server"**
   - Ensure Apache and MySQL are running in XAMPP
   - Check that the database was created successfully

2. **Database errors**
   - Run the setup script again: `http://localhost/Prakriti Care/api/setup.php`
   - Check MySQL credentials in `api/config/database.php`

3. **API returns 404**
   - Ensure `.htaccess` file is present
   - Check Apache rewrite module is enabled

4. **Login not working**
   - Verify database has the correct tables
   - Check JWT secret in config

### Debug Tools

- **Health Check**: `http://localhost/Prakriti Care/api/health`
- **Test Script**: `http://localhost/Prakriti Care/api/test.php`
- **Setup Script**: `http://localhost/Prakriti Care/api/setup.php`

## 📝 Next Steps

1. **Change Default Password**: Update admin password immediately
2. **Add More Herbs**: Use the populate script or admin interface
3. **Customize Assessments**: Modify dosha calculation logic
4. **Add Features**: Extend API with new endpoints
5. **Production Deployment**: Configure for production use

## 📞 Support

For issues, check:
1. Apache error logs in XAMPP
2. Browser developer console
3. API response messages
4. This documentation

The system is now fully functional! 🎉