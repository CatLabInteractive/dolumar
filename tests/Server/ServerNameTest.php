<?php

use PHPUnit\Framework\TestCase;

/**
 * SERVER_NAME sets the world name shown in the browser tab title. The flag is a constant, so the cases that set it
 * run in their own process.
 */
class ServerNameTest extends TestCase
{
	private function mockServer (bool $installed, ?string $currentName)
	{
		$server = $this->getMockBuilder (Dolumar_Players_Server::class)
			->disableOriginalConstructor ()
			->onlyMethods (['isInstalled', 'getServerName', 'setData'])
			->getMock ();
		$server->method ('isInstalled')->willReturn ($installed);
		$server->method ('getServerName')->willReturn ($currentName);
		return $server;
	}

	public function testNothingHappensWhenNotConfigured (): void
	{
		if (defined ('SERVER_NAME') && SERVER_NAME !== '') {
			$this->markTestSkipped ('SERVER_NAME is set by the environment.');
		}

		$this->assertNull (Dolumar_Players_Server::getConfiguredName ());

		$server = $this->mockServer (true, 'Dolumar');
		$server->expects ($this->never ())->method ('setData');
		$this->assertFalse ($server->syncServerName ());
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testStoresAChangedName (): void
	{
		define ('SERVER_NAME', 'x3 Speed');
		$this->assertSame ('x3 Speed', Dolumar_Players_Server::getConfiguredName ());

		$server = $this->mockServer (true, 'Dolumar');
		$server->expects ($this->once ())->method ('setData')->with ('servername', 'x3 Speed');
		$this->assertTrue ($server->syncServerName ());
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testLeavesAnUnchangedNameAlone (): void
	{
		define ('SERVER_NAME', 'x3 Speed');

		$server = $this->mockServer (true, 'x3 Speed');
		$server->expects ($this->never ())->method ('setData');
		$this->assertFalse ($server->syncServerName ());
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testWaitsForTheInstallWhenNotInstalled (): void
	{
		define ('SERVER_NAME', 'x3 Speed');

		$server = $this->mockServer (false, null);
		$server->expects ($this->never ())->method ('setData');
		$this->assertFalse ($server->syncServerName ());
	}
}
