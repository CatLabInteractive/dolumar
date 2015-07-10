<h2><?=$this->getText('title');?></h2>
<p>
	<?=$this->getText('about');?><br />
	<?=$this->getText('choose');?>
</p>

<?php if (isset ($list_spells)) { ?>
	<?php foreach ($list_spells as $v) { ?>		
		<div class="spell fancybox">
		
			<h3><?=$v['title']?></h3>
			<p class="castdetails"><?=$v['type']?>, <?=$this->getText ('dif')?> <?=$v['difficulty']?></p>
		
			<p><?=$v['description']?></p>
			
			<p>
				<span class="title"><?=$this->getText('castcost')?></span><br />
				<?=$v['cost']?>
			</p>
			
			<?php
				$toCast = $this->getText ('toCast');
				$toCast = Neuron_Core_Tools::putIntoText ($toCast, array ('spell' => $v['title']));
			?>
		
			<p>
				<a href="javascript:void(0);" onclick='windowAction(this,<?=json_encode (array_merge ($input, array ('spell' => $v['id']))); ?>);'><?=$toCast?></a>
			</p>
		</div>
	<?php } ?>
<?php } ?>

<?php if (isset ($returnData)) { ?>
	<p>
		<a href="javascript:void(0);" onclick='windowAction(this,<?=json_encode ($returnData)?>);'><?=$this->getText ('return')?></a>
	</p>
<?php } ?>
