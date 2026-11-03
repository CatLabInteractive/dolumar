# Dolumar Credits Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Every premium purchase in Dolumar works again, paid with Dolumar credits on CatLab Accounts.

**Architecture:** The dead `BBGS_Credits` client is replaced by an Accounts client in the engine:
- `Neuron_GameServer_Credits_AccountsClient` handles the HTTP calls.
- `Neuron_GameServer_Credits_PurchaseService` runs the purchase lifecycle on a new table `n_credit_purchases`.

`Neuron_GameServer_Credits` keeps its public methods, so the windows barely change. `getCreditUseUrl()` now returns a link to a Dolumar start endpoint. That endpoint recomputes the price on the server through `Player::quoteCreditAction()`, records a pending purchase, creates the Accounts credit request and redirects to its confirm page. Accounts' notify call is verified against Accounts before `Player::useCredit()` runs. When the action can't be applied, the purchase is refunded automatically.

**Tech Stack:**
- PHP 8.4, Neuron engine (`~/Workbench/dolumar-engine`, branch `php8`) and the game (`~/Workbench/dolumar`, branch `dolumar-credits`)
- league/oauth2-client, plus Guzzle, which it already pulls in
- MySQL 8
- PHPUnit 11: engine tests run in the docker web container with `docker compose exec -T web sh -c 'cd /engine && vendor/bin/phpunit'`

**Spec:** `docs/superpowers/specs/2026-11-02-dolumar-credits-design.md`. Accounts' side is a separate plan: `~/Workbench/accounts-credit-types/.ai/superpowers/plans/2026-11-02-credit-type-per-product.md`.

## Global Constraints

- **Repos and branches:**
  - Game: `dolumar-credits`. It's stacked on `php8-dokku-migration` (PR #3); rebase onto master once #3 merges.
  - Engine: create `credits-accounts` from `php8`.
  - Check `git branch --show-current` before every commit. Never commit on master or develop.
- **Commit trailer:**
  ```
  Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_01NrB7faDXcYL34b6gbit2Jd
  ```
- **Credit costs are unchanged:**
  - premium: `PREMIUM_COST_CREDITS` = 100 for 15 days
  - runes: 75 each
  - resources: 150 for one, 900 for all
  - move village: 750
  - move building: 100, free within 3600s of a village move
  - bonus building: 250
  - speed-up: `PRICE × units`, using the `PREMIUM_SPEEDUP_*` constants
- **Purchases expire one hour** (3600s) after they are created.
- **Never trust the browser or the callback:**
  - The action and credits come from Dolumar's own `n_credit_purchases` row.
  - A grant needs Accounts `GET /api/1.0/credits/requests/{id}` (client credentials) to report `status == ACCEPTED`, `credits ==` the purchase's credits, and the charged profile `user.id ==` the player's stored profile id.
  - Each `credit_request_id` is granted at most once (unique key).
- **Automatic refunds** always pass `credit_request_id`.
- **Configuration:**
  - Credits are on only when `OPENID_CONNECT_CLIENT_ID`, `OPENID_CONNECT_SECRET` and `CATLAB_CREDITS_TYPE_ID` are defined.
  - `CATLAB_ACCOUNTS_URL` defaults to `https://accounts.catlab.eu/`.
  - While credits are off, every purchase link is hidden.
- **Tokens:** never log or print access tokens.
- **Old service:** the old `BBGS_Credits` signing paths (tracker, newsletter, BrowserGamesHub) stay as they are, guarded by the old constants, which stay unset.
- **Every game schema change** goes in an idempotent file under `dump/upgrades/` *and* in `dump/dump.sql`.

## Review Focus

1. A notify POST for a request belonging to another player, another product, a different amount, still PENDING, or already granted: nothing is granted and the purchase is unchanged. Test: `PurchaseServiceTest`.
2. A player confirms on Accounts after the purchase expired, or the action is no longer possible (spot taken, building finished, rune cap): the credits are refunded with `credit_request_id` and the purchase is marked `refunded`. Tests: `PurchaseServiceTest::testExpiredPurchaseIsRefunded` and `testInapplicableActionIsRefunded`.
3. A crafted start link with a tampered `amount`, a negative or huge speed-up `duration`, or another player's village id: the price comes from `quoteCreditAction`, and invalid input gives no purchase at all. Test: `QuoteCreditActionTest`.
4. Accounts unreachable during start, notify or reconcile: the player sees a friendly message, no PHP fatal, and the purchase stays pending for reconcile. Test: `PurchaseServiceTest::testAccountsDownLeavesPending`.
5. A player who logged in before this release has no token or profile id. Purchase links send them through login again instead of failing. Test: `CreditsTest::testMissingTokenAsksForLogin`.

