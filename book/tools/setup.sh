#!/usr/bin/env bash
# تجهيز أدوات بناء الكتاب (مرة واحدة لكل بيئة عمل):
#   - مكتبات Node (Chromium بدون واجهة + puppeteer-core + markdown-it + highlight.js + الخطوط)
#   - مكتبة pypdf (Python) لقراءة فهرس الـ PDF وترقيم الصفحات
# الاستعمال:  cd book && bash tools/setup.sh
set -euo pipefail
cd "$(dirname "$0")/.."
BT=${BOOK_TOOLS:-/tmp/booktools}
mkdir -p "$BT"

[ -d node_modules/@sparticuz/chromium ] || npm install --no-audit --no-fund

if [ ! -x "$BT/rt/chromium" ]; then
  mkdir -p "$BT/rt"
  TMPDIR="$BT/rt" node --input-type=module -e "
    import { inflate } from '@sparticuz/chromium';
    import { join } from 'node:path';
    const bin = join(process.cwd(), 'node_modules/@sparticuz/chromium/bin');
    for (const f of ['chromium.br', 'swiftshader.tar.br', 'al2023.tar.br']) await inflate(join(bin, f));
  "
fi

if [ ! -d "$BT/pylib/pypdfium2" ]; then
  python3 -m pip install -q --target "$BT/pylib" pypdf pypdfium2 pillow
fi

# الخطوط: نسخها إلى مجلد واحد + إعداد fontconfig حتى يجدها Chromium
mkdir -p "$BT/fonts"
FONTS=node_modules/@expo-google-fonts
cp -f $FONTS/noto-naskh-arabic/*/NotoNaskhArabic_*.ttf "$BT/fonts/"
cp -f $FONTS/noto-kufi-arabic/*/NotoKufiArabic_*.ttf "$BT/fonts/"
cp -f $FONTS/cairo/*/Cairo_*.ttf "$BT/fonts/"
cp -f $FONTS/noto-sans/400Regular/NotoSans_400Regular.ttf $FONTS/noto-sans/700Bold/NotoSans_700Bold.ttf \
      $FONTS/noto-sans/400Regular_Italic/NotoSans_400Regular_Italic.ttf "$BT/fonts/"
cp -f $FONTS/jetbrains-mono/400Regular/JetBrainsMono_400Regular.ttf $FONTS/jetbrains-mono/700Bold/JetBrainsMono_700Bold.ttf \
      $FONTS/jetbrains-mono/400Regular_Italic/JetBrainsMono_400Regular_Italic.ttf "$BT/fonts/"
cat > "$BT/fonts.conf" <<EOF
<?xml version="1.0"?>
<!DOCTYPE fontconfig SYSTEM "fonts.dtd">
<fontconfig>
  <dir>$BT/fonts</dir>
  <cachedir>$BT/fonts-cache</cachedir>
  <alias><family>sans-serif</family><prefer><family>Noto Sans</family><family>Noto Naskh Arabic</family></prefer></alias>
  <alias><family>serif</family><prefer><family>Noto Sans</family><family>Noto Naskh Arabic</family></prefer></alias>
  <alias><family>monospace</family><prefer><family>JetBrains Mono</family><family>Noto Naskh Arabic</family></prefer></alias>
</fontconfig>
EOF
echo "setup ok: $BT"
