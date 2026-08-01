# Modern Database Manager

A professional, modern PHP-based database management interface with advanced inline editing capabilities and ALTER TABLE support, built with a modular, scalable architecture.

## ✨ New Features (v4.0)

### 📁 Database File Manager 🚀 NEW!
- **Work Without Server** - Create and manage database schemas offline
- **File-Based Storage** - Save schemas as portable `.dbschema` files
- **Full Designer Support** - Open files in visual designer
- **Import/Export** - Move between files and live databases
- **Version Control Friendly** - Commit schemas to Git
- **Search & Organize** - Tags, descriptions, and metadata
- **Team Collaboration** - Share schemas as files

See **FILE_MANAGER_QUICKSTART.md** for quick start guide!

### 📤 Professional Export System
- **SQL Export** - Export database or tables as SQL files
  - Full database or single table
  - Structure + Data or Structure only
  - phpMyAdmin compatible format
  - Ready-to-import SQL dumps

- **Laravel Migration Generator** 🚀
  - Generate Laravel 10+ compatible migration files
  - Full database export as ZIP archive
  - Single table export as PHP file
  - Automatic column type conversion (40+ types)
  - Preserves indexes, foreign keys, and constraints
  - Ready to use in Laravel projects

### 📥 Database Import
- **Upload SQL files** (up to 50MB)
- **Paste SQL content** directly
- **Progress indicators** and detailed reporting
- **Error handling** with rollback information
- **Automatic refresh** after successful import

### 🏗️ Modular Architecture
- **Separated into multiple files** for better maintainability and scalability
- **MVC-like structure** with clear separation of concerns
- **Easy to extend** with new features
- **Reusable components** and classes

### 🔧 ALTER TABLE Support
- **Change column types** directly from the UI
- **Visual column type editor** with all common MySQL data types
- **Safety warnings** before making changes
- **Preserve column constraints** (NULL, DEFAULT, AUTO_INCREMENT)

### 🎯 Type-Aware Editing
- **Smart input fields** based on column type:
  - **Number fields** for INT, DECIMAL, FLOAT types
  - **Date/Time pickers** for DATE, DATETIME, TIMESTAMP
  - **Checkboxes** for BOOLEAN/TINYINT(1)
  - **Select dropdowns** for ENUM types
  - **Textareas** for TEXT/BLOB columns
  - **Regular text inputs** for VARCHAR/CHAR
- **Automatic validation** based on data type
- **Formatted display** for different data types

## 🎨 Core Features

### Modern UI/UX
- Clean, professional interface with Tailwind CSS
- Responsive design that works on all devices
- Smooth animations and transitions
- Toast notifications for user feedback

### 💾 Database Management
- Browse all databases on your MySQL server
- View all tables within each database
- Display table data with pagination
- View table structure and data types
- Primary key indicators
- **ALTER column types** with UI

### ✏️ Advanced Inline Editing
- Click any cell to edit its value
- **Type-specific input controls** (date pickers, checkboxes, etc.)
- Real-time updates without page refresh
- Visual feedback during editing
- Automatic save on blur or Enter key
- Cancel editing with Escape key

### 🔍 Advanced Features
- **SQL Query Execution**: Execute custom SQL queries with results display
- **Delete Rows**: Remove records with confirmation
- **Pagination**: Navigate large datasets efficiently (50 rows per page)
- **Column Type Management**: Change column types with ALTER TABLE
- **Keyboard Shortcuts**: 
  - `Ctrl+Q`: Open SQL query modal
  - `Escape`: Close modal or cancel edit
  - `Enter`: Save cell edit

### 🔒 Security Features
- PDO with prepared statements (SQL injection prevention)
- Input validation and sanitization
- Error handling with user-friendly messages
- Session management

## Installation

1. **Clone or download** this project to your XAMPP htdocs directory:
   ```
   c:\xampp\htdocs\workplace\phpmyadmin\
   ```

