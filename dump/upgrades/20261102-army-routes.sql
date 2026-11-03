-- Moving armies on the map: the A* route (JSON list of [x, y] waypoints) of an attack and of a squad move,
-- stored at dispatch time so the map can draw the army along it. Rows without a route are drawn as a straight line.
-- Runs on every deploy (tools/setup.php), so each ALTER only runs when the column is still missing
-- (MySQL 8 has no ADD COLUMN IF NOT EXISTS).

SET @dolumar_sql = (SELECT IF (COUNT(*) = 0, 'ALTER TABLE `battle` ADD COLUMN `route` text DEFAULT NULL', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'battle' AND COLUMN_NAME = 'route');
PREPARE dolumar_stmt FROM @dolumar_sql;
EXECUTE dolumar_stmt;
DEALLOCATE PREPARE dolumar_stmt;

SET @dolumar_sql = (SELECT IF (COUNT(*) = 0, 'ALTER TABLE `squad_commands` ADD COLUMN `sc_route` text DEFAULT NULL', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'squad_commands' AND COLUMN_NAME = 'sc_route');
PREPARE dolumar_stmt FROM @dolumar_sql;
EXECUTE dolumar_stmt;
DEALLOCATE PREPARE dolumar_stmt;
