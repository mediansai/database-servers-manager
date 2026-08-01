<?php
/**
 * Session-based Authentication (no database required)
 */
class Auth {

    /**
     * Compute the application's base URL path dynamically.
     * Works regardless of where the project is installed.
     */
    private static function basePath(): string {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        // Walk up from includes/ or handlers/ sub-dirs to project root
        $projectRoot = str_replace('\\', '/', dirname(__DIR__));
        $docRoot     = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $base        = '/' . trim(str_replace($docRoot, '', $projectRoot), '/');
        return rtrim($base, '/');
    }

    /** Redirect to login if not authenticated */
    public static function require(): void {
        if (!self::check()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . self::basePath() . '/login.php?redirect=' . $redirect);
            exit;
        }
        // Auto-logout on session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            self::logout();
            header('Location: ' . self::basePath() . '/login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    /** Return true when a valid user session exists */
    public static function check(): bool {
        return !empty($_SESSION['auth_user']);
    }

    /** Attempt login; returns true on success */
    public static function login(string $username, string $password): bool {
        require_once __DIR__ . '/../config.php';
        $users = $GLOBALS['APP_USERS'] ?? [];

        if (!isset($users[$username])) {
            return false;
        }
        if (!password_verify($password, $users[$username]['password'])) {
            return false;
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['auth_user']     = $username;
        $_SESSION['auth_name']     = $users[$username]['name'];
        $_SESSION['auth_role']     = $users[$username]['role'];
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time']    = time();

        return true;
    }

    /** Destroy session and log out */
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Current username or null */
    public static function user(): ?string {
        return $_SESSION['auth_user'] ?? null;
    }

    /** Current display name or null */
    public static function name(): ?string {
        return $_SESSION['auth_name'] ?? null;
    }

    /** Current role or null */
    public static function role(): ?string {
        return $_SESSION['auth_role'] ?? null;
    }

    /** True when logged-in user has admin role */
    public static function isAdmin(): bool {
        return self::role() === 'admin';
    }
}
