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

/*
	Suggests a regiment name such as "1st Archers" or "2nd Archers".

	The number is the lowest one whose name isn't taken yet in the village.
	The result always passes Neuron_Core_Tools::checkInput ($name, 'unitname'):
	3 to 20 characters, only a-z, A-Z, 0-9 and spaces. Accented letters are
	transliterated and other characters dropped, and the unit name is shortened
	when the whole name would be too long.
*/
class Dolumar_Squads_NameSuggester
{
	const MAX_LENGTH = 20;
	const MIN_LENGTH = 3;

	private static $transliteration = array
	(
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ą' => 'a',
		'ç' => 'c', 'ć' => 'c', 'č' => 'c',
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ę' => 'e',
		'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
		'ł' => 'l', 'ñ' => 'n', 'ń' => 'n',
		'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
		'ś' => 's', 'š' => 's', 'ß' => 'ss',
		'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
		'ý' => 'y', 'ÿ' => 'y', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z',
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Ą' => 'A',
		'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C',
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ę' => 'E',
		'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'Ł' => 'L', 'Ñ' => 'N', 'Ń' => 'N',
		'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
		'Ś' => 'S', 'Š' => 'S',
		'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
		'Ý' => 'Y', 'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z',
		'-' => ' '
	);

	private $pattern;
	private $language;
	private $fallback;

	/*
		$pattern: language text with @@number, @@ordinal and @@unit, e.g. "@@ordinal @@unit".
		$language: language code, picks the ordinal suffixes (en, nl, fr; others use the bare number).
		$fallback: used when the unit name is empty after cleaning, e.g. "Regiment".
	*/
	public function __construct ($pattern = '@@ordinal @@unit', $language = 'en', $fallback = 'Regiment')
	{
		$this->pattern = $pattern;
		$this->language = $language;
		$this->fallback = self::clean ($fallback);

		if (strlen ($this->fallback) < self::MIN_LENGTH)
		{
			$this->fallback = 'Regiment';
		}
	}

	/*
		$unitName: the plural unit name in the player's language.
		$takenNames: the names of the regiments the village already has.
	*/
	public function suggest ($unitName, array $takenNames)
	{
		$taken = array ();
		foreach ($takenNames as $name)
		{
			$taken[strtolower (trim ($name))] = true;
		}

		$unit = self::clean ($unitName);
		if (strlen ($unit) < self::MIN_LENGTH)
		{
			$unit = $this->fallback;
		}

		for ($number = 1; $number < 1000; $number ++)
		{
			$name = $this->format ($number, $unit);
			if (!isset ($taken[strtolower ($name)]))
			{
				return $name;
			}
		}

		return $this->format ($number, $unit);
	}

	public function format ($number, $unit)
	{
		$build = function ($unitPart) use ($number)
		{
			$name = str_replace
			(
				array ('@@ordinal', '@@number', '@@unit'),
				array ($this->ordinal ($number), (string)$number, $unitPart),
				$this->pattern
			);

			return trim (preg_replace ('/\s+/', ' ', self::clean ($name)));
		};

		$name = $build ($unit);
		if (strlen ($name) <= self::MAX_LENGTH)
		{
			return $name;
		}

		// Shorten the unit name, at a word boundary when one is close enough.
		$room = self::MAX_LENGTH - (strlen ($name) - strlen ($unit));
		$short = rtrim (substr ($unit, 0, max (1, $room)));
		$space = strrpos ($short, ' ');
		if ($space !== false && $space >= $room - 6)
		{
			$short = substr ($short, 0, $space);
		}

		return substr ($build ($short), 0, self::MAX_LENGTH);
	}

	public function ordinal ($number)
	{
		switch ($this->language)
		{
			case 'en':
				$lastTwo = $number % 100;
				if ($lastTwo >= 11 && $lastTwo <= 13)
				{
					return $number . 'th';
				}

				switch ($number % 10)
				{
					case 1: return $number . 'st';
					case 2: return $number . 'nd';
					case 3: return $number . 'rd';
					default: return $number . 'th';
				}

			case 'nl':
				return $number . 'e';

			case 'fr':
				return $number == 1 ? '1er' : $number . 'e';

			default:
				return (string)$number;
		}
	}

	public static function clean ($text)
	{
		$text = strtr ((string)$text, self::$transliteration);
		$text = preg_replace ('/[^a-zA-Z0-9 ]/', '', $text);
		return trim (preg_replace ('/\s+/', ' ', $text));
	}
}
