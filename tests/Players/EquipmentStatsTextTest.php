<?php

use PHPUnit\Framework\TestCase;

/**
 * Equipment stats such as melee and hp are percentages stored as strings ("15%").
 * abs() on such a string throws a TypeError on PHP 8, which broke the Equipment window (dolumar #58).
 */
class EquipmentStatsTextTest extends TestCase
{
	private function statsTextFor (array $stats): string
	{
		$item = (new ReflectionClass ('Dolumar_Players_Equipment'))->newInstanceWithoutConstructor ();
		$property = new ReflectionProperty ('Dolumar_Players_Equipment', 'stats');
		$property->setValue ($item, $stats);

		$method = new ReflectionMethod ('Dolumar_Players_Equipment', 'getStats_text');
		return $method->invoke ($item);
	}

	public function testPercentageStatsKeepTheirSign (): void
	{
		$text = $this->statsTextFor (array ('melee' => '15%', 'hp' => '-5%', 'defIn' => 10));

		$this->assertStringContainsString (': +15%', $text);
		$this->assertStringContainsString (': -5%', $text);
		$this->assertStringContainsString (': +10', $text);
	}
}
