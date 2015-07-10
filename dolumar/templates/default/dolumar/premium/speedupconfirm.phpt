<?php $this->setTextSection ('speedup', 'statusbar'); ?>

<p>Confirm</p>

<p>

	<a href="<?=$confirm_url?>" target="_BLANK" onclick="closeThisWindow (this); return !Game.gui.openWindow(this, 450, 190);"><?=$this->getText ('confirm'); ?></a>

</p>