---

### Task 1: Idempotent schema upgrades on deploy

**Files:**
- Modify: `tools/setup.php` (game)
- Create: `dump/upgrades/20261102-credits.sql`, `dump/upgrades/README.md`
- Modify: `dump/dump.sql`

**Interfaces:**
- Produces, as tables:
  - `n_auth_catlab (openid_url VARCHAR(255) PK, profile_id INT NULL, access_token TEXT NULL, scope VARCHAR(255) NULL, updated_at DATETIME NOT NULL)`
  - `n_credit_purchases`, with columns:
    - `id INT AUTO_INCREMENT PK`
    - `plid INT NOT NULL`
    - `credits INT NOT NULL`
    - `description VARCHAR(255) NOT NULL`
    - `action_data TEXT NOT NULL` (JSON)
    - `status ENUM('pending','granted','refunded','expired','failed') NOT NULL DEFAULT 'pending'`
    - `credit_request_id INT NULL UNIQUE`
    - `created_at DATETIME NOT NULL`
    - `expires_at DATETIME NOT NULL`
    - `updated_at DATETIME NOT NULL`
    - `KEY (plid, status)`
    - `KEY (status, expires_at)`

- [ ] **Step 1: Write the upgrade files.**
  - `dump/upgrades/20261102-credits.sql` uses only `CREATE TABLE IF NOT EXISTS` with the two tables above, utf8mb4.
  - Add the same statements to the end of `dump/dump.sql`.
  - `dump/upgrades/README.md` states the rule: files run in name order on every deploy and must be safe to run again.
- [ ] **Step 2: Extend `tools/setup.php`.**
  - After the existing "already installed" check, when `n_players` exists, run every `dump/upgrades/*.sql` in `sort()` order through the same direct-mysqli multi-statement path and drain the results.
  - Print `Applied upgrade <file>` for each file.
  - Exit 1 on any error.
- [ ] **Step 3: Verify on the local stack.**
  - Run `docker compose exec -T web php tools/setup.php` twice. Both runs exit 0 and print the upgrade line.
  - `SHOW TABLES LIKE 'n_credit%'` lists the table.
  - The production-snapshot row counts are unchanged.
- [ ] **Step 4: Commit:** "Apply idempotent schema upgrades on deploy; add credits tables".

### Task 2: Accounts HTTP client (engine)

**Files:**
- Create: `src/Neuron/GameServer/Credits/AccountsClient.php`
- Test: `tests/Credits/AccountsClientTest.php`

**Interfaces:**
- Produces: `class Neuron_GameServer_Credits_AccountsClient`
  - `__construct(string $baseUrl, string $clientId, string $clientSecret, ?GuzzleHttp\ClientInterface $http = null)`
  - `static fromConfig(): ?self` returns null when credits are not configured (see Global Constraints)
  - `getPersonalProfileId(string $accessToken): ?int` sends `GET api/1.0/profiles` with a Bearer token and returns the profile id with `personal === true`
  - `getBalance(string $accessToken): ?int` sends `GET api/1.0/users/me/credits/credit-price` with Bearer and returns `price.available`
  - `createRequest(string $accessToken, int $profileId, int $credits, string $reason, string $notifyUrl, string $returnUrl, array $extra): array{id:int, confirmUrl:string}` sends `POST api/1.0/profiles/{profileId}/credits/request` with a JSON body of `credits`, `reason`, `notify_url`, `return` and `extra`
  - `getLoginToken(string $accessToken): ?string` sends `POST api/1.0/users/me/login-token` with Bearer, the same call QuizWitz makes (check `api.quizwitz.com` `LoginTokenClient` for the response key)
  - `getRequest(int $requestId): ?array` sends `GET api/1.0/credits/requests/{id}` with HTTP Basic `clientId:clientSecret`. It returns the decoded body, or null on 404.
  - `refund(int $profileId, int $credits, string $reason, ?int $creditRequestId): bool` sends `POST api/1.0/profiles/{profileId}/credits/refund` with Basic auth
  - `getBuyUrl(): string` returns `{baseUrl}credits/{CATLAB_CREDITS_TYPE_ID}/purchase`
  - Every network or HTTP ≥ 500 failure throws `Neuron_GameServer_Credits_AccountsUnavailable` (a new `RuntimeException` subclass, same file). HTTP 4xx returns null or false.

