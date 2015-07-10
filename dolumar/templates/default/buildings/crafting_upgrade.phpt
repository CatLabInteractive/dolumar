<?php $this->setTextSection ('crafting', 'buildings'); ?>

<?php if (isset ($error)) { ?>
	<p class="false"><?=$this->getText ($error); ?></p>
<?php } ?>

<?php foreach (array ('current-level' => $current_level, 'next-level' => $next_level) as $k => $v) { ?>

	<h3><?=$this->putIntoText ($this->getText ($k), array ('name' => $v['name'])); ?></h3>
	<div class="fancybox">
		<div class="weapon-stats">
			<?=$v['stats']?>
		</div>	
	</div>
<?php } ?>

<h3><?=$this->getText ('confirmupgrade'); ?></h3>

<p><?=$this->getText ('upgradeinfo'); ?></p>

<p class="maybe"><?=$this->getText ('upgradecost'); ?> <?=$upgradecost?></p>

<ul class="actions">
	<li class="yes"><?php
		echo Neuron_URLBuilder::getInstance ()->getUpdateUrl 
		(
			'building', 
			$this->putIntoText ($this->getText ('doupgrade'), array ('equipment' => $next_level['name'])),
			array 
			(
				'bid' => $buildingid, 
				'action' => 'do-upgrade', 
				'crafting' => $id
			)
		)
	?></li>

	<li class="no"><?php
		echo Neuron_URLBuilder::getInstance ()->getUpdateUrl 
		(
			'building', 
			$this->getText ('dontupgrade'),
			array 
			(
				'bid' => $buildingid
			)
		)
	?></li>
</ul>
