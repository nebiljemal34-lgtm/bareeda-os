#!/usr/bin/env php
<?php
/**
 * BLOCKER 2 FIX: CLI tool to create the first admin user.
 *
 * Usage: php bin/create-admin.php
 *
 * This replaces the removed admin seed from schema.sql.
 * The password is entered interactively (not echoed) and hashed with bcrypt cost 12.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

chdir(dirname(__DIR__));
define('APP_ENV', 'cli');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance();

// Check if any admin already exists
$existing = $db->fetchOne("SELECT id, email FROM users WHERE role = 'admin' LIMIT 1");
if ($existing) {
    fwrite(STDERR, "ERROR: Admin user already exists (id={$existing['id']}, email={$existing['email']}).\n");
    fwrite(STDERR, "To reset the password, use: php bin/reset-admin-password.php\n");
    exit(1);
}

echo "Create Admin User\n";
echo str_repeat('─', 40) . "\n\n";

$email = '';
while (empty($email)) {
    echo 'Email: ';
    $email = trim(fgets(STDIN));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "  Invalid email. Try again.\n";
        $email = '';
    }
}

$first = '';
while (empty($first)) {
    echo 'First name: ';
    $first = trim(fgets(STDIN));
}

$last = '';
while (empty($last)) {
    echo 'Last name: ';
    $last = trim(fgets(STDIN));
}

$phone = '';
echo 'Phone (optional): ';
$phone = trim(fgets(STDIN));

$password = '';
while (strlen($password) < PASSWORD_MIN_LENGTH) {
    echo 'Password (min ' . PASSWORD_MIN_LENGTH . ' chars): ';
    if (function_exists('readline')) {
        $password = readline('');
    } else {
        $password = trim(fgets(STDIN));
    }
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        echo "  Too short. Try again.\n";
        $password = '';
    }
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$id = $db->insert('users', [
    'email'         => $email,
    'password_hash' => $hash,
    'first_name'    => $first,
    'last_name'     => $last,
    'phone'         => $phone ?: null,
    'role'          => 'admin',
]);

echo "\n✓ Admin user created (id=$id).\n";
echo "  You can now log in at /login with $email\n";
