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

/**
 * The `migrations` table: which files of dump/migrations/ have been applied to this database.
 * Shared by tools/migrate.php (runs pending files) and tools/setup.php (marks them all applied after installing
 * dump/dump.sql, which already contains every migration).
 */
class Dolumar_Db_Migrations
{
	const TABLE = 'migrations';

	/**
	 * @return string the DDL, also present in dump/dump.sql
	 */
	public static function createStatement ()
	{
		return "CREATE TABLE IF NOT EXISTS `" . self::TABLE . "` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
	}

	/**
	 * @param Neuron_DB_Database $db
	 */
	public static function ensureTable ($db)
	{
		$db->query (self::createStatement ());
	}

	/**
	 * @param Neuron_DB_Database $db
	 * @return string[] file names already applied
	 */
	public static function applied ($db)
	{
		$applied = [];
		foreach ($db->query ("SELECT migration FROM `" . self::TABLE . "`") as $row) {
			$applied[] = $row['migration'];
		}
		return $applied;
	}

	/**
	 * @param Neuron_DB_Database $db
	 * @param string $fileName
	 */
	public static function record ($db, $fileName)
	{
		$db->query ("
			INSERT IGNORE INTO `" . self::TABLE . "` (migration, created_at)
			VALUES ('" . $db->escape ($fileName) . "', NOW())
		");
	}
}
