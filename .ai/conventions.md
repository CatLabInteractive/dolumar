# Conventions

This is a large legacy codebase (roughly 270 game files and 250 engine files) written before modern PHP practice.
Match the surrounding code rather than modernising it in passing. Small, targeted changes are much easier to review
and much safer for live data.

## Code style

- Tabs for indentation, braces on their own line, a space before the parentheses of a call: `foo ($bar)`.
- Class names are PEAR-style with underscores and must match the path under `src/`:
  `Dolumar_Buildings_Farm` is `src/Dolumar/Buildings/Farm.php`. No namespaces.
- New files start with the GPL-3.0 license header used by the existing files.
- Many old files end with `?>`. Do not add one to new files, and do not leave trailing whitespace after an existing
  one (it would be sent as output).
- Singletons and factories are everywhere: `Neuron_Core_Text::__getInstance()`, `Neuron_GameServer::getPlayer()`,
  `Dolumar_Players_Village::getVillage($id)`, `Neuron_DB_Database::getInstance()`. Use them rather than `new`.

## Database access

- Get the connection with `Neuron_DB_Database::getInstance()` (preferred) or the older
  `Neuron_Core_Database::__getInstance()`. Both wrap mysqli.
- There are no prepared statements. Every value that comes from a request or a player must be escaped with
  `$db->escape()` (or cast with `intval()` / `(int)`) before it goes into SQL. String-concatenated SQL from input is
  the most common source of security bugs in this codebase.
- Query errors throw a plain `Exception` whose message starts with `MySQL Error:` (PHP 7 mysqli semantics are kept on
  purpose, see `mysqli_report(MYSQLI_REPORT_OFF)` in the bootstrap).
- Times are stored as MySQL `DATETIME`; convert with `$db->fromUnixtime()` / `$db->toUnixtime()`. Use the `NOW`
  constant for the request time so all calculations in one request agree.
- Text is utf8mb4. Do not change charsets or collations of existing tables.

## Output and security

- Escape player-provided text in HTML with `Neuron_Core_Tools::output_varchar()` (or `htmlspecialchars`).
- Check ownership before acting on a village or object: compare `$me->getId()` with the owner, and allow
  `$me->isModerator()` where the existing code does.
- `Neuron_Core_Login::__getInstance()->isLogin()` tells you whether a visitor is logged in.

## Templates

- Templates are plain PHP files in `resources/templates/default/` (`.tpl` and `.phpt`). The engine has its own in
  `templates/` of the engine repo.
- From PHP: `$page = new Neuron_Core_Template(); $page->set('name', $value); $page->addListValue('list', $row);
  return $page->parse('file.tpl');`. Inside the template, values are local variables (`$name`) and lists are
  `$list_<name>`.
- `$this->setTextSection('section', 'file')` and `$this->getText('key')` read translations inside a template.

## Translations

- Strings live in `resources/language/<lang>/<file>.lng`, INI style with `[section]` headers. English (`en`) is the
  base and fallback; `fr` and `de` exist too.
- Read them with `Neuron_Core_Text::__getInstance()->get('key', 'section', 'file')`. Placeholders are written
  `@@name` and filled with `Neuron_Core_Tools::putIntoText($text, ['name' => $value])`.
- When adding a user-visible string, add it to `en` at least. Do not hard-code English in new code.

## Game constants

Game speed and rules come from constants defined in `bootstrap/bootstrap.php` (`SPEED_FACTOR`,
`GAME_SPEED_*`, `MAXBUILDINGRADIUS`, `PREMIUM_*`, ...). Unit and building stats come from `resources/stats/`.
Changing any of these changes the game for the live player: only do it when asked.
