<?php
/**
 * Database Manager Configuration
 */

// ============================================================
// SERVERS — add/remove as needed; key is used as server ID
// ============================================================
$GLOBALS['DB_SERVERS'] = [
    'local' => [
        'label'    => 'Local Server',
        'host'     => 'localhost',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'production' => [
        'label'    => 'Production',
        'host'     => '',
        'user'     => '',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
];

// ============================================================
// USERS — session-based auth (no database required)
// Generate hash: php -r "echo password_hash('yourpass', PASSWORD_BCRYPT);"
// Default credentials: admin / admin123 | viewer / manager
// ============================================================
$GLOBALS['APP_USERS'] = [
    'admin' => [
        'password' => '$2y$10$B8lUllOWmzk1pJLwgFGny.obb6sjgCjSygayiX5Q/GT5osKWwP9bS',
        'name'     => 'Administrator',
        'role'     => 'admin',
    ],
    'viewer' => [
        'password' => '$2y$10$Mxctt90r4fAY/quw6pqijuM47guegjJXJQ9hic/yKLigycACY0J3C',
        'name'     => 'Viewer',
        'role'     => 'viewer',
    ],
];

// Application settings
define('ROWS_PER_PAGE', 50);
define('MAX_QUERY_LENGTH', 10000);
define('SESSION_TIMEOUT', 3600);
define('BACKUP_DIR', __DIR__ . '/storage/backups');

// Feature flags
define('ALLOW_DELETE', true);
define('ALLOW_INLINE_EDIT', true);
define('ALLOW_SQL_QUERIES', true);
