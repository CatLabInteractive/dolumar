<?php $this->setTextSection ('general', 'report'); ?>

<h2><?=$this->getText ($type, 'types');?></h2>

<p>
	<?=$this->getText ('date');?> <?=$date?>

	<?php if (isset ($target)) { ?>
		<br /><?=$this->getText ('target');?> <?=$target?>
	<?php } ?>
</p>

<div class="fancybox">
	<?php if (isset ($list_records)) { ?>

		<ul>
			<?php foreach ($list_records as $v) { ?>
				<li><?=$v?></li>
			<?php } ?>
		</ul>

	<?php } ?>
</div>
