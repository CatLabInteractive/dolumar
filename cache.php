<?php
include ('php/config.php');
$dir = isset ($_GET['d']) ? $_GET['d'] : null;
if (file_exists (CACHE_DIR.$dir))
{
	echo file_get_contents (CACHE_DIR.$dir);
}
?>