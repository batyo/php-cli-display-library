# PHP CLI Display Library

日本語（マルチバイト文字）に特化したPHP用CLI表示ライブラリです。  
ANSIカラー、文字装飾、フォント切り替え、エンコード自動判別とUTF-8統一対応を備えています。

---

## 特徴

- **日本語対応**：`mb_strwidth`による全角・半角文字幅計算
- **エンコード自動判別＆UTF-8統一**：`EncodingHelper`で自動変換
- **ANSIカラー・装飾**：前景色・背景色・太字・下線に対応
- **フォント切り替え**：`FontDriverInterface`で拡張可能（FIGlet等）
- **シンプルな単一ファイル構成**：Composerオートロードもサポート

---

## インストール

ComposerでPSR-4オートロードに対応しています。

```json
{
  "autoload": {
    "psr-4": {
      "CLI\\Display\\": "src/"
    }
  }
}
```

---

## 使い方

### 基本例

```php
use CLI\Display\AnsiRenderer;
use CLI\Display\Style;

$renderer = new AnsiRenderer();
$style = new Style('bright_green', null, true);
echo $renderer->render('こんにちは、世界！', $style) . "\n";
```

### Displayクラスによる高機能表示

```php
use CLI\Display\Display;
use CLI\Display\AnsiRenderer;
use CLI\Display\ExternalFigletFontDriver;
use CLI\Display\Style;

$d = new Display(new AnsiRenderer(), new ExternalFigletFontDriver());
$d->header('サンプル', 'standard', new Style('cyan', null, true));
$d->box("日本語のボックス表示をテストします。全角幅にも対応しています。", 40, new Style('green'));
$d->text($d->colorText('赤文字の例', 'red'));
```

---

## エンコード自動判別・文字幅取得

```php
use CLI\Display\EncodingHelper;

$text = "テスト"; // Shift_JISでもOK
$utf8 = EncodingHelper::normalizeEncoding($text);
$width = EncodingHelper::getDisplayWidth($utf8);
```

---

## 利用できる色名

- `black, red, green, yellow, blue, magenta, cyan, white`
- `bright_black, bright_red, bright_green, bright_yellow, bright_blue, bright_magenta, bright_cyan, bright_white`

---

## フォント拡張

`FontDriverInterface`を実装することで独自フォント描画機能を追加できます。

```php
class MyFontDriver implements CLI\Display\FontDriverInterface {
  public function render(string $text, string $fontName): ?string {
    return "*** $text ***";
  }
}
```

---

## ライセンス

MIT License