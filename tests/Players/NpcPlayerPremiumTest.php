<?php

use PHPUnit\Framework\TestCase;

/**
 * The NPC tribe owner (player 0) has no account row. A battle log against a tribe village asked it
 * for its display name, which reads premiumEndDate from that missing row (dolumar #61).
 */
class NpcPlayerPremiumTest extends TestCase
{
	protected function setUp (): void
	{
		set_error_handler (static function (int $no, string $msg, string $file, int $line): bool {
			throw new ErrorException ($msg, 0, $no, $file, $line);
		}, E_WARNING | E_DEPRECATED);
	}

	protected function tearDown (): void
	{
		restore_error_handler ();
	}

	public function testNpcOwnerIsNeverPremium (): void
	{
		$npc = new Dolumar_Players_NPCPlayer (0);
		$this->assertFalse ($npc->isProperPremium ());
		$this->assertFalse ($npc->isPremium ());
	}
}
