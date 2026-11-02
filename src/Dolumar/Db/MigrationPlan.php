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
 * Decides what tools/migrate.php runs from dump/migrations/. Pure: directory listing and the names already in
 * the migrations table go in, the file names to execute come out, in name order.
 *
 * Only `upgrade-YYYYMMDD[suffix].sql` files are migrations. Anything else in the directory (README, scratch
 * files) is never executed against a database.
 */
class Dolumar_Db_MigrationPlan
{
	/**
	 * @param string $fileName
	 * @return bool
	 */
	public static function isMigrationFile ($fileName)
	{
		return preg_match ('/^upgrade-[0-9]{8}[^\/]*\.sql$/', $fileName) === 1;
	}

	/**
	 * @param string[] $files directory listing of dump/migrations/
	 * @return string[] the migration files, sorted by name
	 */
	public static function migrations (array $files)
	{
		$migrations = array_values (array_filter ($files, [self::class, 'isMigrationFile']));
		sort ($migrations);
		return $migrations;
	}

	/**
	 * @param string[] $files directory listing of dump/migrations/
	 * @param string[] $applied names already in the migrations table
	 * @return string[] files to execute, in execution order
	 */
	public static function pending (array $files, array $applied)
	{
		$pending = [];
		foreach (self::migrations ($files) as $fileName) {
			if (!in_array ($fileName, $applied, true)) {
				$pending[] = $fileName;
			}
		}
		return $pending;
	}
}
