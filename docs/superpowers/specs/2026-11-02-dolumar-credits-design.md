# Dolumar credits on CatLab Accounts — design

Date: 2026-11-02. Status: draft, awaiting owner approval. Points marked "my call" were settled by Claude, not the owner.

## Intent

Dolumar's premium features used to be paid with credits from a separate CatLab credits service (credits.catlab.eu, code in `../catlab-credits`). That service is dead, so every premium purchase in the game is currently switched off. CatLab Accounts (accounts.catlab.eu) now has its own credits system, which QuizWitz uses for seats. The owner had always planned to move Dolumar onto it. The goal is to bring back every premium purchase, paid with **Dolumar credits**: a credit type of its own in Accounts, separate from QuizWitz credits, that players can buy both from inside the game and from the Accounts credits page.

## Goals

- All purchases that exist in the game today work again, at today's credit costs:
  - premium account: 100 credits for 15 days
  - runes: 75 each, with the monthly cap
  - one resource: 150; all resources: 900
  - move village: 750
  - move building: 100, free for 1 hour after a village move
  - bonus building: 250
  - speed-ups for buildings, training and scouting
- Dolumar credits are their own credit type in Accounts:
  - their own balance
  - listed on the Accounts credits overview page next to QuizWitz credits
  - buyable there and through the top-up on the confirm page
- Price (owner decision): **500 credits = €7.00 including VAT** for an EU buyer, the old service's bank-transfer price. Accounts stores prices without VAT and adds 21% VAT for EU buyers, so the stored base price is **€0.01157 per credit** (7.00 / 1.21 / 500). The smallest pack is **100 credits**, enough for 15 days of premium. Discount tiers mirror the old bonus credits as price factors:

  | Credits | Price factor |
  |---|---|
  | 100 | 1.00 |
  | 1000 | 0.95 |
  | 2000 | 0.91 |
  | 3000 | 0.86 |
  | 5000 | 0.83 |

  Accounts discounts are thresholds that lower the price per credit (`getCheapestAmountFor`), not bonus credits.
- Price and discount tiers are **stored per credit type in the database and editable in an Accounts admin screen** (owner decision). Today they are hard-coded in `Models/CreditType.php` for every type:
  - €0.50 excluding VAT
  - QuizWitz's tiers at 50, 100, 250, 500, 1000, 1500, 2000 and 3000 credits
  - non-EU buyers paying 21% more
  
  QuizWitz's type 1 must keep exactly those values after the change.
- The game grants a purchase only when all of these hold:
  - Accounts says the credit request is ACCEPTED
  - it was charged to that player's profile
  - it is for the amount the game itself calculates for that action
  - it has not been granted before
- Nobody pays without receiving: a paid purchase that can no longer be applied is refunded automatically (see Spend).
- Admin refunds keep working through Accounts:
  - The manual refund on the admin user page stays as it is.
  - The premium refund tool refunds only premium bought through Accounts, tied to its original credit request. Premium bought on the old service cannot be refunded, because those credits never existed in Accounts.

## Non-goals

- Carrying over balances from the old credits service. They lived in the dead service. Premium end dates are stored in Dolumar's own database (`n_players.premiumEndDate`), so active premium carries over unchanged.
- Changing what any purchase costs or does, or adding new purchases.
- Mandates or unattended charges. Every purchase is confirmed by the player on Accounts.
- The other things signed with the old key: newsletter, trackers, BrowserGamesHub notify/stats push, donations. They stay off.
- Licenses or seats-style products for Dolumar.

## Anti-goals (failure even if it "works")

- QuizWitz credits, seats, shop or AI mandates behave differently in any way, or QuizWitz needs any change.
- A player can get a purchase without paying for exactly that purchase. Examples:
  - replaying a notify call
  - reusing one paid request for a second action
  - editing the action or amount in a URL
  - paying for a cheap action and receiving an expensive one
- One product's OAuth client can read, refund or charge another product's credits.
- Spending debits QuizWitz credits.

## Constraints

- Two repos:
  - `~/Workbench/accounts.catlab.eu` (default branch `develop`; read its `AGENTS.md`, whose specs and plans live in `.ai/superpowers/`)
  - `~/Workbench/dolumar` plus `~/Workbench/dolumar-engine`
  
  Work goes on feature branches with PRs. Accounts payment code went through a security audit (2026-08-27, `.ai/superpowers/specs/2026-08-27-security-audit-findings.md`). The changes must keep its findings fixed, notably A1: products must verify a request before granting.
- Dolumar runs PHP 8.4 on Dokku (game.dolumar.com). Login is OIDC against Accounts (`Neuron_Auth_OpenIDConnect`, scopes `openid email profile`). The access token is currently discarded after login.
- No credits sandbox exists. Testing uses a local Accounts instance or credits granted by an admin.

## How it works

### Accounts: credit type chosen by product

Today every credits API endpoint uses credit type 1 (`QuizWitz Credits`), via `getFromId(1)` in `Controllers/API/Credits.php` at lines 87, 156, 283, 388, 466 and 672. The client-credentials endpoints also require the calling client's product to own type 1.

Change: the credit type for an API call is **the credit type owned by the calling OAuth client's product**.
- For user-token endpoints, this is the client the token was issued to. `BaseAPI::guessActiveProduct()` already resolves it. That covers:
  - `credits/request`
  - `credit-price`
  - `POST credits/mandates`
- For client-credentials endpoints, it is the authenticated client. That covers:
  - refund
  - charge
  - `GET credits/mandates`
  - `credits/requests/{id}`
