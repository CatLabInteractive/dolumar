<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html>
	<head>
		<title>Dolumar <?=$name?></title>
		<link href="<?=ABSOLUTE_URL?>dolumar/css/launch.css" rel="stylesheet" type="text/css" > 
		
		<script type="text/javascript" src="<?=ABSOLUTE_URL?>gameserver/javascript/prototype/prototype.js"></script>
		<script type="text/javascript" src="<?=ABSOLUTE_URL?>gameserver/javascript/scriptaculous/scriptaculous.js"></script>
	</head>
	
	<body>
		<div id="countdown">
			<h1 id="logo"><span>Dolumar</span></h1>
			<p><?= $this->getText ('finished', 'finished', 'finished'); ?></p>

			<?php if (isset ($winner)) { ?>
				<p><?php echo $this->putIntoText ($this->getText ('winner', 'finished', 'finished'), array ('clan' => Neuron_Core_Tools::output_varchar ($winner->getName ()))); ?></p>

				<ul>
					<?php foreach ($winner->getMembers () as $v) { ?>
						<li><?= Neuron_Core_Tools::output_varchar ($v->getName ()); ?></li>
					<?php } ?>
				</ul>

				<p><?= $this->getText ('thankyou', 'finished', 'finished'); ?><br />
				- <a href="http://www.catlab.eu/">CatLab Interactive</a> - </p>
			<?php } ?>
		</div>
	</body>
</html>
