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

abstract class Dolumar_Effects_Effect implements Neuron_GameServer_Interfaces_Logable
{
	protected $sType = 'general';
	
	private $target = null;
	private $village = null;
	
	private $logdata = array ();
	
	private $error = null;
	private $castable = true;
	
	private $messageparameters = array ();
	
	const ERROR_NO_TARGET_FOUND = 'no_target_found';

	public static function getFromId ($id)
	{
		static $data;
		
		if (!isset ($data))
		{
			$db = Neuron_Core_Database::__getInstance ();
			
			$data = array ();
			
			$l = $db->select
			(
				'effects',
				array ('*')
			);
			
			foreach ($l as $v)
			{
				$data[$v['e_id']] = $v['e_name'];
			}
		}
		
		$input = explode (':', $id);
		
		$id = $input[0];
		
		$level = isset ($input[1]) ? $input[1] : null;
		
		$classname = isset ($data[$id]) ? $data[$id] : false;
		
		if ($classname && class_exists ($classname))
		{
			return new $data[$id] ($level);
		}
		return false;
	}
	
	public static function getEffects ()
	{
		$db = Neuron_DB_Database::getInstance ();
		
		$data = $db->query
		("
			SELECT
				*
			FROM
				effects
		");
		
		$out = array ();
		
		foreach ($data as $v)
		{
			$tmp = self::getFromId ($v['e_id']);
			if ($tmp)
			{
				$out[] = $tmp;
			}
		}
		
		return $out;
	}
	
	protected $iLevel;
	
	private $objActor;
	private $bSecret = true;
	
	/*
		Constructor
	*/
	public function __construct ($iLevel = 1)
	{
		if (!isset ($iLevel))
		{
			$iLevel = 1;
		}
		
		$this->iLevel = intval ($iLevel);
	}
	
	/*
		Set the actor of this spell.
	*/
	public function setActor ($objActor, $isSecret = true)
	{
		$this->objActor = $objActor;
		$this->bSecret = $isSecret;
	}
	
	public function getActor ()
	{
		return $this->objActor;
	}
	
	public function isSecret ()
	{
		return $this->bSecret;
	}
	
	public function setLevel ($level)
	{
		$this->iLevel = $level;
	}
	
	public function getLevel ()
	{
		return $this->iLevel;
	}

	/*
		Return the ID for this effect
	*/
	public function getId ()
	{
		$db = Neuron_Core_Database::__getInstance ();
		
		$chk = $db->select
		(
			'effects',
			array ('e_id'),
			"e_name = '".get_class ($this)."'"
		);
		
		if (count ($chk) == 1)
		{
			return $chk[0]['e_id'].':'.$this->iLevel;
		}
		else
		{
			return $db->insert
			(
				'effects',
				array
				(
					'e_name' => get_class ($this)
				)
			);
		}
	}

	public function getClassName ()
	{
		$name = get_class ($this);
		$name = explode ('_', $name);
		$name = $name[count ($name) - 1];

		return $name;
	}

	/* Language specific! */
	public function getName ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$out = $text->get ('title', $this->getClassName (), 'effects', $this->getClassName ());
		
		$level = $this->getLevel ();
		if ($level > 1)
		{
			$out .= ' ' . $level;
		}
		
		return $out;
	}
	
	public function getDisplayName ()
	{
		return '<span class="effect" title="'.$this->getDescription().'">'.$this->getName ().'</span>';
	}
	
	public function getDescription ($data = array ())
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		if (!isset ($data['level']))
		{
			$data['level'] = $this->getLevel ();
			$data['effect'] = $this->getName ();
		}
		
