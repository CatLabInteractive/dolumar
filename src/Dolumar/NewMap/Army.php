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
*	A marching army (attack, return trip, squad move or thief) as a map object.
*	Built by Dolumar_NewMap_ArmyFactory; the movements describe where it is
*	at any moment. Only related viewers (the villages involved, or their
*	clan mates) get the details, see isRelatedTo.
*/
class Dolumar_NewMap_Army extends Neuron_GameServer_Map_MapObject
{
	private $uoid;
	private $kind;
	private $relatedVillageIds;
	private $name;
	private $spriteUrl;
	private $secret = false;

	/**
	*	@param string $uoid e.g. "b:12" (battle), "s:7" (squad command) or "t:3" (thief)
	*	@param string $kind attack | waiting | returning | squad | thief
	*	@param int[] $relatedVillageIds villages whose owners (and clan mates) may see details
	*	@param string|null $name
	*	@param string|null $click javascript to run on tap
	*	@param string $spriteUrl
	*/
	public function __construct ($uoid, $kind, array $relatedVillageIds, $name, $click, $spriteUrl)
	{
		$this->uoid = $uoid;
		$this->kind = $kind;
		$this->relatedVillageIds = array_map ('intval', $relatedVillageIds);
		$this->name = $name;
		$this->spriteUrl = $spriteUrl;

		if ($click !== null)
		{
			$this->observe ('click', $click);
		}
	}

	public function getUOID ()
	{
		return $this->uoid;
	}

	public function getId ()
	{
		return $this->uoid;
	}

	public function getKind ()
	{
		return $this->kind;
	}

	public function getName ()
	{
		return $this->name;
	}

	public function getDisplayObject ()
	{
		return new Neuron_GameServer_Map_Display_Sprite ($this->spriteUrl);
	}

	/**
	*	Unrelated viewers get a sprite that does not name the kind.
	*/
	public function getNeutralDisplayObject ()
	{
		return new Neuron_GameServer_Map_Display_Sprite (Dolumar_NewMap_ArmyFactory::spriteUrl ('neutral'));
	}

	/**
	*	A secret object (a thief) is never sent to unrelated viewers, not even
	*	as a neutral sprite.
	*/
	public function setSecret ($secret)
	{
		$this->secret = (bool) $secret;
	}

	public function isSecret ()
	{
		return $this->secret;
	}

	public function getRelatedVillageIds ()
	{
		return $this->relatedVillageIds;
	}

	/**
	*	$viewer is either an array of village ids (tests, cached lookups)
	*	or a player object, whose own and clan villages are resolved
	*	by Dolumar_NewMap_MapObjectManager::relatedVillageIds().
	*/
	public function isRelatedTo ($viewer)
	{
		if (is_object ($viewer))
		{
			$viewer = Dolumar_NewMap_MapObjectManager::relatedVillageIds ($viewer);
		}

		if (!is_array ($viewer))
		{
			return false;
		}

		foreach ($viewer as $vid)
		{
			if (in_array (intval ($vid), $this->relatedVillageIds, true))
			{
				return true;
			}
		}

		return false;
	}
}
