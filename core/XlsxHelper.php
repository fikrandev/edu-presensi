<?php
declare(strict_types=1);

/**
 * Pure-PHP Native XLSX Reader & Writer (no Composer, no ext-zip required — uses built-in zlib).
 * Explicitly rejects CSV files; accepts only valid Office Open XML (.xlsx) archives.
 */
class XlsxHelper
{
    /**
     * Parse the first worksheet of an .xlsx file into an array of rows (indexed arrays of strings).
     */
    public static function readXlsx(string $filePath): array
    {
        $binary = file_get_contents($filePath);
        if ($binary === false || substr($binary, 0, 2) !== 'PK') {
            throw new RuntimeException('File bukan format Excel (.xlsx) yang valid. Format CSV tidak diperbolehkan.');
        }

        $entries = self::extractZipEntries($binary);
        if (!isset($entries['xl/worksheets/sheet1.xml'])) {
            throw new RuntimeException('Worksheet utama (sheet1.xml) tidak ditemukan dalam file Excel.');
        }

        $sharedStrings = [];
        if (isset($entries['xl/sharedStrings.xml'])) {
            $ssXml = simplexml_load_string($entries['xl/sharedStrings.xml']);
            if ($ssXml) {
                foreach ($ssXml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } else {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    }
                }
            }
        }

        $sheetXml = simplexml_load_string($entries['xl/worksheets/sheet1.xml']);
        if (!$sheetXml) {
            throw new RuntimeException('Gagal membaca XML worksheet Excel.');
        }

        $rows = [];
        if (isset($sheetXml->sheetData->row)) {
            foreach ($sheetXml->sheetData->row as $rowNode) {
                $rowArr = [];
                foreach ($rowNode->c as $cell) {
                    $ref  = (string)$cell['r'];
                    $type = (string)$cell['t'];
                    $colLetters = preg_replace('/[0-9]+/', '', $ref) ?? '';
                    $colIdx = self::colLettersToIndex($colLetters);

                    $val = '';
                    if ($type === 's') {
                        $idx = (int)$cell->v;
                        $val = $sharedStrings[$idx] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $val = (string)($cell->is->t ?? '');
                    } else {
                        $val = (string)($cell->v ?? '');
                    }

                    while (count($rowArr) < $colIdx) {
                        $rowArr[] = '';
                    }
                    $rowArr[$colIdx] = trim($val);
                }
                if (count(array_filter($rowArr, static fn($v) => $v !== '')) > 0) {
                    $rows[] = $rowArr;
                }
            }
        }
        return $rows;
    }

    /**
     * Build a genuine .xlsx binary from 2D array of rows and send as download.
     */
    public static function downloadXlsx(string $filename, array $rows, string $sheetName = 'Data'): never
    {
        $binary = self::buildXlsxBinary($rows, $sheetName);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: max-age=0');
        echo $binary;
        exit;
    }

    public static function buildXlsxBinary(array $rows, string $sheetName = 'Data'): string
    {
        $safeSheet = htmlspecialchars(substr($sheetName, 0, 31), ENT_XML1, 'UTF-8');

        $sheetRowsXml = '';
        foreach ($rows as $rIdx => $row) {
            $rNum = $rIdx + 1;
            $sheetRowsXml .= '<row r="' . $rNum . '">';
            foreach (array_values($row) as $cIdx => $val) {
                $colRef = self::indexToColLetters($cIdx) . $rNum;
                $escaped = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                $styleAttr = $rNum === 1 ? ' s="1"' : '';
                $sheetRowsXml .= '<c r="' . $colRef . '" t="inlineStr"' . $styleAttr . '><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetRowsXml .= '</row>';
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                . '</Types>',

            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',

            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="' . $safeSheet . '" sheetId="1" r:id="rId1"/></sheets>'
                . '</workbook>',

            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                . '</Relationships>',

            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
                . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1D4ED8"/><bgColor indexed="64"/></patternFill></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
                . '</styleSheet>',

            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetData>' . $sheetRowsXml . '</sheetData>'
                . '</worksheet>',
        ];

        return self::createZipBinary($files);
    }

    private static function extractZipEntries(string $data): array
    {
        $entries = [];
        $eocdPos = strrpos($data, "\x50\x4b\x05\x06");
        if ($eocdPos === false) {
            throw new RuntimeException('Struktur arsip XLSX rusak.');
        }
        $cdSize   = unpack('V', substr($data, $eocdPos + 12, 4))[1];
        $cdOffset = unpack('V', substr($data, $eocdPos + 16, 4))[1];
        $pos      = $cdOffset;
        $end      = $cdOffset + $cdSize;

        while ($pos < $end && substr($data, $pos, 4) === "\x50\x4b\x01\x02") {
            $method   = unpack('v', substr($data, $pos + 10, 2))[1];
            $compSize = unpack('V', substr($data, $pos + 20, 4))[1];
            $nameLen  = unpack('v', substr($data, $pos + 28, 2))[1];
            $extraLen = unpack('v', substr($data, $pos + 30, 2))[1];
            $commLen  = unpack('v', substr($data, $pos + 32, 2))[1];
            $lhOffset = unpack('V', substr($data, $pos + 42, 4))[1];
            $name     = substr($data, $pos + 46, $nameLen);

            $lhNameLen  = unpack('v', substr($data, $lhOffset + 26, 2))[1];
            $lhExtraLen = unpack('v', substr($data, $lhOffset + 28, 2))[1];
            $payload    = substr($data, $lhOffset + 30 + $lhNameLen + $lhExtraLen, $compSize);

            if ($method === 0) {
                $entries[$name] = $payload;
            } elseif ($method === 8) {
                $inflated = @gzinflate($payload);
                if ($inflated !== false) {
                    $entries[$name] = $inflated;
                }
            }
            $pos += 46 + $nameLen + $extraLen + $commLen;
        }
        return $entries;
    }

    private static function createZipBinary(array $files): string
    {
        $localData = '';
        $centralDir = '';
        $offset = 0;
        $count = 0;

        foreach ($files as $name => $content) {
            $crc        = crc32($content);
            $uncompSize = strlen($content);
            $compressed = gzdeflate($content, 6);
            $compSize   = strlen($compressed);
            $nameLen    = strlen($name);

            $localHeader = "\x50\x4b\x03\x04"
                . pack('vvvvvVVVvv', 20, 0, 8, 0, 0, $crc, $compSize, $uncompSize, $nameLen, 0)
                . $name;
            $localData .= $localHeader . $compressed;

            $centralHeader = "\x50\x4b\x01\x02"
                . pack('vvvvvvVVVvvvvvVV', 20, 20, 0, 8, 0, 0, $crc, $compSize, $uncompSize, $nameLen, 0, 0, 0, 0, 0, $offset)
                . $name;
            $centralDir .= $centralHeader;

            $offset += strlen($localHeader) + $compSize;
            $count++;
        }

        $eocd = "\x50\x4b\x05\x06"
            . pack('vvvvVVv', 0, 0, $count, $count, strlen($centralDir), $offset, 0);

        return $localData . $centralDir . $eocd;
    }

    private static function colLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return max(0, $index - 1);
    }

    private static function indexToColLetters(int $index): string
    {
        $letters = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = (int)(($index - $mod) / 26);
        }
        return $letters;
    }
}
