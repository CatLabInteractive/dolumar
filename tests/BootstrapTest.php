<?php

use PHPUnit\Framework\TestCase;

class BootstrapTest extends TestCase
{
	public function testGameClassesAutoload (): void
	{
		$this->assertTrue (class_exists ('Dolumar_Players_Village_Ai'));
		$this->assertTrue (defined ('NOW'));
	}
}
