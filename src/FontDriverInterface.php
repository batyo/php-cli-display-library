<?php

namespace CLI\Display;

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
