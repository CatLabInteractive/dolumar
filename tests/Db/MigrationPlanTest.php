<?php

use PHPUnit\Framework\TestCase;

class MigrationPlanTest extends TestCase
{
	public function testOnlyDatedUpgradeFilesAreMigrations (): void
	{
		$this->assertTrue (Dolumar_Db_MigrationPlan::isMigrationFile ('upgrade-20261102.sql'));
		$this->assertTrue (Dolumar_Db_MigrationPlan::isMigrationFile ('upgrade-20261102b.sql'));
		$this->assertTrue (Dolumar_Db_MigrationPlan::isMigrationFile ('upgrade-20261102-npc-tribes.sql'));
		$this->assertFalse (Dolumar_Db_MigrationPlan::isMigrationFile ('README.md'));
		$this->assertFalse (Dolumar_Db_MigrationPlan::isMigrationFile ('dump.sql'));
		$this->assertFalse (Dolumar_Db_MigrationPlan::isMigrationFile ('.'));
		$this->assertFalse (Dolumar_Db_MigrationPlan::isMigrationFile ('upgrade-notes.sql'));
		$this->assertFalse (Dolumar_Db_MigrationPlan::isMigrationFile ('upgrade-20261102.sql.bak'));
	}

	public function testPendingFilesRunInNameOrder (): void
	{
		$pending = Dolumar_Db_MigrationPlan::pending (
			['.', '..', 'upgrade-20261103.sql', 'README.md', 'upgrade-20261102b.sql', 'upgrade-20261102-a.sql'],
			[]
		);
		$this->assertSame (['upgrade-20261102-a.sql', 'upgrade-20261102b.sql', 'upgrade-20261103.sql'], $pending);
	}

	public function testAppliedFilesAreSkipped (): void
	{
		$pending = Dolumar_Db_MigrationPlan::pending (
			['upgrade-20261101.sql', 'upgrade-20261102.sql'],
			['upgrade-20261101.sql']
		);
		$this->assertSame (['upgrade-20261102.sql'], $pending);
	}

	public function testShippedMigrationsSplitIntoStatements (): void
	{
		$dir = __DIR__ . '/../../dump/migrations';
		$files = Dolumar_Db_MigrationPlan::migrations (scandir ($dir));
		$this->assertNotEmpty ($files);

		$dump = file_get_contents (__DIR__ . '/../../dump/dump.sql');
		foreach ($files as $file) {
			$statements = Dolumar_Db_SqlScript::splitStatements (file_get_contents ($dir . '/' . $file));
			$this->assertNotEmpty ($statements, $file);
			foreach ($statements as $statement) {
				// Every table a migration creates must also be in dump/dump.sql for fresh installs.
				if (preg_match ('/^CREATE TABLE (?:IF NOT EXISTS )?`([^`]+)`/i', $statement, $m)) {
					$this->assertStringContainsString ('CREATE TABLE `' . $m[1] . '`', $dump, $file . ' creates ' . $m[1]);
				}
			}
		}
	}

	public function testDumpCarriesTheMigrationsTable (): void
	{
		$dump = file_get_contents (__DIR__ . '/../../dump/dump.sql');
		$this->assertStringContainsString ('CREATE TABLE `migrations`', $dump);
	}
}
