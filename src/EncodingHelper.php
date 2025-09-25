<?php

namespace CLI\Display;

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
