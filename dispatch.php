<?php

if (substr ($_GET['module'], 0, 1) == "/")
{
	$_GET['module'] = substr ($_GET['module'], 1);
}

include ('index.php');