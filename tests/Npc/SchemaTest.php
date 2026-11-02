<?php

use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
	public function testStatementsAreIdempotentCreates (): void
	{
		$statements = Dolumar_Npc_Schema::statements ();
		$this->assertCount (3, $statements);
		foreach ($statements as $sql) {
			$this->assertStringStartsWith ('CREATE TABLE IF NOT EXISTS `npc_', $sql);
		}
	}

	public function testDumpCarriesTheSameTables (): void
	{
		$dump = file_get_contents (__DIR__ . '/../../dump/dump.sql');
		foreach (['npc_villages', 'npc_standings', 'npc_log'] as $table) {
			$this->assertStringContainsString ('CREATE TABLE IF NOT EXISTS `' . $table . '`', $dump);
		}
	}
}
