<?php

/**
 *  Test login for preview instances (one per pull request).
 *
 *  Previews have no OpenID Connect provider (every preview domain would need its own registered redirect URL), so this
 *  page lets anyone with the preview URL log in as one of the players created by tools/seed.php, or as a fresh
 *  account to test the race selection. It answers 404 unless PREVIEW_MODE=1, which production never sets.
 */

require_once '../bootstrap/bootstrap.php';

if (getenv('PREVIEW_MODE') !== '1') {
	http_response_code(404);
	exit;
}

$game = new Dolumar_Game();
$server = Neuron_GameServer::bootstrap();
$server->setGame($game);

$db = Neuron_DB_Database::getInstance();
$login = Neuron_Core_Login::__getInstance();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$plid = null;

	if (isset($_POST['new'])) {
		$count = $db->query('SELECT COUNT(*) AS c FROM n_players');
		$nickname = 'NewTester' . ((int)$count[0]['c'] + 1);
		// Registering sends a validation mail; without SMTP the mailer prints an error, which would break the redirect.
		ob_start();
		$plid = $login->registerAccount($nickname, strtolower($nickname) . '@preview.invalid');
		ob_end_clean();
		if ($plid) {
			// registerAccount() leaves the cached player with only the email loaded.
			Neuron_GameServer::getPlayer($plid)->reloadData();
		}
	} elseif (isset($_POST['plid'])) {
		$plid = (int)$_POST['plid'];
	}

	if ($plid && $login->doLogin($plid, false)) {
		session_regenerate_id(true);
		header('Location: ' . ABSOLUTE_URL . '?session_id=' . session_id());
		exit;
	}

	$error = 'Could not log in: ' . ($login->getError() ?: 'unknown error') .
		'. If this is "server_not_online", the daily cron has not run yet; wait a minute and try again.';
}

$players = $db->query('SELECT plid, nickname, isPlaying FROM n_players ORDER BY plid');

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<title>Dolumar preview login</title>
	<style>
		body { font-family: sans-serif; max-width: 32em; margin: 2em auto; padding: 0 1em; }
		button { display: block; width: 100%; margin: .3em 0; padding: .6em; font-size: 1em; text-align: left; }
		.error { color: #a00; }
	</style>
</head>
<body>
	<h1>Dolumar preview</h1>
	<p>This is a test instance for a pull request. Pick a player to log in as.</p>
	<?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
	<form method="post">
		<?php foreach ($players as $player): ?>
			<button name="plid" value="<?= (int)$player['plid'] ?>">
				<?= htmlspecialchars($player['nickname'] ?: 'Player ' . $player['plid']) ?>
				<?= $player['isPlaying'] ? '' : '(no village yet)' ?>
			</button>
		<?php endforeach; ?>
		<button name="new" value="1">Create a new player (starts at race selection)</button>
	</form>
</body>
</html>
