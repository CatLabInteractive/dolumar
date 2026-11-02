<?php
/**
 * Credit prices for premium actions. Used by the windows (to show prices)
 * and by Dolumar_Players_Player::quoteCreditAction (the authoritative price).
 */
class Dolumar_Premium_Prices
{
	/**
	 * Credits are on only when the Accounts credit service is configured.
	 * While off, every purchase link is hidden.
	 */
	public static function creditsEnabled ()
	{
		return Neuron_GameServer_Credits::getPureCreditsObject () !== null;
	}

	const RUNE = 75;
	const MOVE_VILLAGE = 750;
	const MOVE_BUILDING = 100;
	const RESOURCES_ONE = 150;
	const RESOURCES_ALL = 900;
	const BONUS_BUILDING = 250;

	const FREE_MOVEBUILDING_DURATION = 3600;
	const MAX_BUY_RUNES = 10;

	// Kept from the shop window: despite the name this is applied as hours (60*60*N).
	const MIN_MOVEVILLAGE_INTERVAL_DAYS = 7;

	/**
	 * Price and unit (seconds per unit) of a speed-up type, or null.
	 * @return array|null ['price' => int, 'unit' => int]
	 */
	public static function getSpeedup ($type)
	{
		switch ($type)
		{
			case 'building':
				return array ('price' => (int) PREMIUM_SPEEDUP_BUILDINGS_PRICE, 'unit' => (int) PREMIUM_SPEEDUP_BUILDINGS_UNIT);
			case 'training':
				return array ('price' => (int) PREMIUM_SPEEDUP_TRAINING_PRICE, 'unit' => (int) PREMIUM_SPEEDUP_TRAINING_UNIT);
			case 'scouting':
				return array ('price' => (int) PREMIUM_SPEEDUP_SCOUTING_PRICE, 'unit' => (int) PREMIUM_SPEEDUP_SCOUTING_UNIT);
		}
		return null;
	}

	public static function getSpeedupCredits ($type, $units)
	{
		$s = self::getSpeedup ($type);
		return $s ? $s['price'] * (int) $units : null;
	}
}