- **Fallback (my call):** if the client has no product, or its product has no credit type, the call uses type 1 exactly as today. QuizWitz accepts Accounts tokens issued to several clients (web, mobile, mcp, quizfabriek), and not all of them are guaranteed to be linked to its product, so an error here could break QuizWitz.
- A client whose product owns a different type never touches type 1.

`credits/requests/{id}` keeps returning 404 for a request whose credit type belongs to another product.

Pricing moves from code into the database:
- `credits_type` gains the base price per credit excluding VAT (EUR), and a set of discount tiers, each a threshold and a price factor.
- A migration fills type 1 with today's hard-coded values: €0.50, the eight QuizWitz tiers, and the non-EU +21% rule unchanged.
- `CreditType` reads its price and tiers from these values.
- A small admin screen, next to the existing `Admin/Prices.php`, edits them per type.
- Tiers must stay strictly decreasing in price per credit.

Setup in Accounts, data only, done through the admin:
- a "Dolumar" product
- a "Dolumar Credits" credit type pointing at that product, with the price and tiers above
- Dolumar's existing OIDC client linked to that product, with the `credits` scope allowed

Code change: `game.dolumar.com` (or `dolumar.com` with subdomains) added to `validReturnUrls` in `config/app.php`. Without it, Accounts rejects Dolumar's `notify_url`.

The overview page (`/credits`) already lists all credit types with balances, and purchase pages already work per type. Dolumar credits appear there without further work. The builder should check that the overview and purchase pages show a sensible name and price for the second type.

### Dolumar: the new credits provider

The game already funnels everything through a few seams. Everything above them (the windows, `Player::*Credit*`) stays as it is, except where noted.
- `Neuron_GameServer_Credits` in the engine wraps the provider per player.
- All spends land in a single switch, `Dolumar_Players_Player::useCredit($amount, $data)`.
- One API route, `spendcredit` in `scripts/api/api.php`, handles callbacks.

1. **Login.** Request the `credits` scope as well. Keep the access token instead of discarding it: store it per player along with the player's personal Accounts profile id, fetched from `GET /api/1.0/profiles` (the profile with `personal: true`). Players who logged in before this change re-log in once to grant the new scope.
2. **Balance.** `GET /api/1.0/users/me/credits/credit-price` with the player's token returns `available`, which is the balance.
3. **Buy.** Link to the Dolumar credit type's purchase page on Accounts.
4. **Spend.** Windows no longer create a spend URL per button on every render, because each one would become a confirmable Accounts request. A button points at a Dolumar "start purchase" endpoint that receives the action and its parameters.
   1. The game recomputes the price server-side. Each window's price logic moves into one function of the action and its parameters.
   2. It creates a pending purchase record in Dolumar: player, action and parameters, credits, status, and an expiry of **one hour** (my call).
   3. It calls `POST /api/1.0/profiles/{profileId}/credits/request` with the player's token, sending:
      - `credits`
      - `reason`, the translated action description
      - `notify_url`, Dolumar's callback
      - `return`, back to the game
      - `extra`, holding only the Dolumar purchase id
   4. It opens the returned `confirmUrl` for the player, like the old popup or link. QuizWitz adds a short-lived login token (`?authcode=`) so the player doesn't have to sign in again; Dolumar does the same.
5. **Notify callback.** This replaces the old `spendcredit` handling.
   1. Accounts POSTs `credit_request_id`.
   2. Dolumar fetches `GET /api/1.0/credits/requests/{id}` with its client credentials. It requires:
      - status ACCEPTED
      - the charged profile is the player's profile
      - the credits equal the pending purchase's credits
      - the request id has not been used before
   3. Only then does Dolumar run the stored action through `useCredit`, mark the purchase done, and record the request id. The action comes from Dolumar's own record, never from the callback.
   4. **When the action can't be applied** (my call), Dolumar refunds the credits automatically through `credits/refund` with that `credit_request_id`, and marks the purchase refunded. Examples:
      - the target spot is taken
      - the building already finished
      - the monthly rune cap is reached
      - the village is gone
      - the purchase expired before the player confirmed it
      
      `useCredit` currently fails silently, so each action has to report whether it was applied.
   5. If the notify call ever fails, Accounts has already debited the credits. A small reconcile step re-checks pending purchases against Accounts and grants or refunds them. It runs from cron and when the player next opens the premium shop.
6. **Refund.** `POST /api/1.0/profiles/{profileId}/credits/refund` with client credentials.
   - The premium refund tool and automatic refunds always pass the purchase's `credit_request_id`, so Accounts caps them at what was paid.
   - The manual admin refund may go without one.
   - Players with no stored profile id are skipped and listed.
7. **Credit display.** Plain numbers. The old currency conversion goes away, using the passthrough that already exists.

While credits are not configured, purchase links stay hidden. Today the speed-up links in the status bar show and open an empty window, which is a bug.

## Left to the builder

- Where the token and profile id live: a new table or extra columns on `n_auth_openid`. Store the token encrypted or at least never log it.
- Schema of the pending purchase table, and the exact reconcile schedule.
- How Accounts finds the credit type for a product, for example a mapper method `getFromProduct`.
- The exact columns or table for the price and tiers, and the admin screen's layout.
- Error handling and messages when Accounts is unreachable.
- Tests:
  - Accounts: integration tests showing QuizWitz's type-1 behaviour unchanged, including that its prices are identical after the migration, and a second product isolated from it.
  - Dolumar: unit tests for the callback verification, covering wrong status, wrong profile, wrong amount, replay and unknown purchase.

## Rollout

1. Accounts change, reviewed and deployed. QuizWitz must keep working.
2. Accounts admin setup: product, credit type and price, client link.
3. Dolumar deploy. Players re-log in for the `credits` scope.
4. A test purchase of each kind with admin-granted credits, then one real top-up.
