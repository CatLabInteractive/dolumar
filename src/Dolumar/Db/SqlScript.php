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
 * Splits a migration file into the statements to execute one by one.
 */
class Dolumar_Db_SqlScript
{
	/**
	 * Full-line `--` comments are dropped first, so a `;` inside a comment never splits a statement. The rest
	 * is split on every `;`: a migration file therefore cannot contain a `;` inside a string literal or a
	 * routine body. Empty chunks are skipped.
	 *
	 * @param string $sql
	 * @return string[]
	 */
	public static function splitStatements ($sql)
	{
		$lines = array_filter (preg_split ('/\R/', $sql), function ($line) {
			return !preg_match ('/^\s*--/', $line);
		});

		$statements = [];
		foreach (explode (';', implode ("\n", $lines)) as $statement) {
			$statement = trim ($statement);
			if ($statement !== '') {
				$statements[] = $statement;
			}
		}

		return $statements;
	}
}
