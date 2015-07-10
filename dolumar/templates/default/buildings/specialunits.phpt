<p><?=$this->getText ('intro');?></p>

<?php if (isset ($runetype)) { ?>
	<p><?=$this->getText ('runetype_'.$runetype)?></p>
<?php } ?>

<?php if (isset ($list_spells)) { ?>
	<h3><?=$this->getText ('learn');?></h3>
	<p><?=$this->getText ('level');?></p>
	
	<p><?=Neuron_Core_Tools::putIntoText ($this->getText ('freeSpellSlots'), array ('effects' => $freeSpellSlots)); ?></p>
	
	<?php foreach ($list_spells as $v) { ?>
		<div class="spell fancybox">
			<h3><?=$v['title']?></h3>
			<p class="castdetails"><?=$v['type']?>, <?=$this->getText ('dif', 'cast', $textfile)?> <?=$v['difficulty']?></p>
			<p><?=$v['description']?></p>
			<p>
				<span class="title"><?=$this->getText('castcost', 'cast', $textfile)?></span><br />
				<?=$v['cost']?>
			</p>
		
			<a href="javascript:void(0);" onclick="windowAction(this,{'action':'learn','effect':'<?=$v['id']?>'});"><?=Neuron_Core_Tools::putIntoText ($this->getText ('learn', 'cast', $textfile), array ('effect' => $v['title']))?></a><br />
		</div>
	<?php } ?>
<?php } ?>

<!-- TRAIN COUNTDOWN -->
<?php if (isset ($isTrained) || isset ($training_countdown) || $canTrain) { ?>
	<h3><?=$this->getText ('train');?></h3>
<?php } ?>

<?php if (isset ($isTrained)) { ?>
	<?php if ($isTrained) { ?>
		<p class="true"><?=$this->getText ('isTraining');?></p>
	<?php } else { ?>
		<p class="false"><?=$this->getText ($trainError)?></p>
	<?php } ?>
<?php } ?>

<?php if (isset ($training_countdown)) { ?>
	<p class="maybe">
		<strong><?=$this->getText ('tCountdown')?>:</strong><br />
		<?=$this->getText ('tRemaining')?> <?=$training_countdown?>
	</p>
<?php } ?>

<!-- TRAIN UNITS -->
<?php if ($canTrain) { ?>

	<p><?=Neuron_Core_Tools::putIntoText ($this->getText ('capacity'), array ('units' => $capacity));?></p>

	<?php $toTrain = $this->getClickTo ('toTrain'); ?>
	
	<p class="maybe">
		<strong><?=$this->getText ('trainingCost');?>:</strong><br />
		<?=$training_cost?>
	</p>
	
	<p><?=$toTrain[0]?><a href="javascript:void(0);" onclick="windowAction(this,{'action':'train'});"><?=$toTrain[1]?></a><?=$toTrain[2]?></p>
<?php } ?>

<!-- UNITS OVERVIEW + ACTION -->
<?php if ($inhabitans > 0) { ?>
	<h3><?=$unitsname?></h3>
	<?php $action = $this->getClickTo ('action'); ?>
	<p class="maybe">
		<?=Neuron_Core_Tools::putIntoText ($this->getText ('inhabitans'.($inhabitans > 1 ? '2' : '1')), array ('specialunit' => ($inhabitans > 1 ? $unitsname : $unitname), 'units' => $inhabitans, 'in' => $inhabitans_in))?><br />
		
		<?php if ($inhabitans_in > 0) { ?>
			<?=$action[0]?><a href="javascript:void(0);" onclick="openWindow('<?=$actionWindow?>',{'village':<?=$vid?>,'building':<?=$bid?>});"><?=$action[1]?></a><?=$action[2]?>
		<?php } ?>
	</p>
<?php } ?>
