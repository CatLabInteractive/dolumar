<?php

use PHPUnit\Framework\TestCase;

class NameSuggesterTest extends TestCase
{
	private function assertValidName (string $name): void
	{
		$this->assertMatchesRegularExpression ('/^[a-zA-Z0-9 ]{3,20}$/', $name);
	}

	public function testFirstRegimentIsFirst(): void
	{
		$s = new Dolumar_Squads_NameSuggester ();
		$this->assertSame ('1st Archers', $s->suggest ('Archers', []));
	}

	public function testPicksLowestFreeNumber(): void
	{
		$s = new Dolumar_Squads_NameSuggester ();
		$this->assertSame ('2nd Archers', $s->suggest ('Archers', ['1st Archers', '3rd Archers', 'Bowmen']));
		$this->assertSame ('4th Archers', $s->suggest ('Archers', ['1st archers', '2nd Archers', '3rd Archers ']));
	}

	public function testEnglishOrdinals(): void
	{
		$s = new Dolumar_Squads_NameSuggester ();
		$this->assertSame (['1st', '2nd', '3rd', '4th', '11th', '12th', '13th', '21st', '22nd', '111th'],
			array_map ([$s, 'ordinal'], [1, 2, 3, 4, 11, 12, 13, 21, 22, 111]));
	}

	public function testOtherLanguages(): void
	{
		$this->assertSame ('2e Boogschutters', (new Dolumar_Squads_NameSuggester ('@@ordinal @@unit', 'nl'))->suggest ('Boogschutters', ['1e Boogschutters']));
		$this->assertSame ('1er Executeurs', (new Dolumar_Squads_NameSuggester ('@@ordinal @@unit', 'fr'))->suggest ('Exécuteurs', []));
		$this->assertSame ('Bogenschutzen 1', (new Dolumar_Squads_NameSuggester ('@@unit @@number', 'de'))->suggest ('Bogenschützen', []));
	}

	public function testApostrophesAreDropped(): void
	{
		$name = (new Dolumar_Squads_NameSuggester ())->suggest ("Gu'al Cavalry", []);
		$this->assertSame ('1st Gual Cavalry', $name);
		$this->assertValidName ($name);
	}

	public function testLongNamesAreShortenedToTwentyCharacters(): void
	{
		$s = new Dolumar_Squads_NameSuggester ('@@ordinal @@unit', 'fr');
		$name = $s->suggest ('Sentinelles du temple', []);
		$this->assertValidName ($name);
		$this->assertSame ('1er Sentinelles du', $name);

		$name = (new Dolumar_Squads_NameSuggester ())->suggest ('Supercalifragilisticexpialidocious', []);
		$this->assertValidName ($name);
		$this->assertStringStartsWith ('1st Supercal', $name);
	}

	public function testEmptyUnitNameFallsBack(): void
	{
		$name = (new Dolumar_Squads_NameSuggester ('@@ordinal @@unit', 'en', 'Regiment'))->suggest ('??', []);
		$this->assertSame ('1st Regiment', $name);
	}
}
