<?php $this->setTextSection ('buyrunes', 'premium'); ?>

<h2><?=$this->getText ('buyrunes'); ?></h2>

<div class="fancybox">

	<p><?=$this->getText ('yousure'); ?></p>
	
	<table>
		<?php foreach ($myrunes as $k => $v) { ?>
			<tr>
				<td class="icon"><span class="rune <?=$k?>"><span>&nbsp;</span></span></td>
				<td><?=$v?> <?=$this->getText ($k, $v > 1 ? 'runeDouble' : 'runeSingle', 'main')?></td>
			</tr>
		<?php } ?>
	</table>
	
	<p>
		<?=$this->putIntoText ($this->getText ('cost'), array ('credits' => $credits));?>
	</p>
	
	<p>
		<a href="<?=$confirm_url?>" target="_BLANK" onclick="windowAction(this,{'action':'overview'}); return !Game.gui.openWindow(this, 450, 190);"><?=$this->getText ('confirm'); ?></a>
	</p>
</div>

<p><a href="javascript:void(0);" onclick="windowAction(this,{'action':'overview'});"><?=$this->getText 
('overview', 'shop'); ?></a></p>
