<script type="text/javascript" src="<?=STATIC_ABSOLUTE_URL?>dolumar/javascript/dolumar.js?version=<?=$application_version?>"></script>
<?php
	// Kind texts for the engine map-objects layer (javascript/mapobjects.js reads CONFIG_MAPOBJECT_TEXT)
	$armyText = Neuron_Core_Text::__getInstance ();
	$armyTexts = array ();
	foreach (array ('attack', 'waiting', 'returning', 'squad', 'neutral', 'open', 'close') as $armyKind)
	{
		$armyTexts[$armyKind] = $armyText->get ($armyKind, 'armies', 'map');
	}
?>
<script type="text/javascript">var CONFIG_MAPOBJECT_TEXT = <?=json_encode ($armyTexts)?>;</script>

<!-- dolumar css -->
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/fancybox.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/guide.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/icons.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/windows.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/menu.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/main.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />

<!-- dolumar theme -->
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/theme/dolumar.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />

<!-- mobile layout (rules apply under body.layout-mobile only) -->
<link href="<?=STATIC_ABSOLUTE_URL?>dolumar/css/mobile.css?version=<?=$application_version?>" rel="stylesheet" type="text/css" />

<!-- web app manifest: "Add to Home Screen", needed for browser notifications on iOS -->
<link rel="manifest" href="<?=ABSOLUTE_URL?>manifest.webmanifest" />
<link rel="apple-touch-icon" href="<?=ABSOLUTE_URL?>icon-192.png" />
<meta name="theme-color" content="#3b2a1a" />
<meta name="apple-mobile-web-app-capable" content="yes" />
