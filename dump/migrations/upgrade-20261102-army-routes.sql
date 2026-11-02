-- Moving armies on the map: store the A* route (JSON list of [x, y] waypoints) of an attack
-- and of a squad move at dispatch time, so the map can draw the army along it.
-- Rows without a route are drawn as a straight line.
-- (No semicolons in comments: tools/migrate.php splits on them.)

ALTER TABLE `battle` ADD COLUMN `route` text DEFAULT NULL;

ALTER TABLE `squad_commands` ADD COLUMN `sc_route` text DEFAULT NULL;
