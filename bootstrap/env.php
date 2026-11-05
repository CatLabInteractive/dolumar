<?php

/**
 * Read a positive number from the environment (game speeds, round length). A missing, empty, non-numeric, zero or
 * negative value gives the default, so a typo in the Dokku config never makes a world run at speed 0.
 *
 * Whole numbers come back as int and others as float ("3" gives 3, "1.5" gives 1.5).
 */
if (!function_exists ('dolumar_env_number')) {
	function dolumar_env_number ($name, $default)
	{
		$value = getenv ($name);
		if ($value === false) {
			return $default;
		}

		$value = trim ($value);
		if ($value === '' || !is_numeric ($value) || $value <= 0) {
			return $default;
		}

		return $value + 0;
	}
}
