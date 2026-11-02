<?php

use PHPUnit\Framework\TestCase;

class TribeTest extends TestCase
{
	public function testGenerateNameIsDeterministicWithInjectedRandom (): void
	{
		$rand = function ($min, $max) { return $min; };
		$this->assertSame ('Slickbottoms of Qen Keep', Dolumar_Npc_Tribe::generateName ($rand));

		$rand = function ($min, $max) { return $max; };
		$name = Dolumar_Npc_Tribe::generateName ($rand);
		$this->assertMatchesRegularExpression ('/^[A-Z][a-z]+ of [A-Z][a-z]+ [A-Z][a-z]+$/', $name);
	}

	public function testMultipliersStayInRange (): void
	{
		$low = Dolumar_Npc_Tribe::multipliers (function ($min, $max) { return $min; });
		$high = Dolumar_Npc_Tribe::multipliers (function ($min, $max) { return $max; });
		$this->assertSame (['mul_threshold' => 0.8, 'mul_decay' => 0.8, 'mul_hoard' => 0.8], $low);
		$this->assertSame (['mul_threshold' => 1.2, 'mul_decay' => 1.2, 'mul_hoard' => 1.2], $high);

		for ($i = 0; $i < 50; $i ++) {
			foreach (Dolumar_Npc_Tribe::multipliers () as $v) {
				$this->assertGreaterThanOrEqual (0.8, $v);
				$this->assertLessThanOrEqual (1.2, $v);
			}
		}
	}

	public function testHoardWords (): void
	{
		$this->assertSame ('lean', Dolumar_Npc_Tribe::hoardWord (0, 1000));
		$this->assertSame ('lean', Dolumar_Npc_Tribe::hoardWord (249, 1000));
		$this->assertSame ('stocked', Dolumar_Npc_Tribe::hoardWord (250, 1000));
		$this->assertSame ('stocked', Dolumar_Npc_Tribe::hoardWord (749, 1000));
		$this->assertSame ('overflowing', Dolumar_Npc_Tribe::hoardWord (750, 1000));
		$this->assertSame ('lean', Dolumar_Npc_Tribe::hoardWord (500, 0));
	}

	public function testRequestAsksForTheScarcestResource (): void
	{
		$request = Dolumar_Npc_Tribe::makeRequest (
			['gold' => 1000, 'wood' => 4000, 'stone' => 200, 'iron' => 3000, 'grain' => 5000, 'gems' => 1],
			1.0
		);
		$this->assertSame ('stone', $request['res']);
		// 25 % of 13 200 = 3 300, rounded to 50
		$this->assertSame (3300, $request['amount']);
	}

	public function testRequestIsClampedAndRounded (): void
	{
		$small = Dolumar_Npc_Tribe::makeRequest (['wood' => 10, 'stone' => 20], 1.0);
		$this->assertSame (300, $small['amount']);

		$big = Dolumar_Npc_Tribe::makeRequest (['wood' => 100000, 'stone' => 200000], 1.2);
		$this->assertSame (5000, $big['amount']);

		$this->assertNull (Dolumar_Npc_Tribe::makeRequest ([], 1.0));
	}
}
