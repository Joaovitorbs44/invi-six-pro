<?php
declare(strict_types=1);

/** PostgreSQL CURRENT_TIMESTAMP and all runtime writers use UTC. Explicit offsets are preserved. */
function brazilDate(?string $value, string $format = 'd/m/Y H:i'): string
{
    if (!$value) return '—';
    try {
        // Calendar-only dates have no instant/timezone to convert.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('America/Sao_Paulo'));
            return $date ? $date->format('d/m/Y') : '—';
        }
        return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format($format);
    } catch (Throwable) { return '—'; }
}

/** Allow only view routes, never external origins or mutation endpoints. */
function safeLoginDestination(mixed $value): ?string
{
    if (is_string($value) && $value === '/partidas') return $value;
    if (!is_string($value) || strlen($value)>300 || preg_match('/[\x00-\x20\\\\]/', $value)) return null;
    if (!preg_match('#^/(?:(?:match|partida)/[1-9][0-9]*(?:\?view=maps)?|matchmaking|ranking|perfil|support(?:\?ticket=[1-9][0-9]*)?|admin(?:/(?:support|matches|seasons|punishments))?)$#D', $value)) return null;
    return $value;
}

/** Internal history destinations only; never accept arbitrary redirect URLs. */
function matchHistoryReturn(mixed $value): string
{
    if (!is_string($value) || strlen($value)>200) return '/partidas';
    if (preg_match('#^/(?:partidas|admin/matches)(?:\?status=[a-z_]*&page=[1-9][0-9]{0,5})?$#D', $value)) return $value;
    return '/partidas';
}
