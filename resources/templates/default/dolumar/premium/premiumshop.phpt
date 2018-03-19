<?php $this->setTextSection ('shop', 'premium'); ?>

<div class="giantcrown">
	<!--<h2><?=$this->getText ('shop'); ?></h2>-->
	<!--<p><?=$this->getText ('about')?></p>-->

	<h3><?=$this->getText ('premium');?></h3>
	<?php if (isset ($premium) && $premium >= 0) { ?>
		<p class="credits premium">
			<span class="premium-icon">
				You have <?=$premium?> credits.<br />
				<a href="<?=$buy_url?>" target="_BLANK">Buy more credits</a>
			</span>
		</p>
	<?php } ?>

	<div class="fancybox">
		<p><?=$this->putIntoText ($this->getText ('aboutpremium'), array 
			(
				'here' => '<a href="javascript:void(0);" onclick="openWindow(\'premium\');">'.$this->getText ('here').'</a>'
			)
		)?></p>
	</div>
	
	<h3><?=$this->getText ('bonusses'); ?></h3>

	<p><?=$this->getText ('aboutbonus'); ?></p>
	
	<?php if (isset ($bonusbuilding) && $bonusbuilding) { ?>
		<div class="fancybox halfsize">
	
			<h4><?=$this->getText ('bonusbuilding'); ?></h4>
			<p>
				<?=$this->getText ('aboutbonusbuilding'); ?>
				<a href="javascript:void(0);" onclick="openWindow('Bonusbuildings',{});"><?=$this->getText ('lnkbonusbuilding'); ?></a>
			</p>
		
		</div>
	<?php } ?>
	
	<div class="fancybox halfsize">
		<p class="credits right">
			<?=$this->putIntoText ($this->getText ('credits'), array ('credits' => $cost_movebuilding))?>
		</p>

		<h4><?=$this->getText ('movebuilding'); ?></h4>
		<p>
			<?=$this->getText ('aboutmovebuilding'); ?>
			<a href="javascript:void(0);" onclick="windowAction(this,{'action':'movebuilding'});"><?=$this->getText ('lnkmovebuilding'); ?></a>
		</p>
	</div>


	<!--
	<div class="fancybox halfsize">
		<p class="credits right">
			<?=$this->putIntoText ($this->getText ('credits'), array ('credits' => $cost_resources))?>
		</p>

		<h4><?=$this->getText ('buyresources'); ?></h4>
		<p>
			<?=$this->getText ('aboutbuyresources'); ?>
			<a href="javascript:void(0);" onclick="windowAction(this,{'action':'buyresources'});"><?=$this->getText ('lnkbuyresources'); ?></a>
		</p>
	</div>
	!-->

	<div class="fancybox halfsize">
		<p class="credits right">
			<?=$this->putIntoText ($this->getText ('credits'), array ('credits' => $cost_movevillage))?>
		</p>

		<h4><?=$this->getText ('movevillage'); ?></h4>
		<p>
			<?=$this->getText ('aboutmovevillage'); ?>
			<a href="javascript:void(0);" onclick="windowAction(this,{'action':'movevillage'});"><?=$this->getText ('lnkmovevillage'); ?></a>
		</p>
	</div>
	

	<div class="fancybox halfsize">
		<p class="credits right">
			<?=$this->putIntoText ($this->getText ('credits'), array ('credits' => $cost_runes))?>
		</p>

		<h4><?=$this->getText ('buyrunes'); ?></h4>
		<p>
			<?=Neuron_Core_Tools::putIntoText
			(
				$this->getText ('aboutbuyrunes'),
				array
				(
					'left' => $buyable,
					'total' => $maximum
				)
			);?>
		
			<a href="javascript:void(0);" onclick="windowAction(this,{'action':'buyrunes'});"><?=$this->getText ('lnkbuyrunes'); ?></a>
		</p>
	</div>

	<div class="fancybox halfsize">
		<p class="credits right">
			<?=$this->putIntoText ($this->getText ('credits'), array ('credits' => $cost_resources))?>
		</p>

		<h4><?=$this->getText ('buyresources'); ?></h4>
		<p>
			<?=Neuron_Core_Tools::putIntoText
			(
				$this->getText ('aboutbuyresources')
			);?>
		
			<a href="javascript:void(0);" onclick="windowAction(this,{'action':'buyresources'});"><?=$this->getText ('lnkbuyresources'); ?></a>
		</p>
	</div>
	
	<div class="clearer"></div>
</div>
