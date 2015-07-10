<?php $this->setTextSection ('market', 'buildings'); ?>

<?php
	foreach ($messages as $v)
	{
		echo '<p class="'.($v[1] ? 'true' : 'false').'">'.$v[0].'</p>';
	}
?>

<p><?=$this->getText('selectTarget')?></p>

<?=$choosetarget?>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'page':'home'});"><?=$this->getText ('overview')?></a></p>
