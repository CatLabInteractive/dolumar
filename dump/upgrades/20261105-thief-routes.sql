-- Thieves on the map: where a moving thief left from and its route around lakes (JSON legs, like battle.route),
-- stored when it is sent so the map can draw it. Thieves sent before this have no vsu_from and are not drawn.
-- Runs on every deploy (tools/setup.php), so each ALTER only runs when the column is still missing.

SET @dolumar_sql = (SELECT IF (COUNT(*) = 0, 'ALTER TABLE `villages_specialunits` ADD COLUMN `vsu_from` int(11) DEFAULT NULL', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'villages_specialunits' AND COLUMN_NAME = 'vsu_from');
PREPARE dolumar_stmt FROM @dolumar_sql;
EXECUTE dolumar_stmt;
DEALLOCATE PREPARE dolumar_stmt;

SET @dolumar_sql = (SELECT IF (COUNT(*) = 0, 'ALTER TABLE `villages_specialunits` ADD COLUMN `vsu_route` text DEFAULT NULL', 'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'villages_specialunits' AND COLUMN_NAME = 'vsu_route');
PREPARE dolumar_stmt FROM @dolumar_sql;
EXECUTE dolumar_stmt;
DEALLOCATE PREPARE dolumar_stmt;
