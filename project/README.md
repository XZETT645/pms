# Program Management System

A comprehensive PHP-based program management system designed for government organizations with role-based access control.

## Features

### User Roles
- **Admin**: Full system access, user management, status tracking
- **EXCO User**: Program creation and management
- **EXCO PA**: Program management with query handling capabilities
- **Finance**: Program review, approval/rejection, and financial processing

### Core Functionality

#### Dashboard
- Overview statistics (total, approved, rejected, pending programs)
- Budget tracking (total and remaining budget)
- Recent programs table
- Role-based access to different sections

#### User Management (Admin Only)
- Add/edit users
- Role assignment
- User status management (active/inactive)
- User statistics dashboard

#### Program Management
- Create and edit programs
- Document upload and management
- Program submission workflow
- Status tracking through approval process
- Budget management with voucher and EFT number tracking

#### Query System
- Finance can submit queries to EXCO PA
- EXCO PA can respond to queries
- Query history tracking
- Status updates based on query interactions

#### Status Tracking
- Real-time program status monitoring
- Filtering and search capabilities
- Export functionality
- Comprehensive program history

## Database Schema

### Users Table
- User authentication and role management
- Profile information and photo upload
- Account status tracking

### Programs Table
- Program details (name, budget, recipient, reference numbers)
- Status workflow management
- Financial tracking (voucher/EFT numbers)
- Audit trail with timestamps

### Documents Table
- File upload management
- Document categorization (program documents vs signed documents)
- User tracking for uploads

### Queries Table
- Query submission and response system
- Status tracking (Open/Answered)
- User relationships for query management

### Remarks Table
- Program discussion and comments
- Multi-user collaboration features

## Installation

1. **Database Setup**
   ```sql
   -- Import the database.sql file to create the database structure
   mysql -u root -p < database.sql
   ```

2. **Configuration**
   - Update `config/database.php` with your database credentials
   - Ensure proper file permissions for upload directories

3. **File Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/documents/
   chmod 755 uploads/signed_documents/
   chmod 755 uploads/profile_photos/
   ```

4. **Default Login**
   - Email: `admin@kedah.gov.my`
   - Password: `admin123`

## Security Features

- Password hashing using PHP's password_hash()
- Session management with role-based access control
- File upload validation and security
- SQL injection prevention with prepared statements
- CSRF protection considerations

## Design Features

- Responsive design with Bootstrap 5
- Kedah Government color scheme (Blue and Gold)
- Modern UI with hover effects and transitions
- Mobile-friendly interface
- Accessibility considerations

## File Structure

```
/
├── config/
│   ├── database.php
│   └── session.php
├── includes/
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── uploads/
│   ├── documents/
│   ├── signed_documents/
│   └── profile_photos/
├── login.php
├── dashboard.php
├── user_management.php
├── program_management.php
├── status_tracking.php
├── query.php
├── profile.php
└── logout.php
```

## Usage Guidelines

### For Administrators
1. Access user management to add/manage system users
2. Monitor all program activities through status tracking
3. Generate reports and export data as needed

### For EXCO Users/PA
1. Create programs with required documentation
2. Submit programs for finance review
3. Respond to queries from finance team
4. Track program status through the approval process

### For Finance Team
1. Review submitted programs and documentation
2. Submit queries for clarification when needed
3. Approve or reject programs with proper documentation
4. Upload signed documents and enter financial details

## Technical Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser with JavaScript enabled

## Support

For technical support or questions about the system, please contact the system administrator.