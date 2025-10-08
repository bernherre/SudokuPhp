<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Sudoku;

final class SudokuTest extends TestCase
{
    public function testEmptyGrid(): void
    {
        $g = Sudoku::createEmpty(4);
        $this->assertCount(4, $g);
        $this->assertCount(4, $g[0]);
        $this->assertSame(0, array_sum(array_map('array_sum', $g)));
    }

    public function testGenerateSolve4(): void
    {
        $data = Sudoku::generate(4, 'easy');
        $this->assertIsArray($data['puzzle']);
        $solved = Sudoku::solve($data['puzzle']);
        $this->assertNotNull($solved);
        $check = Sudoku::checkGrid($solved, $data['solution']);
        $this->assertTrue($check['ok']);
    }

    public function testGenerateSolve6(): void
    {
        $data = Sudoku::generate(6, 'medium');
        $this->assertIsArray($data['puzzle']);
        $solved = Sudoku::solve($data['puzzle']);
        $this->assertNotNull($solved);
        $check = Sudoku::checkGrid($solved, $data['solution']);
        $this->assertTrue($check['ok']);
    }

    public function testGenerateSolve9(): void
    {
        $data = Sudoku::generate(9, 'hard');
        $this->assertIsArray($data['puzzle']);
        $solved = Sudoku::solve($data['puzzle']);
        $this->assertNotNull($solved);
        $check = Sudoku::checkGrid($solved, $data['solution']);
        $this->assertTrue($check['ok']);
    }
}