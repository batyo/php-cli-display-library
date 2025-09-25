<?php

namespace CLI\Display;

class ExternalFigletFontDriver implements FontDriverInterface
{
    private ?string $figletCmd;

    public function __construct(?string $figletCmd = null)
    {
        // 指定がなければ 'figlet' を探す
        $this->figletCmd = $figletCmd ?? 'figlet';
    }

    public function render(string $text, string $fontName): ?string
    {
        // figlet が存在するかチェック
        $cmd = escapeshellcmd($this->figletCmd) . ' -f ' . escapeshellarg($fontName) . ' ' . escapeshellarg($text) . ' 2>/dev/null';
        $output = null;
        $return = null;
        @exec($cmd, $output, $return);
        if ($return === 0 && is_array($output)) {
            return implode("\n", $output);
        }
        return null;
    }
}
