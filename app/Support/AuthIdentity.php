<?php

namespace App\Support;

/**
 * Users authenticate with a username; Laravel still stores a unique email.
 * The domain is never shown in the UI.
 */
final class AuthIdentity
{
    public static function domain(): string
    {
        return (string) config('auth.username_domain');
    }

    public static function emailFromUsername(string $username): string
    {
        $username = strtolower(trim($username));
        $username = str_contains($username, '@')
            ? explode('@', $username, 2)[0]
            : $username;

        return $username.'@'.self::domain();
    }

    public static function usernameFromEmail(?string $email): string
    {
        if ($email === null || $email === '') {
            return '';
        }

        return explode('@', $email, 2)[0];
    }
}
