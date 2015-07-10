<h2><?=$this->getText ('title')?></h2>

<p>
	<?=$this->getText ('distance')?> <?=$distance?><br />
	<?=$this->getText ('duration')?> <?=$duration?>
</p>

<p>
	<a href="javascript:void(0);" onclick='windowAction(this,<?=htmlentities(json_encode ($input), ENT_QUOTES)?>);'><?=$this->getText ('confirm')?></a>
</p>
