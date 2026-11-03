<?php

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
	protected function tearDown (): void
	{
		putenv ('NPC_AGGRESSION');
		putenv ('NPC_PROTECTION_RUNES');
	}

	public function testAggressionDefaultsToCounter (): void
	{
		putenv ('NPC_AGGRESSION');
		$this->assertSame ('counter', Dolumar_Npc_Config::aggression ());
	}

	public function testAggressionReadsEnv (): void
	{
		putenv ('NPC_AGGRESSION=off');
		$this->assertSame ('off', Dolumar_Npc_Config::aggression ());
		putenv ('NPC_AGGRESSION=FULL');
		$this->assertSame ('full', Dolumar_Npc_Config::aggression ());
	}

	public function testAggressionFallsBackOnJunk (): void
	{
		putenv ('NPC_AGGRESSION=banana');
		$this->assertSame ('counter', Dolumar_Npc_Config::aggression ());
	}

	public function testProtectionRunes (): void
	{
		putenv ('NPC_PROTECTION_RUNES');
		$this->assertSame (10, Dolumar_Npc_Config::protectionRunes ());
		putenv ('NPC_PROTECTION_RUNES=25');
		$this->assertSame (25, Dolumar_Npc_Config::protectionRunes ());
		putenv ('NPC_PROTECTION_RUNES=abc');
		$this->assertSame (10, Dolumar_Npc_Config::protectionRunes ());
	}
}
