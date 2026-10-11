<?php

declare(strict_types=1);

/** Inline Font Awesome Free SVGs; attribution remains in the asset files. */
function faIcon(string $name, string $class = 'h-4 w-4'): string
{
    static $icons = [];
    $allowed = ['play', 'right-from-bracket', 'users', 'trophy', 'volume-high',
        'volume-xmark', 'rotate', 'shield-halved', 'crosshairs', 'check',
        'clock', 'arrow-right', 'bolt', 'bars', 'chevron-down', 'xmark',
        'arrow-left', 'arrow-up-right-from-square', 'circle-info',
        'triangle-exclamation', 'shield', 'chess-rook', 'pause', 'sun', 'moon'];

    if (!in_array($name, $allowed, true)) {
        return '';
    }

    if (!isset($icons[$name])) {
        $icons[$name] = file_get_contents(dirname(__DIR__, 2) . '/public/assets/icons/fontawesome/' . $name . '.svg');
    }

    return str_replace('<svg ', '<svg aria-hidden="true" focusable="false" fill="currentColor" class="fa-icon ' . e($class) . '" ', $icons[$name]);
}

/** Original geometric side icons, independent of team identity. */
function competitiveSideIcon(?string $side): string
{
    return match ($side) {'attack' => faIcon('crosshairs'), 'defense' => faIcon('shield'), default => ''};
}
