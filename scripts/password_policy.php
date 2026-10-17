<?php
function dlhsPasswordPolicyMessage($password, $profile = 'staff')
{
    $password = (string) $password;
    $profile = (string) $profile;
    $lowerPassword = strtolower($password);

    // Profile-specific basic length and char types
    if ($profile === 'student') {
        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters long.';
        }
        if (!preg_match('/[A-Za-z]/', $password)) {
            return 'Password must include at least one letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must include at least one number.';
        }
        $blockedDefaults = array('password', 'student', '123456', '1234', '4321');
    } else {
        // Staff/Admin policy
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must include at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must include at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must include at least one number.';
        }
        $blockedDefaults = array('1234', '4321', 'password', 'admin');
    }

    // Common blocked defaults check
    foreach ($blockedDefaults as $blockedDefault) {
        if (strpos($lowerPassword, $blockedDefault) !== false) {
            return 'Password cannot contain common defaults or simple sequences like 1234 or 4321.';
        }
    }

    // Common repeating characters check (e.g. 1111)
    if (preg_match('/(.)\1{3,}/', $password)) {
        return 'Password cannot contain repeating characters like 1111 or 2222.';
    }

    // Common ascending/descending sequences (e.g. abcd, 1234)
    $length = strlen($lowerPassword);
    for ($i = 0; $i <= $length - 4; $i++) {
        $chunk = substr($lowerPassword, $i, 4);
        if (!ctype_alnum($chunk)) {
            continue;
        }

        $ascending = true;
        $descending = true;
        for ($j = 1; $j < 4; $j++) {
            $previous = ord($chunk[$j - 1]);
            $current = ord($chunk[$j]);
            if ($current !== $previous + 1) {
                $ascending = false;
            }
            if ($current !== $previous - 1) {
                $descending = false;
            }
        }

        if ($ascending || $descending) {
            return 'Password cannot contain ascending or descending sequences (e.g. 1234 or abcd).';
        }
    }

    return '';
}

function dlhsPasswordMeetsPolicy($password, $profile = 'staff')
{
    return dlhsPasswordPolicyMessage($password, $profile) === '';
}
?>
