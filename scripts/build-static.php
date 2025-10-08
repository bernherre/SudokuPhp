<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Sudoku;

$dist = __DIR__ . '/../dist/data';
@mkdir($dist, 0777, true);

$sizes = [4, 6, 9];
$diffs = ['easy', 'medium', 'hard'];
$count = (int) ($_ENV['PUZZLES_PER_SET'] ?? 15); // ajusta cantidad

foreach ($sizes as $n) {
    foreach ($diffs as $d) {
        $set = [];
        for ($i = 0; $i < $count; $i++) {
            $g = Sudoku::generate($n, $d);
            $set[] = ['puzzle' => $g['puzzle'], 'solution' => $g['solution']];
        }
        $file = sprintf('%s/%dx%d-%s.json', $dist, $n, $n, $d);
        file_put_contents($file, json_encode($set, JSON_UNESCAPED_UNICODE));
        echo "Wrote $file (" . count($set) . ")\n";
    }
}
