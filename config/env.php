<?php

/**
 * Loads key/value environment variables from a .env file.
 *
 * Rules:
 * - Ignores blank lines and comment lines starting with # or ;
 * - Supports optional "export " prefix
 * - Supports quoted values: KEY="value", KEY='value'
 * - By default, does not override variables that already exist in server env
 */
function load_env_file(string $filePath, bool $override = false): void
{
    if (!is_file($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $separatorPos = strpos($line, '=');
        if ($separatorPos === false) {
            continue;
        }

        $name = trim(substr($line, 0, $separatorPos));
        $value = trim(substr($line, $separatorPos + 1));

        if ($name === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            continue;
        }

        if (!$override) {
            $alreadySet = getenv($name) !== false || array_key_exists($name, $_ENV) || array_key_exists($name, $_SERVER);
            if ($alreadySet) {
                continue;
            }
        }

        if ($value !== '') {
            $isDoubleQuoted = str_starts_with($value, '"') && str_ends_with($value, '"');
            $isSingleQuoted = str_starts_with($value, "'") && str_ends_with($value, "'");

            if ($isDoubleQuoted || $isSingleQuoted) {
                $value = substr($value, 1, -1);
                if ($isDoubleQuoted) {
                    $value = stripcslashes($value);
                }
            } else {
                // Strip trailing inline comments for unquoted values.
                $hashPos = strpos($value, ' #');
                if ($hashPos !== false) {
                    $value = rtrim(substr($value, 0, $hashPos));
                }
            }
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

