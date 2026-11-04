<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap/env.php';

/**
 * SPEED_FACTOR, GAME_SPEED_* and ROUND_DAYS come from the environment through dolumar_env_number().
 */
class EnvNumberTest extends TestCase
{
	private const NAME = 'DOLUMAR_TEST_ENV_NUMBER';

	protected function tearDown (): void
	{
		putenv (self::NAME);
	}

	public function testMissingGivesTheDefault (): void
	{
		putenv (self::NAME);
		$this->assertSame (1, dolumar_env_number (self::NAME, 1));
	}

	#[DataProvider ('invalidValues')]
	public function testInvalidValuesGiveTheDefault (string $value): void
	{
		putenv (self::NAME . '=' . $value);
		$this->assertSame (1, dolumar_env_number (self::NAME, 1));
	}

	public static function invalidValues (): array
	{
		return [
			'empty' => [''],
			'spaces' => ['  '],
			'zero' => ['0'],
			'negative' => ['-3'],
			'text' => ['fast'],
			'x prefix' => ['x3'],
		];
	}

	public function testWholeNumberIsAnInt (): void
	{
		putenv (self::NAME . '=3');
		$this->assertSame (3, dolumar_env_number (self::NAME, 1));
	}

	public function testDecimalIsAFloat (): void
	{
		putenv (self::NAME . '= 1.5 ');
		$this->assertSame (1.5, dolumar_env_number (self::NAME, 1));
	}
}
