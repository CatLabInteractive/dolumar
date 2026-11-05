<?php
	/* The way back from every step after a target was picked (Dolumar_Windows_Battle::getBackLinks). */
	$again = htmlspecialchars (json_encode (array ('action' => $action, 'command' => $action)), ENT_QUOTES);
?>
<div class="battle-report"><p class="br-back">
	<a href="javascript:void(0);" onclick="windowAction(this, <?=$again?>);">‹ <?=$this->getText ('otherTarget')?></a>
	<span class="ui-muted">·</span>
	<a href="javascript:void(0);" onclick="windowAction(this, {'overview':'true'});"><?=$this->getText ('allBattles', 'report2')?></a>
</p></div>
