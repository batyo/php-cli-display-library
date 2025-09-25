<?php

namespace CLI\Display;

class AnsiRenderer implements RendererInterface
{
    /** @var bool マルチバイト文字幅計算に mb_strwidth を使用 */
    private bool $useMbStrWidth = true;

    public function render(string $text, ?Style $style = null): string
    {
        $prefix = '';
        $suffix = '';
        if ($style) {
            $prefix = Style::ansiCode($style->color, $style->bgColor, $style->bold, $style->underline);
            $suffix = Colors::RESET;
        }
        return $prefix . $text . $suffix;
    }

    /**
     * 指定幅に合わせてテキストをセンタリング（全角文字幅考慮）
     */
    public function center(string $text, int $width): string
    {
        $w = $this->strWidth($text);
        if ($w >= $width) return $text;
        $pad = $width - $w;
        $left = intdiv($pad, 2);
        $right = $pad - $left;
        return str_repeat(' ', $left) . $text . str_repeat(' ', $right);
    }

    public function strWidth(string $s): int
    {
        if ($this->useMbStrWidth && function_exists('mb_strwidth')) {
            return mb_strwidth($s, 'UTF-8');
        }
        return strlen($s);
    }

    /**
     * 幅に合わせて折り返し（簡易）
     */
    public function wrap(string $s, int $width): string
    {
        $out = '';
        $line = '';
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $ch) {
            $line .= $ch;
            if ($this->strWidth($line) >= $width) {
                $out .= $line . "\n";
                $line = '';
            }
        }
        if ($line !== '') $out .= $line;
        return $out;
    }
}
