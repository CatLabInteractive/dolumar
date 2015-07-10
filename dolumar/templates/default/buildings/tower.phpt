<?php $this->setTextSection ('tower', 'buildings'); ?>

<p class="maybe"><?=$description?></p>

<p>
	<?php echo Neuron_Core_Tools::putIntoText 
	(
		$this->getText ('towerbonus'), 
		array 
		(
			'runes' => '<strong>'.$runes.'</strong>',
			'percentage' => '<strong>'.$percentage.'</strong>',
			'bonus' => '<strong>'.$bonus.'</strong>'
		)
	); ?>
</p>

<p class="true">
	<?php echo Neuron_Core_Tools::putIntoText 
	(
		$this->getText ('defensebonus'), 
		array 
		(
			'runes' => '<strong>'.$runes.'</strong>',
			'percentage' => '<strong>'.$percentage.'</strong>',
			'bonus' => '<strong>'.$bonus.'</strong>'
		)
	); ?>
</p>
