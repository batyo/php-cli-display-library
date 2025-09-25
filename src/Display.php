<?php

namespace CLI\Display;

class Display
{
    private RendererInterface $renderer;
    private ?FontDriverInterface $fontDriver;

    public function __construct(RendererInterface $renderer = null, ?FontDriverInterface $fontDriver = null)
    {
        $this->renderer = $renderer ?? new AnsiRenderer();
        $this->fontDriver = $fontDriver;
    }

    public function setRenderer(RendererInterface $r): void
    {
        $this->renderer = $r;
    }

    public function setFontDriver(FontDriverInterface $d): void
    {
        $this->fontDriver = $d;
    }

    /** シンプルなテキスト出力 */
    public function text(string $text, ?Style $style = null): void
    {
        echo $this->renderer->render($text, $style) . "\n";
    }

    /**
     * フォント付きヘッダー（外部フォントが使えない場合は大きくはならない）
     * fontName: FIGlet フォント名等
     */
    public function header(string $text, string $fontName = 'standard', ?Style $style = null): void
    {
        if ($this->fontDriver) {
            $art = $this->fontDriver->render($text, $fontName);
            if ($art !== null) {
                echo $this->renderer->render($art, $style) . "\n";
                return;
            }
        }
        // フォールバック: 大きな箱で囲む
        $len = (new AnsiRenderer())->strWidth($text) + 4;
        echo $this->renderer->render(str_repeat('-', $len), $style) . "\n";
        echo $this->renderer->render('| ' . $text . ' |', $style) . "\n";
        echo $this->renderer->render(str_repeat('-', $len), $style) . "\n";
    }

    /** ボックス描画（日本語幅に対応） */
    public function box(string $text, int $width = 0, ?Style $style = null): void
    {
        $r = $this->renderer;
        $ar = new AnsiRenderer();
        if ($width <= 0) {
            $width = $ar->strWidth($text) + 4;
        }
        $top = '┌' . str_repeat('─', $width - 2) . '┐';
        $bottom = '└' . str_repeat('─', $width - 2) . '┘';
        echo $r->render($top, $style) . "\n";
        $lines = explode("\n", $ar->wrap($text, $width - 4));
        foreach ($lines as $ln) {
            $pad = $width - 2 - $ar->strWidth($ln);
            echo $r->render('│ ' . $ln . str_repeat(' ', $pad - 1) . '│', $style) . "\n";
        }
        echo $r->render($bottom, $style) . "\n";
    }

    /**
     * 簡易テーブル表示（2次元配列）
     * @param array $rows テーブルデータ（2次元配列）
     * @param Style|null $style スタイル
     * @param bool $hasHeader 先頭行をヘッダーとして扱うか
     */
    public function table(array $rows, ?Style $style = null, bool $hasHeader = false): void
    {
        $renderer = $this->renderer;
        $ar = new AnsiRenderer();
        // カラム幅計算
        $widths = [];
        foreach ($rows as $r) {
            foreach ($r as $i => $c) {
                $w = $ar->strWidth((string)$c);
                if (!isset($widths[$i]) || $w > $widths[$i]) $widths[$i] = $w;
            }
        }
        $rowIndex = 0;
        foreach ($rows as $r) {
            $line = '';
            foreach ($r as $i => $c) {
                $s = (string)$c;
                $pad = $widths[$i] - $ar->strWidth($s);
                $line .= ' ' . $s . str_repeat(' ', $pad) . ' |';
            }
            $line = rtrim($line, ' |');
            echo $renderer->render($line, $style) . "\n";
            // ヘッダー行の下に区切り線を出力
            if ($hasHeader && $rowIndex === 0) {
                $sep = '';
                foreach ($widths as $w) {
                    $sep .= ' ' . str_repeat('-', $w) . ' |';
                }
                $sep = rtrim($sep, ' |');
                echo $renderer->render($sep, $style) . "\n";
            }
            $rowIndex++;
        }
    }

    /** ユーティリティ：色付きテキストを返す */
    public function colorText(string $text, string $color, ?string $bg = null, bool $bold = false): string
    {
        $style = new Style($color, $bg, $bold);
        return $this->renderer->render($text, $style);
    }
}
