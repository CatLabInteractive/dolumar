<?php $this->setTextSection ('market', 'buildings'); ?>

<?php 
	$data = array
	(
		'maxtransfers' => $maxtransfers,
		'outgoingtransfers' => $outgoingtransfers,
		'maxtransfers_premium' => $maxtransfers_premium
	);
?>

<h3><?=$this->getText ('transfers'); ?></h3>
<p class="false"><?=$this->putIntoText ($this->getText ('isbusy'), $data); ?></p>

<?php if (!$ispremium) { ?>
<p class="information">
		<span class="info-icon">
			<?=$this->putIntoText ($this->getText ('busypremium'), $data); ?>
		</span>
</p>
<?php } ?>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'page':'home'});"><?=$this->getText ('overview')?></a></p>