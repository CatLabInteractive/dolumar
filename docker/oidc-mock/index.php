<?php
/**
 *  Dolumar, browser based strategy game
 *  Copyright (C) 2009 Thijs Van der Schaeghe
 *  CatLab Interactive bvba, Gent, Belgium
 *  http://www.catlab.eu/
 *  http://www.dolumar.com/
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */


/*
	Dev-only OpenID Connect provider for the local Docker stack. Never deploy this.

	Run as a router script: OIDC_MOCK=1 php -S 0.0.0.0:8090 index.php

	The game calls /token and /profile from the server, without the browser's cookie, so the player
	number travels in the authorization code, which is returned as the access token: "mock" is player 1,
	"mock-2" player 2. /authorize?user=2 remembers player 2 in a cookie; ?user=1 switches back.
*/

if (getenv ('OIDC_MOCK') !== '1')
{
	http_response_code (503);
	exit ("Refusing to run: set OIDC_MOCK=1. This provider is for local development only.\n");
}

$path = parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

function mockToken (int $user): string
{
	return $user > 1 ? 'mock-' . $user : 'mock';
}

function mockJson (array $data): void
{
	header ('Content-Type: application/json');
	echo json_encode ($data);
}

/*
	Only redirect back to a local development host (localhost, 127.0.0.1, [::1], *.localhost, *.test),
	so the mock cannot be used as an open redirect.
*/
function isLocalRedirect (string $url): bool
{
	$parts = parse_url ($url);

	if (!is_array ($parts) || !isset ($parts['scheme'], $parts['host']) || !in_array (strtolower ($parts['scheme']), array ('http', 'https'), true))
	{
		return false;
	}

	$host = strtolower ($parts['host']);

	return in_array ($host, array ('localhost', '127.0.0.1', '[::1]'), true)
		|| substr ($host, -10) === '.localhost'
		|| substr ($host, -5) === '.test';
}

if ($path === '/authorize' && $method === 'GET')
{
	$redirect = isset ($_GET['redirect_uri']) && is_string ($_GET['redirect_uri']) ? $_GET['redirect_uri'] : '';
	if ($redirect === '' || !isLocalRedirect ($redirect))
	{
		http_response_code (400);
		exit ("Missing or non-local redirect_uri\n");
	}

	if (isset ($_GET['user']))
	{
		$user = max (1, (int) $_GET['user']);
		setcookie ('oidc_mock_user', (string) $user, 0, '/');
	}
	else
	{
		$user = max (1, (int) ($_COOKIE['oidc_mock_user'] ?? 1));
	}

	$query = http_build_query (array ('code' => mockToken ($user), 'state' => $_GET['state'] ?? ''));
	header ('Location: ' . $redirect . (strpos ($redirect, '?') === false ? '?' : '&') . $query, true, 302);
	return;
}

if ($path === '/token' && $method === 'POST')
{
	$code = isset ($_POST['code']) && is_string ($_POST['code']) ? $_POST['code'] : 'mock';
	mockJson (array ('access_token' => $code, 'token_type' => 'Bearer'));
	return;
}

if ($path === '/profile' && $method === 'GET')
{
	$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
	$token = preg_replace ('/^Bearer\s+/i', '', $auth);
	$user = preg_match ('/^mock-(\d+)$/', $token, $m) ? max (1, (int) $m[1]) : 1;

	mockJson (array (
		'id' => 'mock-' . $user,
		'email' => $user > 1 ? 'mock' . $user . '@example.com' : 'mock@example.com',
		'username' => $user > 1 ? 'Mock' . $user : 'Mock',
		'verified_email' => true
	));
	return;
}

http_response_code (404);
echo "Not found\n";
