<?php

/**
 * PHP CLI Display Library
 *
 * - 日本語（UTF-8）に特化したCLI表示ユーティリティ
 * - 好きなフォント（標準 / FIGlet / カスタムドライバ）を選択可能
 * - 組み込みが容易（1ファイルで完結、ネームスペース対応、Composerでのautoloadにも対応）
 * - 複数の文字色（ANSI）をサポート
 * - 将来の拡張を見据えたインタフェース駆動設計
 */

namespace CLI\Display;

mb_internal_encoding('UTF-8');

// -----------------------------
// インターフェース群
// -----------------------------

interface RendererInterface
{
    /**
     * 指定されたテキストとスタイルでレンダリングし、端末出力文字列を返す
     * @param string $text
     * @param Style|null $style
     * @return string
     */
    public function render(string $text, ?Style $style = null): string;
}

interface FontDriverInterface
{
    /**
     * 指定テキストを指定フォントでレンダリング（大きなアスキーアート等）
     * フォントが使えない場合は null を返す（フォールバックあり）
     * @param string $text
     * @param string $fontName
     * @return string|null
     */
    public function render(string $text, string $fontName): ?string;
}


// -----------------------------
// 文字エンコードと幅計算のヘルパー
// -----------------------------

class EncodingHelper
{
    /**
    * テキストのエンコードを自動判別してUTF-8に変換
    */
    public static function normalizeEncoding(string $text): string
    {
        $enc = mb_detect_encoding($text, ['UTF-8','SJIS','EUC-JP','ISO-2022-JP','CP932'], true);
        if ($enc && strtoupper($enc) !== 'UTF-8') {
            return mb_convert_encoding($text, 'UTF-8', $enc);
        }
        return $text;
    }


    /**
    * UTF-8としての文字幅を返す（全角2/半角1）
    */
    public static function getDisplayWidth(string $text): int
    {
        $text = self::normalizeEncoding($text);
        return mb_strwidth($text, 'UTF-8');
    }
}

// -----------------------------
// スタイル定義
// -----------------------------

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

// -----------------------------
// ANSIカラーマップ
// -----------------------------

class Colors
{
    // シンプルな名前 -> ANSIコード
    public const ANSI_MAP = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37,
        // 明るい色
        'bright_black' => 90,
        'bright_red' => 91,
        'bright_green' => 92,
        'bright_yellow' => 93,
        'bright_blue' => 94,
        'bright_magenta' => 95,
        'bright_cyan' => 96,
        'bright_white' => 97,
    ];

    public const RESET = "\e[0m";
}

// -----------------------------
// FIGlet を使ったフォントドライバ（外部コマンド figlet / toilet を利用）
// 拡張可能な FontDriver の一例
// -----------------------------

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

// -----------------------------
// ANSI レンダラー（デフォルト）
// -----------------------------

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

// -----------------------------
// 中心クラス：Display
// -----------------------------

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
