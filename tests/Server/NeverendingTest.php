<?php

use PHPUnit\Framework\TestCase;

/**
 * NEVERENDING=1 turns off the endgame for a server. The flag is a constant, so the cases that switch it on run in
 * their own process.
 */
class NeverendingTest extends TestCase
{
	public function testOffWhenNotConfigured (): void
	{
		if (defined ('NEVERENDING')) {
			$this->markTestSkipped ('NEVERENDING is defined by the environment.');
		}

		$this->assertFalse (Dolumar_Players_Server::isNeverending ());
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testUpdateStatusNeverMovesTowardsTheEndgame (): void
	{
		define ('NEVERENDING', true);
		$this->assertTrue (Dolumar_Players_Server::isNeverending ());

		$server = $this->getMockBuilder (Dolumar_Players_Server::class)
			->disableOriginalConstructor ()
			->onlyMethods (['getData', 'setData', 'getStartDate'])
			->getMock ();

		// Not even the current state is read: no endgame scroll, no winner, no reset newsletter.
		$server->expects ($this->never ())->method ('getData');
		$server->expects ($this->never ())->method ('setData');
		$server->expects ($this->never ())->method ('getStartDate');

		$server->updateStatus ();
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testResetNeverWipesTheWorld (): void
	{
		define ('NEVERENDING', true);

		$server = $this->getMockBuilder (Dolumar_Players_Server::class)
			->disableOriginalConstructor ()
			->onlyMethods (['setData', 'clearCache'])
			->getMock ();

		$server->expects ($this->never ())->method ('setData');
		$server->expects ($this->never ())->method ('clearCache');

		// Without the guard this would connect to the database and truncate every table.
		$server->reset ();
	}
}
