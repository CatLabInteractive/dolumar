<?php ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html>
	<head>
		<title>Admin panel</title>
		
		<link href="<?=STATIC_ABSOLUTE_URL?>gameserver/css/<?=$stylesheet?>.css" rel="stylesheet" type="text/css" >
		
		<script type="text/javascript">
			var Game = new Object ();
			var CONFIG_GAME_URL = '<?=ABSOLUTE_URL?>';
			var CONFIG_STATIC_GAME_URL = '<?=STATIC_ABSOLUTE_URL?>';<?php
			echo "\n";
			foreach ($_GET as $k => $v)
			{
				echo "\t\t\tvar PARAM_".strtoupper ($k)." = '".$v."';\n";
			}
			?>
			var CONFIG_IS_TESTSERVER = <?= IS_TESTSERVER ? 'true' : 'false'?>;
			var CONFIG_DATETIME_FORMAT = '<?=DATETIME?>';
			var RUNTIME_SESSION_ID = '<?=session_id()?>';
			<?php if (defined ('GOOGLE_ANALYTICS')) { ?>
			
			var GOOGLE_ANALYTICS = '<?=GOOGLE_ANALYTICS?>';
			<?php } ?>
		</script> 
		
		<script type="text/javascript" src="<?=$static_client_url?>javascript/prototype/prototype.js"></script>
		<script type="text/javascript" src="<?=$static_client_url?>javascript/scriptaculous/scriptaculous.js"></script>
		<script type="text/javascript" src="<?=$static_client_url?>javascript/overlib/overlib.js"></script>
		
		<?php if (isset ($list_javascripts)) { ?>
			<?php foreach ($list_javascripts as $v) { ?>
				<script type="text/javascript" src="<?=$static_client_url?>javascript/<?=$v?>.js"></script>
			<?php } ?>
		<?php } ?>
	</head>
	
	<body>
		<?=$body?>
	</body>
</html>
