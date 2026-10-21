<?php
/** Lectura y escritura CSV (Excel: BOM, ; , tab, fila entre comillas). */

/** @return array<int,array<int,string>> */
function csv_read_rows(string $tmpPath): array {
    $raw = file_get_contents($tmpPath);
    if ($raw === false || $raw === '') {
        throw new InvalidArgumentException('Archivo vacío');
    }
    if (str_starts_with($raw, "\xEF\xBB\xBF")) {
        $raw = substr($raw, 3);
    }
    $lines = preg_split('/\R/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
    if (!$lines) {
        throw new InvalidArgumentException('Archivo vacío');
    }
    $rows = [];
    foreach ($lines as $line) {
        $row = csv_expand_row($line);
        if ($row !== []) {
            $rows[] = $row;
        }
    }
    if ($rows === []) {
        throw new InvalidArgumentException('Archivo vacío');
    }
    return $rows;
}

/** @return array<int,string> */
function csv_expand_row(string $line): array {
    $line = trim($line);
    if ($line === '') {
        return [];
    }
    $delimiter = csv_detect_delimiter($line);
    $row = str_getcsv($line, $delimiter, '"', '\\');
    if (count($row) > 1) {
        return array_map(fn($c) => trim((string)$c), $row);
    }
    $single = trim((string)($row[0] ?? ''));
    if ($single === '') {
        return [];
    }
    if (strlen($single) >= 2 && $single[0] === '"' && substr($single, -1) === '"') {
        $single = str_replace('""', '"', substr($single, 1, -1));
    }
    $innerDelim = csv_detect_delimiter($single);
    $expanded = str_getcsv($single, $innerDelim, '"', '\\');
    if (count($expanded) > 1) {
        return array_map(fn($c) => trim((string)$c), $expanded);
    }
    $altDelim = $innerDelim === ',' ? ';' : ',';
    if (str_contains($single, $altDelim)) {
        $expanded = str_getcsv($single, $altDelim, '"', '\\');
        if (count($expanded) > 1) {
            return array_map(fn($c) => trim((string)$c), $expanded);
        }
    }
    return [$single];
}

function csv_detect_delimiter(string $line): string {
    $comma = substr_count($line, ',');
    $semi = substr_count($line, ';');
    $tab = substr_count($line, "\t");
    if ($semi > $comma && $semi >= $tab) {
        return ';';
    }
    if ($tab > $comma && $tab > $semi) {
        return "\t";
    }
    return ',';
}

function csv_normalize_header_name(string $cell): string {
    $cell = trim($cell);
    $cell = preg_replace('/^\xEF\xBB\xBF/u', '', $cell) ?? $cell;
    $cell = strtolower($cell);
    $cell = str_replace([' ', '-'], '_', $cell);
    return $cell;
}

/** @param array<int,string> $header */
function csv_column_index(array $header, string $canonical, array $aliases = []): int|false {
    $keys = array_merge([$canonical], $aliases);
    foreach ($keys as $key) {
        $pos = array_search($key, $header, true);
        if ($pos !== false) {
            return $pos;
        }
    }
    return false;
}

/** @param array<int,string|int|float> $fields */
function csv_line(array $fields, string $delimiter = ';'): string {
    $parts = [];
    foreach ($fields as $field) {
        $value = (string)$field;
        if (str_contains($value, $delimiter) || str_contains($value, '"') || str_contains($value, "\n") || str_contains($value, "\r")) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        $parts[] = $value;
    }
    return implode($delimiter, $parts);
}
