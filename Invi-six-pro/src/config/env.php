<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

/**
 * Carrega as variáveis de ambiente da aplicação.
 *
 * Desenvolvimento: lê o arquivo .env.
 * Produção: utiliza as variáveis configuradas na Vercel.
 *
 * Não depende do Composer.
 */
final class Env
{
    public static function load(string $root): void
    {
        $file = $root . '/.env';

        // Na Vercel, o arquivo .env não precisa existir.
        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException(
                'Não foi possível ler o arquivo .env.'
            );
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignora linhas vazias e comentários.
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Separa o nome da variável de seu valor.
            if (!str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name = trim($name);
            $value = trim($value);

            // Aceita nomes válidos de variáveis.
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
                continue;
            }

            // Remove aspas simples ou duplas ao redor do valor.
            if (
                strlen($value) >= 2 &&
                (
                    ($value[0] === '"' && str_ends_with($value, '"')) ||
                    ($value[0] === "'" && str_ends_with($value, "'"))
                )
            ) {
                $value = substr($value, 1, -1);
            }

            // Variáveis reais do servidor têm prioridade.
            if (getenv($name) !== false || isset($_ENV[$name])) {
                continue;
            }

            $_ENV[$name] = $value;
        }
    }

    /**
     * Recupera uma configuração pelo nome.
     */
    public static function get(
        string $name,
        string $default = ''
    ): string {
        $value = getenv($name);

        if ($value !== false) {
            return $value;
        }

        return $_ENV[$name] ?? $default;
    }
}
