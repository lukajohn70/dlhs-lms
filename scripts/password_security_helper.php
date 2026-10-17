<?php
/**
 * DLHS Security Helper - Password Hashing & Transparent Migration
 * 
 * Provides safe verification of legacy plaintext passwords alongside modern
 * bcrypt/argon2id hashes, with automatic and transparent rehashing upon successful login.
 */

if (!defined('DLHS_SECURITY_HELPER_LOADED')) {
    define('DLHS_SECURITY_HELPER_LOADED', true);

    function dlhs_bcrypt_cost()
    {
        $cost = getenv('DLHS_BCRYPT_COST');
        if ($cost !== false && is_numeric($cost)) {
            $cost = (int)$cost;
            if ($cost >= 4 && $cost <= 12) {
                return $cost;
            }
        }
        return 8; // High-concurrency tuned cost for CBT exam environments (4x faster)
    }

    function dlhs_verify_and_rehash_password($inputPassword, $storedPassword, $onRehashCallback = null)
    {
        $inputPassword = (string)$inputPassword;
        $storedPassword = (string)$storedPassword;

        if ($inputPassword === '' || $storedPassword === '') {
            return false;
        }

        // Fast check: Is stored password a valid hash format ($2y$, $2a$, $2b$, or $argon2)?
        $isHash = (strncmp($storedPassword, '$2', 2) === 0 || strncmp($storedPassword, '$argon2', 7) === 0);

        if ($isHash) {
            // 1. Check if already a valid hash
            if (password_verify($inputPassword, $storedPassword)) {
                $cost = dlhs_bcrypt_cost();
                if (password_needs_rehash($storedPassword, PASSWORD_BCRYPT, array('cost' => $cost))) {
                    if (is_callable($onRehashCallback)) {
                        $newHash = dlhs_hash_password($inputPassword);
                        call_user_func($onRehashCallback, $newHash);
                    }
                }
                return true;
            }
            return false;
        }

        // 2. Fast check legacy plaintext fallback (avoids slow password_verify failure on non-hash)
        if (hash_equals($storedPassword, $inputPassword)) {
            // Transparently migrate plaintext password to modern hash on successful login
            if (is_callable($onRehashCallback)) {
                $newHash = dlhs_hash_password($inputPassword);
                call_user_func($onRehashCallback, $newHash);
            }
            return true;
        }

        return false;
    }

    function dlhs_hash_password($password)
    {
        $cost = dlhs_bcrypt_cost();
        return password_hash((string)$password, PASSWORD_BCRYPT, array('cost' => $cost));
    }
}
?>