- [ ] **Step 1: Write the failing tests** with a Guzzle `MockHandler` and a history middleware:
  - `testCreateRequestSendsBearerAndBody`: asserts the method, the path `api/1.0/profiles/42/credits/request`, the `Authorization: Bearer tok` header and the JSON body keys, and returns `['id'=>7,'confirmUrl'=>'https://a/credits/request/7/x']`.
  - `testGetRequestUsesBasicAuth`: asserts the Basic header for `cid:secret`, and that a 404 gives null.
  - `testPersonalProfileIsPicked`: a body with two profiles where only the second has `personal: true` gives the second's id.
  - `testServerErrorThrowsUnavailable`: a 503 throws `Neuron_GameServer_Credits_AccountsUnavailable`.
  - `testBuyUrl`: with base `https://accounts.catlab.eu/` and type `2`, the URL is `https://accounts.catlab.eu/credits/2/purchase`.
- [ ] **Step 2: Run them and see them fail.**
- [ ] **Step 3: Implement the client.**
- [ ] **Step 4: Run the engine suite and see it pass.**
- [ ] **Step 5: Commit on the engine branch `credits-accounts`:** "Accounts credits API client".

### Task 3: Keep the Accounts token and profile at login

**Files:**
- Modify (engine): `src/Neuron/Auth/OpenIDConnect.php` (`SCOPE`, `getProfile` return), `src/Neuron/GameServer/scripts/openid2/login.php`
- Create (engine): `src/Neuron/GameServer/Mappers/CatLabAuthMapper.php`
- Modify (engine): `tests/Auth/OpenIDConnectTest.php`
- Test: `tests/Mappers/CatLabAuthMapperTest.php` (DB-backed like `ChatMapperTest`)

**Interfaces:**
- Consumes: Task 1's `n_auth_catlab`, and Task 2's `getPersonalProfileId`.
- Produces:
  - `Neuron_Auth_OpenIDConnect::SCOPE = 'openid email profile credits'`
  - `getProfile()` additionally returns `'access_token' => string`
  - `Neuron_GameServer_Mappers_CatLabAuthMapper::save(string $openIdUrl, ?int $profileId, string $accessToken, string $scope): void`, which upserts
  - `getForPlayer(Neuron_GameServer_Player $player): ?array{openid_url:string, profile_id:?int, access_token:string, scope:string}`, which joins `n_auth_openid.user_id` with the CatLab openid URL prefix and takes the most recently updated row

- [ ] **Step 1: Update `OpenIDConnectTest`** so it expects `access_token` = `'token'` in the profile and `credits` in the authorize URL's scope. Add `CatLabAuthMapperTest`:
  - `testSaveAndLoadForPlayer`: insert a player and its `n_auth_openid` row, save, then load.
  - `testSaveUpdates`: a second save replaces the token and the profile id.
- [ ] **Step 2: Run them and see them fail.**
- [ ] **Step 3: Implement.** In `login.php`, after `getProfile`:
  - When `AccountsClient::fromConfig()` is not null, call `getPersonalProfileId`.
  - Catch `AccountsUnavailable` and save `profile_id` NULL in that case.
  - `save()` the URL, profile, token and scope before `handleAuthentication`.
  - A failure here must never block login.
- [ ] **Step 4: Run the engine suite and see it pass.**
- [ ] **Step 5: Commit:** "Request the credits scope and keep the Accounts token per account".

### Task 4: Purchase service: start, notify, reconcile, refund (engine)

