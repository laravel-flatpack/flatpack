#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Summarize Pest/Xdebug Clover XML into coverage buckets (statements).
 *
 * Usage: php tools/coverage-from-clover.php [path/to/clover.xml]
 * Default: ./clover.xml (repository root)
 */
$path = $argv[1] ?? dirname(__DIR__) . '/clover.xml';

if (! is_readable($path)) {
    fwrite(STDERR, "Cannot read: {$path}\n");
    exit(1);
}

$xml = new SimpleXMLElement((string) file_get_contents($path));

$zero = [];
$partial = [];
$sufficient = [];
$statementsTotal = 0;
$coveredTotal = 0;

foreach ($xml->xpath('//project//file[@name]') as $file) {
    $name = (string) $file['name'];
    if (! str_contains($name, '/src/')) {
        continue;
    }

    $rel = preg_replace('#^.*/src/#', 'src/', $name) ?? $name;
    $fileMetrics = $file->metrics;
    if ($fileMetrics->count() === 0) {
        continue;
    }
    $m = $fileMetrics[0];
    $st = (int) $m['statements'];
    $cv = (int) $m['coveredstatements'];
    if ($st === 0) {
        continue;
    }
    $statementsTotal += $st;
    $coveredTotal += $cv;
    $pct = $cv / $st;
    if ($cv === 0) {
        $zero[] = [$rel, 0.0, $st];
    } elseif ($pct < 0.8) {
        $partial[] = [$rel, $pct, $st, $cv];
    } else {
        $sufficient[] = [$rel, $pct, $st, $cv];
    }
}

$sort = static fn (array $a, array $b): int => ($b[1] <=> $a[1]) ?: strcmp($a[0], $b[0]);
usort($zero, $sort);
usort($partial, static fn (array $a, array $b): int => ($a[1] <=> $b[1]) ?: strcmp($a[0], $b[0]));
usort($sufficient, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));

$pctAll = $statementsTotal > 0 ? 100 * $coveredTotal / $statementsTotal : 0.0;

echo 'Coverage summary (src/ only, file-level statement metrics)' . PHP_EOL;
echo sprintf('Total statements: %d covered / %d (%.1f%%)', $coveredTotal, $statementsTotal, $pctAll) . PHP_EOL . PHP_EOL;

echo 'Not covered (0%): ' . count($zero) . ' files' . PHP_EOL;
foreach ($zero as [$f, , $st]) {
    echo sprintf('  %s  (%d stmts)', $f, $st) . PHP_EOL;
}

echo PHP_EOL . 'Partial (1–79%): ' . count($partial) . ' files' . PHP_EOL;
foreach ($partial as [$f, $p, $st, $cv]) {
    echo sprintf('  %s  %.1f%% (%d/%d)', $f, $p * 100, $cv, $st) . PHP_EOL;
}

echo PHP_EOL . 'Sufficient (≥80%): ' . count($sufficient) . ' files (listed briefly)' . PHP_EOL;
foreach ($sufficient as [$f, $p]) {
    echo sprintf('  %s  %.1f%%', $f, $p * 100) . PHP_EOL;
}
