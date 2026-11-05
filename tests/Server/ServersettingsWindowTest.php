<?php

use PHPUnit\Framework\TestCase;

/**
 * The server settings window shows the endgame in days when it is more than a day away, a live countdown in the
 * last day, and no countdown at all on a never-ending world.
 */
class ServersettingsWindowTest extends TestCase
{
	const DAY = 86400;
	const NOW = 1800000000;

	public function testNeverendingWorldHasNoEndgame (): void
	{
		$this->assertSame (
			Dolumar_Windows_Serversettings::ENDGAME_NEVER,
			Dolumar_Windows_Serversettings::getEndgameState (true, Dolumar_Players_Server::GAMESTATE_PLAYING, self::NOW + 10 * self::DAY, self::NOW)
		);
	}

	public function testMoreThanADayAwayShowsDays (): void
	{
		$this->assertSame (
			Dolumar_Windows_Serversettings::ENDGAME_DAYS,
			Dolumar_Windows_Serversettings::getEndgameState (false, Dolumar_Players_Server::GAMESTATE_PLAYING, self::NOW + self::DAY + 1, self::NOW)
		);
	}

	public function testLastDayShowsCountdown (): void
	{
		$this->assertSame (
			Dolumar_Windows_Serversettings::ENDGAME_COUNTDOWN,
			Dolumar_Windows_Serversettings::getEndgameState (false, Dolumar_Players_Server::GAMESTATE_PLAYING, self::NOW + self::DAY, self::NOW)
		);
	}

	public function testPassedDateOrEndgameStateMeansStarted (): void
	{
		$this->assertSame (
			Dolumar_Windows_Serversettings::ENDGAME_STARTED,
			Dolumar_Windows_Serversettings::getEndgameState (false, Dolumar_Players_Server::GAMESTATE_PLAYING, self::NOW - 1, self::NOW)
		);
		$this->assertSame (
			Dolumar_Windows_Serversettings::ENDGAME_STARTED,
			Dolumar_Windows_Serversettings::getEndgameState (false, Dolumar_Players_Server::GAMESTATE_ENDGAME_RUNNING, self::NOW + 5 * self::DAY, self::NOW)
		);
	}

	public function testFinishedRound (): void
	{
		$this->assertSame (
			Dolumar_Windows_Serversettings::ENDGAME_FINISHED,
			Dolumar_Windows_Serversettings::getEndgameState (false, Dolumar_Players_Server::GAMESTATE_WAITINGFORRESET, self::NOW - self::DAY, self::NOW)
		);
	}

	public function testDaysLeftRoundsDown (): void
	{
		// 4317 hours, the countdown from the old window.
		$this->assertSame (179, Dolumar_Windows_Serversettings::getDaysLeft (self::NOW + 4317 * 3600, self::NOW));
		$this->assertSame (1, Dolumar_Windows_Serversettings::getDaysLeft (self::NOW + 2 * self::DAY - 1, self::NOW));
		$this->assertSame (0, Dolumar_Windows_Serversettings::getDaysLeft (self::NOW - 10, self::NOW));
	}

	public function testFormatSpeed (): void
	{
		$this->assertSame ('x3', Dolumar_Windows_Serversettings::formatSpeed (3));
		$this->assertSame ('x1', Dolumar_Windows_Serversettings::formatSpeed (1.0));
		$this->assertSame ('x1.5', Dolumar_Windows_Serversettings::formatSpeed (1.5));
		$this->assertSame ('x0.25', Dolumar_Windows_Serversettings::formatSpeed ('0.25'));
	}
}
