<?php
	// Notice shown on a never-ending world (NEVERENDING=1). Include it with $neverending_key set to one of the
	// keys in neverending.lng (welcome, ranking, register, serversettings). Prints nothing on a normal world.
	if (Dolumar_Players_Server::isNeverending ())
	{
		$neverending_text = Neuron_Core_Text::getInstance ();
?>
	<div class="information neverending-notice">
		<span class="info-icon">
			<strong><?=$neverending_text->get ('title', 'neverending', 'neverending')?></strong><br />
			<?=$neverending_text->get ($neverending_key, 'neverending', 'neverending')?>
		</span>
	</div>
<?php
	}
?>
