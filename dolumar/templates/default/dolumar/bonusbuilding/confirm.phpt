<?php $this->setTextSection ('bonusbuilding', 'premium'); ?>

<p class="true">
	<?=$this->putIntoText
	(
		$this->getText ('confirm'),
		array
		(
			'building' => $building,
			'location' => '('.$x.','.$y.')'
		)
	);
	?>
</p>

<p>
	<a href="<?=htmlentities ($url)?>" target="_BLANK" onclick="windowAction(this,{'action':'overview'}); return !Game.gui.openWindow(this, 450, 190);"><?=$this->getText ('dyes'); ?></a>
	<a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText ('dno'); ?></a>
</p>
