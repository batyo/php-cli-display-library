<?php

namespace CLI\Display;

class Style
{
    public ?string $color = null;
    public ?string $bgColor = null;
    public bool $bold = false;
    public bool $underline = false;

    public function __construct(?string $color = null, ?string $bgColor = null, bool $bold = false, bool $underline = false)
    {
        $this->color = $color;
        $this->bgColor = $bgColor;
        $this->bold = $bold;
        $this->underline = $underline;
    }

    public static function ansiCode(?string $color, ?string $bg = null, bool $bold = false, bool $underline = false): string
    {
        $codes = [];
        if ($bold) $codes[] = '1';
        if ($underline) $codes[] = '4';
        $map = Colors::ANSI_MAP;
        if ($color !== null && isset($map[$color])) $codes[] = (string)$map[$color];
        if ($bg !== null && isset($map[$bg])) $codes[] = (string)($map[$bg] + 10); // bg codes
        return $codes ? "\e[".implode(';', $codes)."m" : '';
    }
}