2. **Configure database connection** in `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Start XAMPP** services:
   - Apache
   - MySQL

4. **Access the application** in your browser:
   ```
   http://localhost/workplace/phpmyadmin/
   ```

## Usage

### Browsing Data
1. Select a database from the left sidebar
2. Select a table to view its contents
3. Navigate through pages using pagination controls

### Editing Data
1. Click on any cell (except primary key cells)
2. Modify the value
3. Press Enter or click outside to save
4. Press Escape to cancel

### Deleting Rows
1. Click the trash icon in the Actions column
2. Confirm the deletion
3. Row will be removed immediately

### Running SQL Queries
1. Click "SQL Query" button in the top bar (or press Ctrl+Q)
2. Enter your SQL query
3. Click "Execute" to run
4. View results in the modal

### Exporting Database
1. Select a database
2. Click "Export" button (or press Ctrl+E)
3. Choose export type:
   - **SQL Export**: For database backup or migration
   - **Laravel Migration**: For Laravel projects
4. Select scope (Full Database or Current Table)
5. Click export button to download

### Importing Database
1. Select a database
2. Click "Import" button (or press Ctrl+I)
3. Upload SQL file or paste SQL content
4. Click "Import Database"
5. Confirm and wait for completion

### Generating Laravel Migrations
1. Select a database
2. Click "Export" button
3. Choose "Laravel Migration"
4. Download ZIP file
5. Extract to Laravel's `database/migrations/` folder
6. Run `php artisan migrate`

## Configuration

### Pagination
Change the number of rows per page in `config.php`:
```php
define('ROWS_PER_PAGE', 50);
```

### Feature Toggles
Enable or disable features in `config.php`:
```php
define('ALLOW_DELETE', true);
define('ALLOW_INLINE_EDIT', true);
define('ALLOW_SQL_QUERIES', true);
```

## Technical Details

### Built With
- **PHP 7.4+**: Server-side logic
- **PDO**: Database connectivity
- **Tailwind CSS**: Styling and responsive design
- **Font Awesome**: Icons
- **Vanilla JavaScript**: Client-side interactivity

### File Structure
```
phpmyadmin/
├── index.php                          # Main entry point
├── file_manager.php                   # File Manager interface (NEW v4.0)
├── designer.php                       # Visual database designer
├── config.php                         # Configuration settings
├── README.md                          # This file
├── FILE_MANAGER_QUICKSTART.md         # File Manager quick start (NEW v4.0)
├── FILE_MANAGER_GUIDE.md              # Complete File Manager guide (NEW v4.0)
├── FILE_MANAGER_IMPLEMENTATION.md     # Technical implementation (NEW v4.0)
├── PROJECT_SUMMARY.md                 # Complete project overview
├── EXPORT_IMPORT_GUIDE.md            # Export/Import documentation
├── LARAVEL_MIGRATION_GUIDE.md        # Laravel migration reference
├── handlers/
│   ├── ajax_handler.php              # Handles all AJAX requests
│   ├── export_handler.php            # Handles export downloads
│   ├── file_handler.php              # File Manager API (NEW v4.0)
│   └── designer_handler.php          # Designer operations (updated v4.0)
├── includes/
│   ├── Database.php                  # Database connection singleton
│   ├── DatabaseOperations.php        # All database operations
│   ├── DatabaseFileManager.php       # File-based schema management (NEW v4.0)
│   ├── DatabaseDesigner.php          # Visual designer logic
│   ├── ColumnTypeHelper.php          # Column type utilities
│   ├── LaravelMigrationGenerator.php # Laravel migration generator
│   └── DatabaseExportImport.php      # Export/Import functionality
├── views/
│   ├── header.php                    # HTML head and opening tags
│   ├── footer.php                    # Closing tags and scripts
│   ├── sidebar.php                   # Database/table navigation (updated v4.0)
│   ├── table_view.php                # Main table data view
│   ├── database_overview.php         # Database tables overview
│   └── modals.php                    # All modal dialogs
├── storage/                           # File storage (NEW v4.0)
│   ├── README.md                     # Storage documentation
│   └── schemas/                      # Schema files directory
│       └── .gitkeep                  # Git tracking
└── assets/
    ├── css/
    │   └── styles.css                # Custom styles
    └── js/
        ├── main.js                   # Core JavaScript functions
        ├── editor.js                 # Cell editing logic
        ├── designer.js               # Designer canvas logic (updated v4.0)
        ├── file_manager.js           # File Manager client (NEW v4.0)
        └── modals.js                 # Modal management
