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
 * One decision of a planner: a type and its parameters. The executor turns it into game API calls.
 */
class Dolumar_Npc_Action
{
	const SCOUT = 'scout';
	const BUILD = 'build';
	const TRAIN = 'train';
	const REQUEST = 'request';
	const SCOUT_RAID = 'scout_raid';
	const ENVOY = 'envoy';
	const WAR_PARTY = 'war_party';
	const SUPPORT = 'support';
	const RECALL_SUPPORT = 'recall_support';
	const ALLIED_STRIKE = 'allied_strike';
	const THANK_YOU = 'thank_you';

	/** @var string */
	public $type;

	/** @var array */
	public $params;

	/**
	 * @param string $type
	 * @param array $params
	 */
	public function __construct ($type, array $params = [])
	{
		$this->type = $type;
		$this->params = $params;
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get ($key, $default = null)
	{
		return array_key_exists ($key, $this->params) ? $this->params[$key] : $default;
	}

	/**
	 * @return string e.g. "war_party {plid: 3, target: 12, fraction: 0.3}"
	 */
	public function __toString ()
	{
		$parts = [];
		foreach ($this->params as $key => $value) {
			$parts[] = $key . ': ' . (is_array ($value) ? json_encode ($value) : $value);
		}
		return $this->type . ' {' . implode (', ', $parts) . '}';
	}
}
