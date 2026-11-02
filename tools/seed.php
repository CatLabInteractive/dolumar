<?php

/**
 *  Seeds an empty preview database with test players.
 *
 *  Only for preview instances (one per pull request). It refuses to run unless PREVIEW_MODE=1 and the database has no
 *  players yet, so it can never touch a real game. Runs after tools/setup.php in the preview postdeploy step.
 *
 *  Creates SEED_PLAYERS players (default 12) named Tester1, Tester2, ... with a village each, spread over the three
 *  selectable races and the map directions, with full resource stores and some extra runes. Tester1 to Tester4 share a
 *  clan led by Tester1. NPC tribes are generated next to every village, the same way cron/ai.php does it.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require_once __DIR__ . '/../bootstrap/bootstrap.php';

if (getenv('PREVIEW_MODE') !== '1') {
	fwrite(STDERR, "PREVIEW_MODE is not 1, refusing to seed.\n");
	exit(1);
}

$game = new Dolumar_Game();
$server = Neuron_GameServer::bootstrap();
$server->setGame($game);

$db = Neuron_DB_Database::getInstance();

$existing = $db->query('SELECT COUNT(*) AS c FROM n_players');
if ((int)$existing[0]['c'] > 0) {
	echo "Database already has players, not seeding.\n";
	exit(0);
}

$amount = max(1, (int)(getenv('SEED_PLAYERS') ?: 12));
// 'ne' is left out: on PHP 8 it crashes calculateNewStartLocation() (its arc wraps past 2, so mt_rand gets min > max).
$directions = ['n', 'e', 'es', 's', 'sw', 'w', 'wn'];
$races = array_keys(Dolumar_Races_Race::getRaces());
$login = Neuron_Core_Login::__getInstance();

$villages = [];
$clanMembers = [];

for ($i = 1; $i <= $amount; $i++) {
	$nickname = 'Tester' . $i;
	$id = $login->registerAccount($nickname, 'tester' . $i . '@preview.invalid');
	if (!$id) {
		fwrite(STDERR, "Could not register $nickname: " . $login->getError() . "\n");
		exit(1);
	}

	// registerAccount() leaves the cached player with only the email loaded; reload it before use.
	$player = Neuron_GameServer::getPlayer($id);
	$player->reloadData();
	// Rotate over the races this player may pick (Goblins need an unlock, for example).
	$selectable = array_values(array_filter($races, fn ($r) => Dolumar_Races_Race::getRace($r)->canPlayerSelect($player)));
	$race = $selectable[($i - 1) % count($selectable)];
	$direction = $directions[($i - 1) % count($directions)];

	if (!$player->initializeAccount($race, $direction)) {
		fwrite(STDERR, "Could not start a village for $nickname: " . $player->getError() . "\n");
		exit(1);
	}

	$village = $player->getMainVillage();

	// Fill the stores up to capacity (giveResources caps at capacity) and add some of every rune.
	$village->resources->giveResources(array_fill_keys(['gold', 'wood', 'stone', 'iron', 'grain', 'gems'], 1000000));
	foreach (array_keys($village->resources->getRunes()) as $rune) {
		$village->resources->giveRune($rune, 5);
	}

	$villages[] = $village;
	if ($i <= 4) {
		$clanMembers[] = $player;
	}

	echo "Created $nickname (" . Dolumar_Races_Race::getRaceFromId($race) . ", $direction)\n";
}

// One clan so clan screens have something to show.
if (count($clanMembers) > 1) {
	$clanId = $db->query("INSERT INTO clans SET c_name = 'Preview Testers', c_description = 'Seeded test clan.'");
	$clan = new Dolumar_Players_Clan($clanId);
	foreach ($clanMembers as $k => $member) {
		$clan->doJoinClan($member, $k === 0 ? 'leader' : 'member');
	}
	echo "Created clan Preview Testers\n";
}

foreach ($villages as $village) {
	$village->lookForNPCs();
}
echo 'NPC villages: ' . count(Dolumar_Players_Village::getAllNpc(false)) . "\n";

echo "Done.\n";