**Files:**
- Create: `src/Neuron/GameServer/Credits/PurchaseService.php`, `src/Neuron/GameServer/Mappers/CreditPurchaseMapper.php`
- Modify: `src/Neuron/GameServer/Player.php`. Make the engine default `useCredit($amount, $data)` return `bool` (premium: extend 15 days, return true), and add `quoteCreditAction(array $data): ?array`. The default returns `['credits' => PREMIUM_COST_CREDITS, 'description' => 'Premium account (15 days)']` when `action` is empty or `'premium'`, and null otherwise.
- Test: `tests/Credits/PurchaseServiceTest.php`

**Interfaces:**
- Consumes: Task 2's client (inject a fake in tests), Task 3's `CatLabAuthMapper::getForPlayer`.
- Produces: `class Neuron_GameServer_Credits_PurchaseService`
  - `__construct(Neuron_GameServer_Credits_AccountsClient $client, ?callable $now = null)`
  - `start(Neuron_GameServer_Player $player, array $data): array{status:string, url:?string}`. The status is one of:
    - `'redirect'`: the URL is `confirmUrl` + `?authcode=<login token>` when one is available
    - `'login'`: no token or profile id
    - `'invalid'`: the quote is null
    - `'unavailable'`
  - `handleNotify(int $creditRequestId): string` returns one of `'granted'|'refunded'|'ignored'|'pending'`
  - `reconcile(int $limit = 50): int` checks pending purchases that have a request id and returns how many it settled
  - `refundPurchase(int $purchaseId, string $reason): bool` is for the admin tool
  - `getPlayerPurchases(Neuron_GameServer_Player $player): array` lists purchases for admin display
- Rules, which the tests pin:
  - `start` takes `credits` and `description` from `$player->quoteCreditAction($data)`. Any `amount` in `$data` is ignored.
  - It inserts a pending row with `expires_at = now + 3600`, then calls `createRequest` with:
    - `extra = ['purchase_id' => id]`
    - `notify_url = API_FULL_URL . 'creditnotify/'`
    - `return = ABSOLUTE_URL`
  - It then stores `credit_request_id` on the row.
  - `handleNotify` loads the purchase by `credit_request_id` and returns `ignored` if there is none, or if it is not pending. It then calls `getRequest`:
    - status not `ACCEPTED` → `pending`
    - credits ≠ row credits, or `user.id` ≠ the player's profile id → `ignored`, with an `error_log`
    - row expired → refund, status `refunded`
    - otherwise `$player->useCredit($row credits, $row action_data)`: true → `granted`, false → refund → `refunded`
  - Status changes use `UPDATE ... WHERE id = ? AND status = 'pending'` and act only when one row changed, so concurrent notifies grant once.
  - `reconcile` runs the same path for pending rows with a request id. It marks rows `expired` when they are past `expires_at` and Accounts still says PENDING; a later notify then refunds.

- [ ] **Step 1: Write the failing tests** with a fake client recording calls and a fixed clock:
  - `testStartUsesServerQuoteNotAmount`: `data = ['action'=>'premium','amount'=>1]` creates a request for 100 credits.
  - `testStartWithoutTokenAsksLogin`
  - `testStartInvalidActionCreatesNothing`
  - `testNotifyGrantsOnce`: two notifies give `granted` then `ignored`, and `useCredit` is called once.
  - `testNotifyPendingDoesNothing`
  - `testNotifyWrongAmountIgnored`
  - `testNotifyWrongProfileIgnored`
  - `testUnknownRequestIgnored`
  - `testExpiredPurchaseIsRefunded`: the refund is called with that `credit_request_id`.
  - `testInapplicableActionIsRefunded`: `useCredit` returns false.
  - `testAccountsDownLeavesPending`: `getRequest` throws `AccountsUnavailable` → `pending`, and the row stays pending.
  - `testReconcileSettlesAcceptedAndExpiresOld`
- [ ] **Step 2: Run them and see them fail.**
- [ ] **Step 3: Implement the mapper and the service.**
- [ ] **Step 4: Run the engine suite and see it pass.**
- [ ] **Step 5: Commit:** "Credit purchase lifecycle on CatLab Accounts".

### Task 5: Wire the engine to the new service

