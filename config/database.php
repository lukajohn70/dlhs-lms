<?php
require_once __DIR__ . '/env.php';

if (!function_exists('dlhs_database_config')) {
    function dlhs_unique_values(array $values)
    {
        $unique = array();

        foreach ($values as $value) {
            $key = (string) $value;
            if ($key === '' && $value !== '') {
                continue;
            }

            if (!array_key_exists($key, $unique)) {
                $unique[$key] = $value;
            }
        }

        return array_values($unique);
    }

    function dlhs_database_config()
    {
        $appEnv = dlhs_env_first(array('DLHS_APP_ENV', 'APP_ENV'), 'local');

        return array(
            'app_env' => $appEnv,
            'host' => dlhs_env_first(array('DLHS_DB_HOST', 'DB_HOST', 'MYSQL_HOST'), 'localhost'),
            'user' => dlhs_env_first(array('DLHS_DB_USER', 'DB_USERNAME', 'MYSQL_USER'), 'root'),
            'password' => dlhs_env_first(array('DLHS_DB_PASS', 'DLHS_DB_PASSWORD', 'DB_PASSWORD', 'MYSQL_PASSWORD'), ''),
            'database' => dlhs_env_first(array('DLHS_DB_NAME', 'DB_DATABASE', 'MYSQL_DATABASE'), 'deeper_life'),
            'port' => dlhs_env_int('DLHS_DB_PORT', dlhs_env_int('DB_PORT', 3306)),
            'socket' => dlhs_env_first(array('DLHS_DB_SOCKET', 'DB_SOCKET', 'MYSQL_SOCKET'), ''),
            'charset' => dlhs_env_first(array('DLHS_DB_CHARSET', 'DB_CHARSET'), 'utf8mb4'),
            'timezone' => dlhs_env_first(array('DLHS_TIMEZONE', 'APP_TIMEZONE'), 'Africa/Lagos'),
            'display_errors' => dlhs_env_bool('DLHS_DISPLAY_ERRORS', $appEnv !== 'production'),
            'persistent' => dlhs_env_bool('DLHS_DB_PERSISTENT', false),
            'strict' => dlhs_env_bool('DLHS_DB_STRICT', false),
        );
    }

    function dlhs_apply_runtime_config()
    {
        $config = dlhs_database_config();

        ini_set('display_errors', $config['display_errors'] ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting($config['display_errors'] ? E_ALL : 0);
        date_default_timezone_set($config['timezone']);

        return $config;
    }

    function dlhs_database_name_candidates($preferredDatabase)
    {
        $configured = dlhs_env_first(array('DLHS_DB_CANDIDATES', 'DB_CANDIDATES'), '');
        $names = array($preferredDatabase);

        if ($configured !== '') {
            foreach (explode(',', $configured) as $name) {
                $names[] = trim($name);
            }
        }

        $names = array_merge($names, array(
            'deeper_life',
            'dlhs',
            'deeperlife',
            'deeper_life_dump',
            'deeper_life_backup',
        ));

        return dlhs_unique_values(array_filter($names, 'strlen'));
    }

    function dlhs_database_candidate_configs(array $config, $usePersistent)
    {
        $hostCandidates = array($config['host']);
        $isLocalHost = in_array($config['host'], array('localhost', '127.0.0.1', '::1', ''), true);

        if (!$config['strict'] && $isLocalHost) {
            $hostCandidates[] = '127.0.0.1';
            $hostCandidates[] = 'localhost';
        }

        $portCandidates = array((int) $config['port']);
        if (!$config['strict'] && $isLocalHost) {
            $portCandidates[] = 3306;
            $portCandidates[] = 8889;
        }

        $credentialCandidates = array(
            array($config['user'], $config['password']),
        );

        if (!$config['strict'] && $isLocalHost) {
            $credentialCandidates[] = array('dlhs_user', $config['password']);
            $credentialCandidates[] = array('root', '');
            $credentialCandidates[] = array('root', 'root');
        }

        $hostCandidates = dlhs_unique_values(array_filter($hostCandidates, 'strlen'));
        $portCandidates = dlhs_unique_values($portCandidates);
        $databaseCandidates = $config['strict']
            ? array($config['database'])
            : dlhs_database_name_candidates($config['database']);
        $seenCredentials = array();
        $credentials = array();

        foreach ($credentialCandidates as $credential) {
            $key = $credential[0] . "\n" . $credential[1];
            if (!isset($seenCredentials[$key])) {
                $seenCredentials[$key] = true;
                $credentials[] = $credential;
            }
        }

        $candidates = array();
        foreach ($hostCandidates as $host) {
            if ($usePersistent && strpos($host, 'p:') !== 0) {
                $host = 'p:' . $host;
            }

            foreach ($portCandidates as $port) {
                foreach ($credentials as $credential) {
                    foreach ($databaseCandidates as $database) {
                        $candidates[] = array(
                            'host' => $host,
                            'display_host' => preg_replace('/^p:/', '', $host),
                            'user' => $credential[0],
                            'password' => $credential[1],
                            'database' => $database,
                            'port' => (int) $port,
                            'socket' => $config['socket'] !== '' ? $config['socket'] : null,
                            'charset' => $config['charset'],
                        );
                    }
                }
            }
        }

        return $candidates;
    }

    function dlhs_connect_with_candidate(array $candidate, &$error)
    {
        $connection = mysqli_init();
        if (!$connection) {
            $error = 'Unable to initialize mysqli.';
            return null;
        }

        if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
            $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        }

        $connected = @$connection->real_connect(
            $candidate['host'],
            $candidate['user'],
            $candidate['password'],
            $candidate['database'],
            $candidate['port'],
            $candidate['socket']
        );

        if (!$connected) {
            $error = $connection->connect_error ?: mysqli_connect_error();
            return null;
        }

        $connection->set_charset($candidate['charset']);
        $charset = $connection->real_escape_string($candidate['charset']);
        $connection->query("SET NAMES '{$charset}'");
        $connection->query("SET CHARACTER SET '{$charset}'");

        return $connection;
    }

    function dlhs_try_database_connection($persistent = null, &$lastError = null)
    {
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        $config = dlhs_apply_runtime_config();
        $usePersistent = $persistent === null ? $config['persistent'] : (bool) $persistent;
        $candidates = dlhs_database_candidate_configs($config, $usePersistent);
        $attempts = array();

        foreach ($candidates as $candidate) {
            $error = null;
            $connection = dlhs_connect_with_candidate($candidate, $error);

            if ($connection instanceof mysqli) {
                if (
                    $candidate['display_host'] !== $config['host'] ||
                    $candidate['user'] !== $config['user'] ||
                    $candidate['database'] !== $config['database'] ||
                    (int) $candidate['port'] !== (int) $config['port']
                ) {
                    error_log(sprintf(
                        'Database connection recovered using %s@%s:%s/%s',
                        $candidate['user'],
                        $candidate['display_host'],
                        $candidate['port'],
                        $candidate['database']
                    ));
                }

                return $connection;
            }

            $attempts[] = sprintf(
                '%s@%s:%s/%s (%s)',
                $candidate['user'],
                $candidate['display_host'],
                $candidate['port'],
                $candidate['database'],
                $error ?: 'unknown error'
            );
        }

        $lastError = implode('; ', $attempts);
        error_log('Database connection failed after trying configured/fallback environments: ' . $lastError);

        return null;
    }

    function dlhs_create_database_connection($persistent = null)
    {
        $lastError = null;
        $connection = dlhs_try_database_connection($persistent, $lastError);

        if ($connection instanceof mysqli) {
            return $connection;
        }

        $config = dlhs_database_config();
        if ($config['display_errors']) {
            die('Database connection failed: ' . htmlspecialchars($lastError, ENT_QUOTES, 'UTF-8'));
        }

        die('Database connection failed. Please contact the system administrator.');
    }
}
