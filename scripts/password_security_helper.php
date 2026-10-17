<?php
/**
 * DLHS Security Helper - Password Hashing & Transparent Migration
 * 
 * Provides safe verification of legacy plaintext passwords alongside modern
 * bcrypt/argon2id hashes, with automatic and transparent rehashing upon successful login.
 */

function dlhs_verify_and_rehash_password($inputPassword, $storedPassword, $onRehashCallback = null)
{
    $inputPassword = (string)$inputPassword;
    $storedPassword = (string)$storedPassword;

    if ($inputPassword === '' || $storedPassword === '') {
        return false;
    }

    // 1. Check if already a valid hash
    if (password_verify($inputPassword, $storedPassword)) {
        if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
            if (is_callable($onRehashCallback)) {
                $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
                call_user_func($onRehashCallback, $newHash);
            }
        }
        return true;
    }

    // 2. Check legacy plaintext fallback
    if (hash_equals($storedPassword, $inputPassword)) {
        // Transparently migrate plaintext password to modern hash on successful login
        if (is_callable($onRehashCallback)) {
            $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
            call_user_func($onRehashCallback, $newHash);
        }
        return true;
    }

    return false;
}

function dlhs_hash_password($password)
{
    return password_hash((string)$password, PASSWORD_DEFAULT);
}
?>
