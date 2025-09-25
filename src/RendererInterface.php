<?php

namespace CLI\Display;

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
