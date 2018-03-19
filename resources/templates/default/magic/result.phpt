<h2><?=$this->getText ('title')?></h2>

<?php if ($success) { ?><p class="true"><?php } else { ?><p class="false"><?php } ?>

	<?php if (isset ($message)) { ?>
		<?=$message?>
	<?php } elseif (isset ($error)) { ?>
		<?=$this->getText ($error);?>
	<?php } ?>
	
	<?php if (isset ($retry) && $retry)  { ?>
		<?php $toCast = $this->getClickTo ('toRetry'); ?>
	
		<?php $action = json_encode (isset ($inputData) ? array_merge ($inputData, array ('confirm' => 'yes') ) : array ('confirm' => 'yes')); ?>
		<p><?=$toCast[0]?><a href="javascript:void(0);" onclick='return windowAction (this, <?=$action?>);'><?=$toCast[1]?></a><?=$toCast[2]?></p>
	<?php } ?>
</p>

<?php if (isset ($extra)) { ?>
	<?=$extra?>
<?php } ?>

<p>
	<a href="javascript:void(0);" onclick="return windowAction (this, {'page':'select'});"><?=$this->getText ('overview')?></a>
</p>
