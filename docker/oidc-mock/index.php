<?php
/**
 * Dev-only OpenID Connect provider for the local Docker stack. Never deploy this.
 *
 * Run as a router script: OIDC_MOCK=1 php -S 0.0.0.0:8090 index.php
 *
 * The game calls /token and /profile from the server, without the browser's cookie, so the player number
 * travels in the authorization code, which is returned as the access token: "mock" is player 1, "mock-2" player 2.
 */

if (getenv('OIDC_MOCK') !== '1') {
	http_response_code(503);
	exit("Refusing to run: set OIDC_MOCK=1. This provider is for local development only.\n");
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

function mockToken(int $user): string
{
	return $user > 1 ? 'mock-' . $user : 'mock';
}

function json(array $data): void
{
	header('Content-Type: application/json');
	echo json_encode($data);
}

if ($path === '/authorize' && $method === 'GET') {
	$redirect = $_GET['redirect_uri'] ?? '';
	if ($redirect === '') {
		http_response_code(400);
		exit("Missing redirect_uri\n");
	}

	if (isset($_GET['user'])) {
		setcookie('oidc_mock_user', (string) max(1, (int) $_GET['user']), 0, '/');
		$user = max(1, (int) $_GET['user']);
	} else {
		$user = max(1, (int) ($_COOKIE['oidc_mock_user'] ?? 1));
	}

	$query = http_build_query(['code' => mockToken($user), 'state' => $_GET['state'] ?? '']);
	header('Location: ' . $redirect . (strpos($redirect, '?') === false ? '?' : '&') . $query, true, 302);
	return;
}

if ($path === '/token' && $method === 'POST') {
	$code = $_POST['code'] ?? 'mock';
	json(['access_token' => $code, 'token_type' => 'Bearer']);
	return;
}

if ($path === '/profile' && $method === 'GET') {
	$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
	$token = preg_replace('/^Bearer\s+/i', '', $auth);
	$user = preg_match('/^mock-(\d+)$/', $token, $m) ? max(1, (int) $m[1]) : 1;

	json([
		'id' => 'mock-' . $user,
		'email' => $user > 1 ? 'mock' . $user . '@example.com' : 'mock@example.com',
		'username' => $user > 1 ? 'Mock' . $user : 'Mock',
		'verified_email' => true,
	]);
	return;
}

http_response_code(404);
echo "Not found\n";