**Files:**
- Modify: `src/Neuron/GameServer/Credits.php`. Its public methods keep their names:
  - `getPureCreditsObject` → returns `AccountsClient::fromConfig()`
  - `getCredits` → `getBalance` with the player's token, or null
  - `getBuyUrl` → `getBuyUrl()`
  - `getUseUrl($amount, $data, $description)` → `API_FULL_URL . 'creditstart/?' . http_build_query($data)`, or null when off. `$amount` is no longer used.
  - `refundCredits($amount, $description, $action)` → `client->refund(profileId, $amount, $description, null)`, used by the manual admin refund
  - `handleUseRequest` → removed
  - `convertCredits`/`getCreditDisplay` → passthrough
- Modify: `src/Neuron/GameServer/scripts/api/api.php`:
  - Replace the `spendcredit` branch with `creditstart`. It needs the logged-in session player (`Neuron_GameServer::getPlayer()`, no `id` parameter) and calls `PurchaseService::start($player, $_GET minus module params)`:
    - `redirect` → `header('Location: ' . $url)`
    - `login` → redirect to `oauth2/login`
    - otherwise a short HTML message
  - Add `creditnotify`: a POST that reads `credit_request_id` (int) and calls `handleNotify`. It answers HTTP 200 with the result word, even for `ignored`, so Accounts never retries forever.
- Modify: `src/Neuron/GameServer/Player.php`. Remove the `handleUseRequest` proxy.
- Modify: `src/Neuron/GameServer/Pages/Admin/Premium.php`. With `?refund=1`, refund only purchases with `action` premium and status `granted`, through `PurchaseService::refundPurchase`. List the players it skipped.
- Test: `tests/Credits/CreditsTest.php`

- [ ] **Step 1: Write the failing tests:**
  - `testUseUrlIsLocalStartLink`: with credits configured, `getUseUrl(1, ['action'=>'buyrunes','village'=>3])` is `API_FULL_URL.'creditstart/?action=buyrunes&village=3'`.
  - `testUseUrlNullWhenOff`
  - `testMissingTokenAsksForLogin`: `creditstart` for a player without a stored token redirects to `oauth2/login`.
  - `testNotifyEndpointAlways200`: a POST with an unknown id gives 200 and the body `ignored`.
- [ ] **Step 2: Run them and see them fail.**
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run the engine suite.** Also run `grep -rn "spendcredit\|handleUseRequest" src` in both repos and confirm nothing is left.
- [ ] **Step 5: Commit:** "Route credit purchases through Accounts; remove the old spend callback".

### Task 6: Game actions: server-side quotes and applied/not-applied results

**Files:**
- Modify (game): `src/Dolumar/Players/Player.php`. Each `useCredit` branch returns true only when the action was actually applied, and a new `quoteCreditAction(array $data): ?array` overrides the engine default.
- Modify (game): `src/Dolumar/Windows/Premiumshop.php`, `Speedup.php`, `Bonusbuildings.php`. The price constants move to a new `src/Dolumar/Premium/Prices.php` (`class Dolumar_Premium_Prices`, holding the constants from Global Constraints plus a speed-up price helper). Windows and `quoteCreditAction` both use it.
- Test (game): add a minimal game PHPUnit setup:
  - `phpunit.xml`
  - `tests/bootstrap.php`, which loads `bootstrap/bootstrap.php` against the docker DB with the production snapshot
  - `tests/QuoteCreditActionTest.php`

