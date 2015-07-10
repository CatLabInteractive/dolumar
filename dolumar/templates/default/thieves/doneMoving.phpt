<p><?=$this->getText ('done')?></p>
<p>
	<?php $toReturn = $this->getClickTo ('toReturn'); ?>
	
	<?=$toReturn[0]?><a href="javascript:void(0);" onclick="windowAction(this,<?=htmlentities (json_encode ($input), ENT_QUOTES)?>);"><?=$toReturn[1]?></a><?=$toReturn[2]?>
</p>
