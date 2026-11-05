<div class="serversettings">
	<p><?=$this->putIntoText ($this->getText ('intro'), array ('speed' => '<strong>' . $speed . '</strong>'))?></p>

	<table class="serversettings-speeds">
		<?php foreach ($speeds as $i => $v) { ?>
			<tr<?php if ($i % 2 == 0) { echo ' class="even"'; } ?>>
				<td><?=$v[0]?></td>
				<td class="serversettings-value"><?=$v[1]?></td>
			</tr>
		<?php } ?>
	</table>

	<h3><?=$this->getText ('endgame')?></h3>

	<?php if ($endgame_state === Dolumar_Windows_Serversettings::ENDGAME_NEVER) { ?>
		<?php $neverending_key = 'serversettings'; include (TEMPLATE_DIR.'blocks/neverending.phpt'); ?>

	<?php } elseif ($endgame_state === Dolumar_Windows_Serversettings::ENDGAME_STARTED) { ?>
		<p class="maybe ui-msg"><strong class="ui-msg-title"><?=$this->getText ('started')?></strong><?=$this->getText ('startedText')?></p>

	<?php } elseif ($endgame_state === Dolumar_Windows_Serversettings::ENDGAME_FINISHED) { ?>
		<p class="maybe ui-msg"><?=$this->getText ('finished')?></p>

	<?php } else { ?>
		<p><?=$endgame_in?></p>
		<p class="ui-muted"><?=$this->getText ('explain')?></p>
	<?php } ?>
</div>
