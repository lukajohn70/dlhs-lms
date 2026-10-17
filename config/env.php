<?php
/**
 * Lightweight environment loader for DLHS.
 *
 * Real server environment variables always win. If they are not present,
 * values are loaded from the app-root .env file.
 */

if (!defined('DLHS_ENV_LOADED')) {
    define('DLHS_ENV_LOADED', true);

    function dlhs_env_has_value($key)
    {
        return getenv($key) !== false || array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER);
    }

    function dlhs_set_env_value($key, $value)
    {
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }

    function dlhs_load_env_file($path)
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, 'export ') === 0) {
                $line = trim(substr($line, 7));
            }

            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separator));
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            if (dlhs_env_has_value($key)) {
                continue;
            }

            $value = trim(substr($line, $separator + 1));
            $quote = $value !== '' ? $value[0] : '';

            if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
                $value = substr($value, 1, -1);
                if ($quote === '"') {
                    $value = strtr($value, array(
                        '\\n' => "\n",
                        '\\r' => "\r",
                        '\\t' => "\t",
                        '\\"' => '"',
                        '\\\\' => '\\',
                    ));
                }
            }

            dlhs_set_env_value($key, $value);
        }
    }

    function dlhs_raw_env_value($key, $default = null)
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }

    function dlhs_normalize_environment_profile($profile)
    {
        $normalized = strtolower(trim((string) $profile));

        if (in_array($normalized, array('mac', 'macos', 'darwin', 'mamp'), true)) {
            return 'MAC';
        }

        if (in_array($normalized, array('windows', 'win', 'wamp'), true)) {
            return 'WINDOWS';
        }

        if (in_array($normalized, array('ubuntu', 'linux', 'server', 'production'), true)) {
            return 'UBUNTU';
        }

        return '';
    }

    function dlhs_detect_environment_profile()
    {
        $requested = dlhs_raw_env_value('DLHS_ENVIRONMENT', dlhs_raw_env_value('DLHS_ENV', 'auto'));
        $requested = strtolower(trim((string) $requested));

        if ($requested !== '' && $requested !== 'auto') {
            $profile = dlhs_normalize_environment_profile($requested);
            if ($profile !== '') {
                return $profile;
            }
        }

        $osFamily = defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY : PHP_OS;
        $osFamily = strtolower((string) $osFamily);

        if (strpos($osFamily, 'windows') !== false || strpos($osFamily, 'win') === 0) {
            return 'WINDOWS';
        }

        if (strpos($osFamily, 'darwin') !== false || strpos($osFamily, 'mac') !== false) {
            return 'MAC';
        }

        return 'UBUNTU';
    }

    function dlhs_apply_environment_profile()
    {
        $profile = dlhs_detect_environment_profile();
        dlhs_set_env_value('DLHS_ACTIVE_ENVIRONMENT', strtolower($profile));

        $targets = array(
            'DLHS_APP_ENV' => 'APP_ENV',
            'DLHS_DISPLAY_ERRORS' => 'DISPLAY_ERRORS',
            'DLHS_TIMEZONE' => 'TIMEZONE',
            'DLHS_DB_HOST' => 'DB_HOST',
            'DLHS_DB_PORT' => 'DB_PORT',
            'DLHS_DB_NAME' => 'DB_NAME',
            'DLHS_DB_USER' => 'DB_USER',
            'DLHS_DB_PASS' => 'DB_PASS',
            'DLHS_DB_PASSWORD' => 'DB_PASSWORD',
            'DLHS_DB_SOCKET' => 'DB_SOCKET',
            'DLHS_DB_CHARSET' => 'DB_CHARSET',
            'DLHS_DB_PERSISTENT' => 'DB_PERSISTENT',
            'DLHS_DB_STRICT' => 'DB_STRICT',
            'DLHS_OPTIMIZED_DB_PERSISTENT' => 'OPTIMIZED_DB_PERSISTENT',
            'DLHS_MYSQLDUMP_PATH' => 'MYSQLDUMP_PATH',
            'DLHS_7ZIP_PATH' => '7ZIP_PATH',
        );

        foreach ($targets as $target => $suffix) {
            if (dlhs_env_has_value($target)) {
                continue;
            }

            $source = 'DLHS_' . $profile . '_' . $suffix;
            if (dlhs_env_has_value($source)) {
                dlhs_set_env_value($target, dlhs_raw_env_value($source, ''));
            }
        }
    }

    $appRoot = dirname(__DIR__);
    $explicitEnvFile = getenv('DLHS_ENV_FILE');

    if ($explicitEnvFile !== false && $explicitEnvFile !== '') {
        dlhs_load_env_file($explicitEnvFile);
    } else {
        dlhs_load_env_file($appRoot . '/.env');
    }

    dlhs_apply_environment_profile();

    if (!dlhs_env_has_value('APP_ENV') && dlhs_env_has_value('DLHS_APP_ENV')) {
        dlhs_set_env_value('APP_ENV', getenv('DLHS_APP_ENV'));
    }

    if (!dlhs_env_has_value('DLHS_APP_ENV') && dlhs_env_has_value('APP_ENV')) {
        dlhs_set_env_value('DLHS_APP_ENV', getenv('APP_ENV'));
    }

    function dlhs_env($key, $default = null)
    {
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        return $default;
    }

    function dlhs_env_first(array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $value = dlhs_env($key, null);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    function dlhs_env_bool($key, $default = false)
    {
        $value = dlhs_env($key, null);
        if ($value === null || $value === '') {
            return (bool) $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, array('1', 'true', 'yes', 'on'), true)) {
            return true;
        }

        if (in_array($normalized, array('0', 'false', 'no', 'off'), true)) {
            return false;
        }

        return (bool) $default;
    }

    function dlhs_env_int($key, $default = 0)
    {
        $value = dlhs_env($key, null);
        if ($value === null || $value === '' || !is_numeric($value)) {
            return (int) $default;
        }

        return (int) $value;
    }
}
