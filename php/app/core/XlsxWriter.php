<?php
/**
 * كاتب ملفات Excel (xlsx) مصغّر بدون مكتبات خارجية (بديل openpyxl).
 * يدعم: الألوان، الخط العريض، المحاذاة، الحدود، عرض الأعمدة، ارتفاع الصفوف، الاتجاه من اليمين لليسار.
 * يتطلب امتداد ZipArchive (مفعّل افتراضياً في XAMPP وأغلب الاستضافات).
 */
final class XlsxWriter
{
    private string $sheetName;
    private array $rows = [];        // rowNum => [colNum => [value, styleId]]
    private array $rowHeights = [];
    private array $colWidths = [];
    private array $styles = [];      // key => [id, def]
    private array $fonts = ['<font><sz val="11"/><name val="Arial"/></font>'];
    private array $fills = ['<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>'];
    private array $borders = ['<border><left/><right/><top/><bottom/><diagonal/></border>'];
    private array $xfs = ['<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'];
    private bool $rtl;

    public function __construct(string $sheetName = 'Sheet1', bool $rtl = true)
    {
        $this->sheetName = mb_substr($sheetName, 0, 31);
        $this->rtl = $rtl;
    }

    public static function available(): bool
    {
        return class_exists('ZipArchive');
    }

    /**
     * تسجيل نمط: ['bold'=>true, 'size'=>12, 'color'=>'FFFFFF', 'fill'=>'2C3E50', 'align'=>'center', 'border'=>true]
     */
    public function style(array $def): int
    {
        ksort($def);
        $key = json_encode($def);
        if (isset($this->styles[$key])) {
            return $this->styles[$key];
        }
        $font = '<font>' . (!empty($def['bold']) ? '<b/>' : '') . '<sz val="' . ($def['size'] ?? 11) . '"/>'
            . (isset($def['color']) ? '<color rgb="FF' . $def['color'] . '"/>' : '') . '<name val="Arial"/></font>';
        $fontId = $this->indexOf($this->fonts, $font);
        $fillId = 0;
        if (isset($def['fill'])) {
            $fill = '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $def['fill'] . '"/><bgColor indexed="64"/></patternFill></fill>';
            $fillId = $this->indexOf($this->fills, $fill);
        }
        $borderId = 0;
        if (!empty($def['border'])) {
            $s = '<left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom>';
            $borderId = $this->indexOf($this->borders, '<border>' . $s . '<diagonal/></border>');
        }
        $align = '';
        if (isset($def['align']) || !empty($def['wrap'])) {
            $align = '<alignment' . (isset($def['align']) ? ' horizontal="' . $def['align'] . '"' : '') . ' vertical="center"'
                . (!empty($def['wrap']) ? ' wrapText="1"' : '') . '/>';
        }
        $xf = '<xf numFmtId="0" fontId="' . $fontId . '" fillId="' . $fillId . '" borderId="' . $borderId . '" xfId="0"'
            . ($fontId ? ' applyFont="1"' : '') . ($fillId ? ' applyFill="1"' : '') . ($borderId ? ' applyBorder="1"' : '')
            . ($align ? ' applyAlignment="1">' . $align . '</xf>' : '/>');
        $this->xfs[] = $xf;
        return $this->styles[$key] = count($this->xfs) - 1;
    }

    private function indexOf(array &$list, string $item): int
    {
        $i = array_search($item, $list, true);
        if ($i === false) {
            $list[] = $item;
            $i = count($list) - 1;
        }
        return $i;
    }

    public function setCell(int $row, int $col, $value, int $style = 0): void
    {
        $this->rows[$row][$col] = [$value, $style];
    }

    public function setColumnWidth(int $col, float $width): void
    {
        $this->colWidths[$col] = $width;
    }

    public function setRowHeight(int $row, float $height): void
    {
        $this->rowHeights[$row] = $height;
    }

    public static function colName(int $col): string
    {
        $name = '';
        while ($col > 0) {
            $m = ($col - 1) % 26;
            $name = chr(65 + $m) . $name;
            $col = intdiv($col - 1, 26);
        }
        return $name;
    }

    private static function x(string $s): string
    {
        // إزالة المحارف غير المسموحة في XML
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function sheetXml(): string
    {
        ksort($this->rows);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"' . ($this->rtl ? ' rightToLeft="1"' : '') . '/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>';
        if ($this->colWidths) {
            ksort($this->colWidths);
            $xml .= '<cols>';
            foreach ($this->colWidths as $c => $w) {
                $xml .= '<col min="' . $c . '" max="' . $c . '" width="' . $w . '" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }
        $xml .= '<sheetData>';
        foreach ($this->rows as $r => $cells) {
            ksort($cells);
            $xml .= '<row r="' . $r . '"' . (isset($this->rowHeights[$r]) ? ' ht="' . $this->rowHeights[$r] . '" customHeight="1"' : '') . '>';
            foreach ($cells as $c => [$value, $style]) {
                $ref = self::colName($c) . $r;
                $s = $style ? ' s="' . $style . '"' : '';
                if ($value === null || $value === '') {
                    $xml .= '<c r="' . $ref . '"' . $s . '/>';
                } elseif (is_int($value) || is_float($value)) {
                    $xml .= '<c r="' . $ref . '"' . $s . '><v>' . $value . '</v></c>';
                } else {
                    $xml .= '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . self::x((string) $value) . '</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        return $xml . '</sheetData></worksheet>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($this->fonts) . '">' . implode('', $this->fonts) . '</fonts>'
            . '<fills count="' . count($this->fills) . '">' . implode('', $this->fills) . '</fills>'
            . '<borders count="' . count($this->borders) . '">' . implode('', $this->borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($this->xfs) . '">' . implode('', $this->xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    public function save(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('تعذر إنشاء ملف Excel');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>');
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>DOU Setif</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>DOU Setif</Application></Properties>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<bookViews><workbookView/></bookViews>'
            . '<sheets><sheet name="' . self::x($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());
        $zip->close();
    }

    /** إرسال الملف للمتصفح */
    public function download(string $filename): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $this->save($tmp);
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        never_cache();
        readfile($tmp);
        @unlink($tmp);
        exit;
    }
}