```

### Browser Compatibility
- Chrome (latest)
- Firefox (latest)
- Edge (latest)
- Safari (latest)

## Security Notes

⚠️ **Important**: This is a development tool and should NOT be exposed to production environments without proper security measures:

1. Add authentication/authorization
2. Implement CSRF protection
3. Add IP whitelisting
4. Use HTTPS in production
5. Set strong database passwords
6. Limit database user permissions

## Features in Detail

### Export & Import System
- **SQL Export**:
  - Full database or single table export
  - Structure + Data or Structure only options
  - phpMyAdmin compatible format
  - Instant download as .sql file
  
- **Laravel Migration Generator**:
  - Converts MySQL tables to Laravel migration files
  - Supports 40+ column types with automatic conversion
  - Preserves indexes, foreign keys, and constraints
  - Full database exports as ZIP archive
  - Single table exports as PHP file
  - Laravel 10+ compatible
  
- **SQL Import**:
  - Upload SQL files up to 50MB
  - Paste SQL content directly
  - Progress indicators and detailed error reporting
  - Automatic page refresh after successful import
  - Validates file size and type

### Type-Aware Inline Editing
- **Visual Feedback**: Cells highlight on hover and change color when editing
- **Smart Input Controls**: Different input types based on column data type
  - Date picker for DATE columns
  - Datetime picker for DATETIME/TIMESTAMP columns
  - Number input with step control for numeric types
  - Checkbox for BOOLEAN/TINYINT(1)
  - Dropdown for ENUM types
  - Textarea for TEXT/BLOB columns
- **NULL Handling**: Display and edit NULL values properly
- **Real-time Updates**: Changes saved immediately via AJAX
- **Error Handling**: Shows friendly error messages if update fails
- **Type Validation**: Validates input based on column type

### ALTER TABLE Column Type
- **Visual Interface**: Change column types without writing SQL
- **Comprehensive Type List**: All common MySQL data types organized by category
  - Integer Types (TINYINT, SMALLINT, INT, BIGINT)
  - Decimal Types (DECIMAL, FLOAT, DOUBLE)
  - String Types (VARCHAR, CHAR, TEXT variants)
  - Date/Time Types (DATE, DATETIME, TIMESTAMP, TIME, YEAR)
  - Binary Types (BLOB variants)
  - Other Types (BOOLEAN, ENUM, SET, JSON)
- **Constraint Preservation**: Maintains NULL/NOT NULL, DEFAULT values, and AUTO_INCREMENT
- **Safety Warnings**: Alerts about potential data loss
- **Automatic Refresh**: Updates UI after successful ALTER

### Pagination
- Displays 50 rows per page (configurable)
- Shows current page and total pages
- Quick navigation with Previous/Next buttons
- Jump to specific pages
- Displays row count information

### SQL Query Executor
- Syntax highlighting (monospace font)
- Pre-filled with sample query
- Displays results in a formatted table
- Error messages for invalid queries
- Clear button to reset query

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+E` | Open Export modal |
| `Ctrl+I` | Open Import modal |
| `Ctrl+Q` | Open SQL query modal |
| `Escape` | Close modal or cancel edit |
| `Enter` | Save cell edit |
| `Click` | Edit cell |

## Troubleshooting

### Connection Issues
- Verify XAMPP MySQL is running
- Check database credentials in `config.php`
- Ensure PHP PDO MySQL extension is enabled

### Editing Not Working
- Check browser console for JavaScript errors
- Verify table has a primary key
- Ensure ALLOW_INLINE_EDIT is true

### Styling Issues
- Ensure internet connection (Tailwind CSS CDN)
- Clear browser cache
- Check browser compatibility

## Architecture Benefits

### Scalability
- **Modular Design**: Easy to add new features without touching existing code
- **Separation of Concerns**: Business logic, presentation, and data access are separated
- **Reusable Components**: Classes and functions can be reused across the application
- **Easy Testing**: Isolated components are easier to test

### Maintainability
- **Clear Structure**: Organized file structure makes it easy to find code
- **Single Responsibility**: Each file/class has one clear purpose
- **Less Code Duplication**: Shared functionality in reusable classes
- **Better Documentation**: Smaller files are easier to document and understand

## Future Enhancements

Possible features for future versions:
- [x] Modular architecture with separated files
- [x] ALTER TABLE column type support
- [x] Type-aware cell editing
- [ ] Add new rows with INSERT
- [ ] Export data (CSV, JSON, SQL)
- [ ] Import data from files
- [ ] Advanced search and filtering with WHERE clauses
- [x] Export database as SQL
- [x] Import database from SQL
- [x] Generate Laravel migrations
- [ ] Full table structure modification (add/drop columns)
- [ ] User authentication system
- [ ] Query history with favorites
- [ ] Dark mode theme
- [ ] Multi-row editing
- [ ] Drag-and-drop column reordering
- [ ] Foreign key relationship visualization
- [ ] CSV/Excel export options

## License

This project is open-source and available for personal and commercial use.

## Documentation

Comprehensive guides are available:
- **EXPORT_IMPORT_GUIDE.md** - Complete export/import documentation
- **LARAVEL_MIGRATION_GUIDE.md** - Laravel migration generation guide
- **VISUAL_EXPORT_IMPORT_GUIDE.md** - Visual walkthrough with diagrams
- **FEATURES_EXPORT_IMPORT.md** - Feature showcase
- **IMPLEMENTATION_SUMMARY.md** - Technical implementation details
- **PROJECT_SUMMARY.md** - Complete project overview

## Support

For issues or questions, please refer to the documentation and resources:
- [PHP Manual](https://www.php.net/manual/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Laravel Migration Documentation](https://laravel.com/docs/migrations)

---

**Made with ❤️ for modern database management**

Version 3.0 - Now with professional export/import features and Laravel migration generation!