**Interfaces:**
- Consumes: Task 4's engine contract (`useCredit` returns bool; the quote is `['credits'=>int,'description'=>string]` or null).
- Produces: `Dolumar_Players_Player::quoteCreditAction(array $data): ?array` for these actions, with the same parameters the windows put in `$data` today (read them from each window's `$data`):
  - `premium` (or none): 100
  - `buyrunes`: 75 × the sum of rune counts. The sum must be ≥ 1 and must not exceed the remaining monthly cap.
  - `buyresources`: 150, or 900 for `all`
  - `movevillage`: 750
  - `movebuilding`: 100. It returns null inside the free hour, because that path doesn't use credits.
  - `bonusbuilding`: 250
  - `speedup`: price × units for its type. The units must be ≥ 1, and must not exceed what is needed to finish the building, training or scouting.
  
  The village or building in `$data` must belong to `$this`; otherwise the quote is null.

- [ ] **Step 1: Write the failing tests** against the snapshot player plid 9 and their village (`SELECT vid FROM villages WHERE plid=9 LIMIT 1`):
  - `testPremiumQuote`: 100
  - `testRunesQuote`: 2 runes → 150
  - `testRunesOverCapIsNull`
  - `testResourcesQuote`: one → 150, all → 900
  - `testSpeedupNegativeOrZeroUnitsIsNull`
  - `testSpeedupHugeUnitsIsNull`
  - `testOtherPlayersVillageIsNull`: a village not owned by plid 9 gives null
  - `testUnknownActionIsNull`
  - `testUseCreditReportsFailure`: `movebuilding` to an occupied tile returns false
- [ ] **Step 2: Run them and see them fail.** Use `docker compose exec -T web vendor/bin/phpunit`.
- [ ] **Step 3: Implement `Dolumar_Premium_Prices`, `quoteCreditAction` and the boolean returns.** Switch the windows to `Dolumar_Premium_Prices`. Keep their `getCreditUseUrl(...)` calls; the URL is now the local start link.
- [ ] **Step 4: Run the game tests, the engine suite and the local smoke test** (`SMOKE_NO_OAUTH=1 tools/smoke.sh http://localhost:8080`). Then open the premium shop, speed-up and bonus building windows as plid 9 with the window sweep. They must show no PHP errors.
- [ ] **Step 5: Commit (game):** "Server-side credit quotes; purchases report whether they applied".

### Task 7: Hide purchases while credits are off, reconcile from cron, config and docs

**Files:**
- Modify (game): `src/Dolumar/Windows/Statusbar.php:51-64` (show speed-up links only when `Neuron_GameServer_Credits::getPureCreditsObject()` is not null), `cron/constantly.php` (call `PurchaseService::reconcile()` when credits are configured), `bootstrap/serverconfig-default.php` (`defEnvOrDefault('CATLAB_ACCOUNTS_URL', 'https://accounts.catlab.eu/')`, `defEnvOrDefault('CATLAB_CREDITS_TYPE_ID')`), `.env.example`, `README.md` (the Accounts setup steps from the spec's Rollout)
- Modify (game): `src/Dolumar/Windows/Premiumshop.php`. Call `reconcile` for this player's pending purchases when the window opens.
- Modify (game): `composer.lock`, to bump the engine to the `credits-accounts` head the same way earlier engine bumps were done (edit the reference hash).
- Test: the window sweep and the smoke test.

- [ ] **Step 1: Make the changes.**
- [ ] **Step 2: Verify with credits off** (the local default): the status bar shows no speed-up links, and the premium shop shows no buy or purchase links and no errors.
- [ ] **Step 3: Verify with credits on locally.**
  - Set the three env vars, with a fake `CATLAB_ACCOUNTS_URL` that refuses connections.
  - The links appear.
  - `creditstart` shows the friendly "unavailable" message.
  - The cron run logs no fatal errors.
- [ ] **Step 4: Run all suites and the smoke test.**
- [ ] **Step 5: Commit:** "Hide purchases when credits are off; reconcile from cron; config and docs".

### Task 8: End-to-end check against a local Accounts

**Files:** none. This is a verification task, recorded in the ledger.

- [ ] **Step 1: Run Accounts locally** from `~/Workbench/accounts-credit-types`, with its plan implemented, using its docker setup.
  - Create a Dolumar product, a credit type priced 0.01157 with the Dolumar ladder, and an OIDC client for `http://localhost:8080/oauth2/login/next` with the `credits` scope.
  - Grant 2000 credits to a test user with the admin tool.
- [ ] **Step 2: Point the local Dolumar at it** (the `OPENID_CONNECT_*`, `CATLAB_ACCOUNTS_URL` and `CATLAB_CREDITS_TYPE_ID` env vars). Then:
  - log in
  - buy premium, 2 runes, one resource, and a speed-up
  - confirm each on Accounts
- [ ] **Step 3: Check the results.**
  - Each purchase applied once.
  - `n_credit_purchases` rows are `granted`.
  - The Accounts balance dropped by exactly 100 + 150 + 150 + the speed-up price.
- [ ] **Step 4: Make an action inapplicable,** for example by finishing the building before confirming the speed-up. Then confirm on Accounts: the purchase is `refunded` and the balance is restored.
- [ ] **Step 5: Record the results in the ledger.**
