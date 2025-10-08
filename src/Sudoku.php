<?php
namespace App;

final class Sudoku
{
    /** @return array{int,int} */
    public static function subgridDims(int $n): array
    {
        return match ($n) {
            4 => [2,2],
            6 => [2,3],
            default => [3,3],
        };
    }

    /** @return int[][] */
    public static function createEmpty(int $n): array
    {
        return array_fill(0, $n, array_fill(0, $n, 0));
    }

    /** @param int[][] $g */
    public static function cloneGrid(array $g): array
    {
        return array_map(fn($r)=>array_values($r), $g);
    }

    /** @param int[][] $g */
    public static function isValid(array $g, int $r, int $c, int $val): bool
    {
        $n = count($g);
        for ($i=0; $i<$n; $i++) {
            if ($g[$r][$i] === $val) return false;
            if ($g[$i][$c] === $val) return false;
        }
        [$sr,$sc] = self::subgridDims($n);
        $r0 = intdiv($r, $sr) * $sr;
        $c0 = intdiv($c, $sc) * $sc;
        for ($i=0;$i<$sr;$i++) for ($j=0;$j<$sc;$j++) {
            if ($g[$r0+$i][$c0+$j] === $val) return false;
        }
        return true;
    }

    /** @param int[][] $g */
    public static function solve(array $g): ?array
    {
        $n = count($g);
        $grid = self::cloneGrid($g);

        $vals = range(1,$n);
        $shuffle = function(array &$a){ for($i=count($a)-1;$i>0;$i--){ $j=random_int(0,$i); [$a[$i],$a[$j]] = [$a[$j],$a[$i]]; } };

        $backtrack = function(int $pos) use (&$grid, $n, &$backtrack, $vals, $shuffle): bool {
            if ($pos === $n*$n) return true;
            $r = intdiv($pos, $n);
            $c = $pos % $n;
            if ($grid[$r][$c] !== 0) return $backtrack($pos+1);
            $try = $vals;
            $shuffle($try);
            foreach ($try as $v) {
                if (Sudoku::isValid($grid, $r, $c, $v)) {
                    $grid[$r][$c] = $v;
                    if ($backtrack($pos+1)) return true;
                    $grid[$r][$c] = 0;
                }
            }
            return false;
        };

        return $backtrack(0) ? $grid : null;
    }

    /** Count solutions up to limit. @param int[][] $g */
    public static function countSolutions(array $g, int $limit=2): int
    {
        $n = count($g);
        $grid = self::cloneGrid($g);
        $count = 0;

        $backtrack = function(int $pos) use (&$grid, $n, &$count, $limit, &$backtrack): bool {
            if ($pos === $n*$n) { $count++; return $count >= $limit; }
            $r = intdiv($pos, $n);
            $c = $pos % $n;
            if ($grid[$r][$c] !== 0) return $backtrack($pos+1);
            for ($v=1; $v<=$n; $v++) {
                if (Sudoku::isValid($grid, $r, $c, $v)) {
                    $grid[$r][$c] = $v;
                    if ($backtrack($pos+1)) return true;
                    $grid[$r][$c] = 0;
                }
            }
            return false;
        };

        $backtrack(0);
        return $count;
    }

    /** @return array{puzzle:int[][],solution:int[][]} */
    public static function generate(int $n, string $difficulty): array
    {
        $solved = self::solve(self::createEmpty($n));
        if ($solved === null) throw new \RuntimeException("No se pudo generar solución base");

        $puzzle = self::cloneGrid($solved);
        $positions = range(0, $n*$n-1);
        shuffle($positions);
        $toRemove = self::removalCount($n, $difficulty);

        foreach ($positions as $pos) {
            if ($toRemove <= 0) break;
            $r = intdiv($pos, $n);
            $c = $pos % $n;
            $backup = $puzzle[$r][$c];
            if ($backup === 0) continue;
            $puzzle[$r][$c] = 0;
            $solutions = self::countSolutions($puzzle, 2);
            if ($solutions !== 1) {
                $puzzle[$r][$c] = $backup;
            } else {
                $toRemove--;
            }
        }

        return ["puzzle"=>$puzzle, "solution"=>$solved];
    }

    private static function removalCount(int $n, string $d): int
    {
        $totals = [4=>16, 6=>36, 9=>81];
        $base = match($d){
            'easy'=>'easy', 'medium'=>'medium', 'hard'=>'hard', default=>'medium'
        };
        $ratio = ['easy'=>0.45,'medium'=>0.6,'hard'=>0.7][$base];
        $minClues = [4=>6, 6=>10, 9=>22][$n];
        $remove = max(0, (int)floor($totals[$n]*$ratio));
        return min($totals[$n]-$minClues, $remove);
    }

    /** @param int[][] $cur @param int[][] $sol */
    public static function checkGrid(array $cur, array $sol): array
    {
        $errors = [];
        $n = count($cur);
        for ($r=0;$r<$n;$r++) for ($c=0;$c<$n;$c++) {
            $v = $cur[$r][$c];
            if ($v !== 0 && $v !== $sol[$r][$c]) {
                $errors[] = [$r,$c];
            }
        }
        $ok = count($errors)===0;
        $complete = $ok && !in_array(0, array_merge(...$cur), true);
        return ["ok"=>$complete, "errors"=>$errors];
    }

    /** @param int[][] $g */
    public static function firstEmpty(array $g): ?array
    {
        $n = count($g);
        for ($r=0;$r<$n;$r++) for ($c=0;$c<$n;$c++) {
            if ($g[$r][$c] === 0) return [$r,$c];
        }
        return null;
    }
}