		return Neuron_Core_Tools::putIntoText
		(
			$text->get ('description', $this->getClassName (), 'effects', 'No description available.'),
			$data
		);
	}
	
	public function getExtraContent ()
	{
		return null;
	}
	
	public function getSuccessMessage ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$msg = $text->get ('onSuccess', $this->getClassName (), 'effects', 
			$text->get ('onSuccess', $this->sType, 'effects',
				$text->get ('onSuccess', 'general', 'effects')));
			
		return Neuron_Core_Tools::putIntoText ($msg, $this->getMessageParameters ());
	}
	
	public function getFailedMessage ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		$msg = $text->get ('onFailed', $this->getClassName (), 'effects', 
			$text->get ('onFailed', $this->sType, 'effects',
				$text->get ('onFailed', 'general', 'effects')));
		
		return Neuron_Core_Tools::putIntoText ($msg, $this->getMessageParameters ());
	}
	
	protected function setMessageParameters ($msg)
	{
		$this->messageparameters = $msg;
	}
	
	private function getMessageParameters ()
	{
		$target = $this->getTarget ();
		if (isset ($target))
		{
			$this->messageparameters['target'] = $this->getTarget ()->__toString ();
		}
		return $this->messageparameters;
	}
	
	public function getType ()
	{
		return 'instant';
	}
	
	public function getEffectType ()
	{
		return $this->sType;
	}
	
	public function getDifficulty ($iBaseAmount = 40)
	{
		return $iBaseAmount + ($this->getLevel () * 10);
	}
	
	public function getType_text ()
	{
		$text = Neuron_Core_Text::__getInstance ();
		
		$type = $this->getType ();
		switch ($type)
		{
			case 'battle':
			case 'instant':
				return $text->get ($type, 'types', 'effects');
			break;
			
			case 'boost':
				return Neuron_Core_Tools::putIntoText
				(
					$text->get ($type, 'types', 'effects'),
					array
					(
						'duration' => Neuron_Core_Tools::getDurationText ($this->getDuration (), true)
					)
				);
			break;
		}
	}
	
	protected function getCostFromLevel ()
	{
		switch ($this->getLevel ())
		{
			case 1:
				return 5.0;
			break;
			
			case 2:
				return 5.5;			
			break;
			
			case 3:
				return 6.0;
			break;
			
			case 4:
				return 6.5;
			break;
			
			case 5:
				return 7.0;
			break;
			
			default:
				return 5 + ($this->getLevel () * 0.5);
			break;
		}
	}
	
	/*
		Return (or increase) the cast cost.
	*/
	public function getCost ($objUnit, $objTarget, $cost = null)
	{
		if (!isset ($cost))
		{
			$cost = array
			(
				'gems' => $this->getCostFromLevel ()
			);
		}
		
		if (isset ($objTarget))
		{
			$runes = $objTarget->resources->getTotalRunes ();
		}
		
		else
		{
			$runes = $objUnit->getBuilding ()->getVillage ()->resources->getTotalRunes ();
		}
		
		foreach ($cost as $k => $v)
		{
			$cost[$k] = ceil ($v * $runes);
		}
		
		return $cost;
	}
	
	public function getProbability ($objUnit, $objTarget, $bIsBattle = false)
	{
		$objBuilding = $objUnit->getBuilding (); 
	
		$dif = $this->getDifficulty ();
		
		// If the target is not yourself, do a dif check
		if (isset ($objTarget) && $objBuilding->getVillage ()->getId () != $objTarget->getId ())
		{
			$dif = $this->manipulateDifficulty ($objUnit, $objTarget, $dif, $bIsBattle);
		}
		
		// Lower the dif by the casters level
		$lvldif = $objUnit->getLevel () - $this->getMinimalBuildingLevel ();
		if ($lvldif < 0)
		{
			$lvldif = 0;
		}
		$dif -= ($lvldif - 1) * 10;
		
		// Handle the effects
		foreach ($objBuilding->getVillage ()->getEffects () as $v)
		{
			$dif = $v->procEffectDifficulty ($dif, $this);
		}
		
		if (isset ($objTarget))
		{
			$dif = $this->manipulateDifficultyOnBuildingLevel ($objUnit, $objTarget, $dif, $bIsBattle);	
			$dif = $this->manipulateDifficultyOnPownness ($objUnit, $objTarget, $dif, $bIsBattle);
		}
		
		$prob = 100 - $dif;
		return max (10, min (90, round ($prob)));
	}
	
	private function manipulateDifficultyOnPownness ($objUnit, $objTarget, $dif, $bIsBattle)
	{
		$myunits = $this->getSpecialUnitPercentage ($objUnit, $objUnit->getBuilding ()->getVillage ());
		$hisunits = $this->getSpecialUnitPercentage ($objUnit, $objTarget);
		
		if ($myunits > $hisunits)
		{
			$dif -= 10;
		}
		
		elseif ($myunits < $hisunits)
		{
			$dif += 10;
		}
		
		return $dif;
	}
	
	/*
		Manipulate difficulty again against optimal building level
	*/
	private function manipulateDifficultyOnBuildingLevel ($objUnit, $objTarget, $iDiffifulty, $bIsBattle = false)
	{
		$objVillage = $objUnit->getBuilding ()->getVillage ();
		$myUnits = $this->countSpecialUnits ($objUnit, $objVillage);
		
		$percentage = $myUnits / $objVillage->resources->getTotalRunes ();
		
		$optimal = $this->getOptimalBuildingPercentage ();
		$optimal = $optimal / 100;
		
		$current = min (1, $percentage / $optimal);
		$current *= $current;
		
		return $iDiffifulty * $current;
	}
	
	/*
		Addapt difficulty to your enemies defenses
		Must return an int between 0 and 100.
	*/
	protected function manipulateDifficulty ($objUnit, $objTarget, $iDifficulty, $bIsBattle = false)
	{
		// Count the special units
		$objVillage = $objUnit->getBuilding ()->getVillage ();
		
		// If this cast is done in battle, only one unit is casting
		/*
		if ($bIsBattle)
		{
			$myUnits = $objUnit->getLevel () * 3;
		}
		
		// If this cast is done without battle, count the units in your village
		else
		{
		*/
			$myUnits = $this->countSpecialUnits ($objUnit, $objVillage);
		//}
		
		$hisUnits = $this->countSpecialUnits ($objUnit, $objTarget);
		
		/*
		$mypercentage = $myUnits;
		$hispercentage = $hisUnits;
		*/
		
		$mypercentage = ($myUnits / $objVillage->resources->getTotalRunes ());
		$hispercentage = ($hisUnits / $objTarget->resources->getTotalRunes ());
		
		if ($hisUnits > 0 && $myUnits > 0) // just to play safe ;-)
		{
			$iDifficulty *= max (1, $hispercentage / $mypercentage);
		}
		
		return $iDifficulty;
	}
	
	/*
		Returns the amount of units in the village.
		A level 2 units counts as 2 regular units.
	*/
	private function countSpecialUnits ($objUnit, $objVillage)
	{
		$unit = get_class ($objUnit);
		
		// Count the amount of units in this village (only available)
		$amount = 0;
		
		$buildings = array ();
		
		foreach ($objVillage->getSpecialUnits () as $v)
		{
			// If it's the same unit, increase
			if ($v instanceof $unit)
			{
				$bid = $v->getBuilding ()->getId ();
				if (!isset ($buildings[$bid]))
				{
					$amount += $v->getLevel ();
					$buildings[$bid] = true;
				}
			}
		}
		
		return $amount;
	}
	
	private function getSpecialUnitPercentage ($objUnit, $objVillage)
	{
		$hisUnits = $this->countSpecialUnits ($objUnit, $objVillage);
		return $hisUnits / $objVillage->resources->getTotalRunes ();
	}
	
	protected function getMinimalBuildingLevel ()
	{
		return 1;
	}
	
	/*
		Return TRUE if $building can learn this spell.
	*/
	public function canLearnSpell ($building)
	{
		$chk = true;
		
		$chk = $building->getLevel () >= $this->getMinimalBuildingLevel ();
	
		// If level is higher then 1, check 
		// if the previous version is available.
		$iLevel = $this->iLevel;
		
		if ($chk && $iLevel > 1)
		{
			$classname = get_class ($this);
			$chk = $building->doesKnowEffect (new $classname ($iLevel - 1));
		}
	
		return $chk;
	}
	
	/*
		Check if this classname is the same type.
	*/
	public function equals ($objEffect)
	{
		if (
			$this->getClassName () == $objEffect->getClassName ()
			&& $this->getLevel () == $objEffect->getLevel ()
		)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/*
		Output data used in templates!
	*/
	public function getOutputData ($objUnit, $objTarget)
	{
		return array
		(
			'title' => $this->getName (),
			'id' => $this->getId (),
			'description' => $this->getDescription (),
			'cost' => Neuron_Core_Tools::resourceToText ($this->getCost ($objUnit, $objTarget)),
			'type' => $this->getType_text (),
			'difficulty' => $this->getDifficulty ()
		);
	}
	
	/*
		For logging and stuff
	*/
	public function getLogArray ()
	{
		return array ();
	}
	
	public function execute ($a = null, $b = null, $c = null)
	{
	
	}
	
	/*
		Prepare is called right before execute.
	*/
	public function prepare ()
	{
	
	}
	
	public function setTarget ($target)
	{
		$this->target = $target;
	}
	
	public function getTarget ()
	{
		return $this->target;
	}
	
	public function setVillage ($village)
	{
		$this->village = $village;
	}
	
	public function getVillage ()
	{
		return $this->village;
	}

	public function requiresTarget ()
	{
		return false;
	}
	
	/*
		If this effect has any additional data
	*/
	protected function addLogData ($data)
	{
		if (! ($data instanceof Neuron_GameServer_Interfaces_Logable))
		{
			throw new Neuron_Core_Error ('Log data should implement Neuron_GameServer_Interfaces_Logable');
		}
		
		$this->logdata[] = $data;
	}
	
	public function getLogData ()
	{
		return $this->logdata;
	}
	
	/*
		Cancels the spell NOW.
	*/
	public function cancel ()
	{
	
	}
	
	public function getOptimalBuildingPercentage ()
	{
		return 10;
	}
	
	public function doesHandleOwnLogs ($success = true)
	{
		return false;
	}
	
	protected function setCastable ($isCastable = true)
	{
		$this->castable = $isCastable;
	}
	
	public function isCastable ()
	{
		return $this->castable;
	}
	
	protected function setError ($error)
	{
		$this->error = $error;
	}
	
	public function getError ()
	{
		return $this->error;
	}
	
	public function __toString ()
	{
		return $this->getDisplayName ();
	}
	
	public function __destruct ()
	{
		unset ($this->target);
		unset ($this->village);
	}
}
?>
