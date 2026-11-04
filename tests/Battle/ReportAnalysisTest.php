<?php

use PHPUnit\Framework\TestCase;

/**
 * Dolumar_Battle_ReportAnalysis reads the raw battle_report columns. The fixtures are battles fought on a local
 * test world (no player data): 7301 is a 58% attacker victory with three regiments a side, 7302 a repelled attack,
 * 7298 a near total victory against a tribe.
 */
class ReportAnalysisTest extends TestCase
{
	private function analyse (int $id, ?int $cutoff = null): Dolumar_Battle_ReportAnalysis
	{
		$data = json_decode (file_get_contents (__DIR__ . '/fixtures/report-' . $id . '.json'), true);
		return new Dolumar_Battle_ReportAnalysis ($data['battleLog'], $data['fightLog'], $data['slots'], $cutoff);
	}

	public function testArmyStartsAtSurvivorsPlusDead (): void
	{
		$army = $this->analyse (7301)->getArmy (Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER);

		$this->assertCount (3, $army);
		$this->assertSame (120, $army[0]['amount']);
		$this->assertSame (13, $army[0]['died']);
		$this->assertSame (107, $army[0]['left']);
		$this->assertSame ('held', $army[0]['fate']);
		$this->assertSame ('fled', $army[2]['fate']);
		$this->assertSame (array (1), $army[0]['squads']);
	}

	public function testPhasesAndRounds (): void
	{
		$analysis = $this->analyse (7301);
		$events = $analysis->getEvents ();

		$this->assertSame ('phase', $events[0]['type']);
		$this->assertSame ('shooting', $events[2]['type']);
		$this->assertSame (Dolumar_Battle_ReportAnalysis::PHASE_SHOOTING, $events[2]['phase']);
		$this->assertSame (8, $analysis->countRounds ());

		// A defender move after the defenders' melee opens the next round.
		$this->assertSame ('move', $events[18]['type']);
		$this->assertSame (2, $events[18]['round']);
		$this->assertSame (1, $events[17]['round']);

		$groups = $analysis->getGroups ();
		$this->assertSame ('shooting', $groups[0]['phase']);
		$this->assertSame ('melee', $groups[1]['phase']);
		$this->assertSame (1, $groups[1]['round']);
		$this->assertCount (9, $groups);
	}

	public function testKeyMomentsAreInFightOrderWithoutDuplicates (): void
	{
		$moments = $this->analyse (7301)->getMoments (Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER, Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER);

		$kinds = array_map (fn ($m) => $m['kind'], $moments);
		$this->assertSame (array ('volley', 'turningpoint'), $kinds);

		// The biggest volley is also the reader's heaviest loss: listed once.
		$this->assertSame (43, $moments[0]['event']['kills']);
		$this->assertSame ('fled', $moments[1]['event']['type']);
		$this->assertSame (Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER, $moments[1]['event']['team']);
	}

	public function testHeroIsTheUnitWithMostKills (): void
	{
		$hero = $this->analyse (7301)->getHero (Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER);
		$this->assertSame ('8_3427_1_4', $hero['key']);
		$this->assertSame (33, $hero['kills']);
	}

	public function testReplayFramesTrackHeadcountsAndStatus (): void
	{
		$replay = $this->analyse (7301)->getReplay ();

		$this->assertCount (6, $replay['tokens']);
		$this->assertCount (count ($this->analyse (7301)->getEvents ()), $replay['frames']);

		// Event 8: the defending Guards' volley kills 43 Spearmen (token 2).
		$frame = $replay['frames'][8];
		$this->assertSame (5, $frame['actor']);
		$this->assertSame (2, $frame['target']);
		$this->assertSame ('hit', $frame['state'][2][2]);
		$this->assertSame ('acting', $frame['state'][5][2]);

		// The moved Guards end up in slot 2.
		$last = end ($replay['frames']);
		$this->assertSame (2, $last['state'][5][0]);
		$this->assertSame ('gone', $last['state'][2][2]);
	}

	public function testCutoffStopsALiveReport (): void
	{
		$analysis = $this->analyse (7301, 200);

		$this->assertFalse ($analysis->isComplete ());
		$this->assertCount (11, $analysis->getEvents ());
		$this->assertTrue ($this->analyse (7301)->isComplete ());
	}

	public function testChronicleFacts (): void
	{
		$facts = $this->analyse (7301)->getChronicle ();

		$this->assertSame (17, $facts['shooting'][Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER]);
		$this->assertSame (53, $facts['shooting'][Dolumar_Battle_ReportAnalysis::TEAM_DEFENDER]);
		$this->assertSame (43, $facts['shootingBest']['kills']);
		$this->assertSame ('8_3427_1_4', $facts['meleeBest'][Dolumar_Battle_ReportAnalysis::TEAM_ATTACKER]['unit']);
		$this->assertCount (4, $facts['broke']);
	}

	public function testEmptyAndBrokenInputDoNotFail (): void
	{
		$analysis = new Dolumar_Battle_ReportAnalysis ('', '', null);
		$this->assertSame (array (), $analysis->getEvents ());
		$this->assertSame (array (), $analysis->getArmy (1));
		$this->assertSame (array (), $analysis->getMoments (2, 1));

		$analysis = new Dolumar_Battle_ReportAnalysis ('0|11|5|1|not json|{}|3;', '1:2', '{"attacking":1}');
		$events = $analysis->getEvents ();
		$this->assertCount (1, $events);
		$this->assertNull ($events[0]['actor']);
	}
}
