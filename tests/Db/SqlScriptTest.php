<?php

use PHPUnit\Framework\TestCase;

class SqlScriptTest extends TestCase
{
	public function testSplitsOnSemicolonsAndSkipsEmptyChunks (): void
	{
		$this->assertSame (
			['CREATE TABLE a (id int)', 'ALTER TABLE a ADD b int'],
			Dolumar_Db_SqlScript::splitStatements ("CREATE TABLE a (id int);\n\nALTER TABLE a ADD b int;\n;\n")
		);
	}

	public function testFullLineCommentsAreDroppedBeforeSplitting (): void
	{
		$sql = "-- adds b; and c\n  -- indented; comment\nALTER TABLE a ADD b int;\n";
		$this->assertSame (['ALTER TABLE a ADD b int'], Dolumar_Db_SqlScript::splitStatements ($sql));
	}

	public function testWindowsLineEndings (): void
	{
		$this->assertSame (['A', 'B'], Dolumar_Db_SqlScript::splitStatements ("-- x\r\nA;\r\nB;"));
	}

	public function testEmptyInput (): void
	{
		$this->assertSame ([], Dolumar_Db_SqlScript::splitStatements (''));
		$this->assertSame ([], Dolumar_Db_SqlScript::splitStatements ("-- only a comment\n"));
	}
}
