<?php $this->setTextSection ('market', 'buildings'); ?>

<?php if ($hasResources) { ?>
	<h3><?=$this->getText ('resources'); ?></h3>
	<p>
		<?php 
		echo Neuron_Core_Tools::putIntoText
		(
			$this->getText ('conresources'),
			array
			(
				'resources' => $resources,
				'target' => '<a href="javascript:void(0);" onclick="openWindow (\'villageProfile\', {\'village\':'.$targetid.'});">'.$target.'</a>'
			)
		);
		?>
	</p>
<?php } ?>

<?php if ($hasRunes) { ?>
	<h3><?=$this->getText ('runes'); ?></h3>
	<p>
		<?php 
		echo Neuron_Core_Tools::putIntoText
		(
			$this->getText ('conrunes' . ($runes > 1 ? '2' : '1')),
			array
			(
				'runes' => $runes,
				'target' => '<a href="javascript:void(0);" onclick="openWindow (\'villageProfile\', {\'village\':'.$targetid.'});">'.$target.'</a>'
			)
		);
		?>
	</p>
<?php } ?>

<?php if ($hasEquipment) { ?>
	<h3><?=$this->getText ('equipment'); ?></h3>
	<ul class="regular">
		<?php foreach ($list_equipment as $v) { ?>
			<li><?=$v['amount']?> <?=$v['name']?></li>
		<?php } ?>
	</ul>
<?php } ?>

<?php if (isset ($costs)) { ?>
	<p>
		<?php 
		echo Neuron_Core_Tools::putIntoText
		(
			$this->getText ('costs'),
			array
			(
				'cost' => $costs
			)
		);
		?>
	</p>
<?php } ?>

<?php if (isset ($premiumerror)) { ?>
	<p class="information">
		<span class="info-icon"></span>
		<?php 
		echo Neuron_Core_Tools::putIntoText
		(
			$this->getText ('premium'),
			array
			(
				'cost' => $premiumcost
			)
		);
		?>
	
		<?php 
		echo Neuron_Core_Tools::putIntoText
		(
			$this->getText ($premiumerror),
			array
			(
				'target' => $target
			)
		);
		?>
	</p>
<?php } ?>

<h3><?=$this->getText ('confirm'); ?></h3>

<?php $txt = $this->putIntoText ($this->getText ('transactions' . ($transactions == 1 ? '1' : '2')), array ('transactions' => $transactions, 'maximum' => $maxtransactions, 'duration' => $duration)); ?>
<?php if (!$canConfirm) { ?>
	<p class="false">
		<span class="false-icon">
			<?=$txt?>
		</span>
	</p>
<?php } else { ?>
	<p class="information">
		<span class="info-icon">
			<?=$txt?>
		</span>
	</p>
<?php } ?>

<ul class="actions">
	<?php if ($canConfirm) { ?>
	<li>
		<a href="javascript:void(0);" onclick='windowAction(this,<?=$input?>);'><?=$this->getText ('confirm')?></a>
	</li>
	<?php } ?>
	
	<li>
		<a href="javascript:void(0);" onclick="windowAction(this,{'action':'donate', 'target':<?=$targetid?>, 'tab':'<?=$tab?>'});"><?=$this->getText ('cancel')?></a>
	</li>
</ul>
