<h2><?=$upgrade?></h2>

<?php
if (isset ($error))
{
	if ($errorV == 'done')
	{
		echo '<p class="true">'.$error.'</p>';
	}
	
	else
	{
		echo '<p class="false">'.$error.'</p>';
	}
}

else
{
	echo '<p>'.$about.'</p>';
}
?>

<?php if (isset ($info)) { ?>
	<?=$info?>
<?php } ?>

<h3><?=$cost?></h3>

<form onsubmit="return submitForm(this);">

	<p class="maybe resources-cost">
		<?=$upgradeCost?>
	</p>

	<p>
		<?php echo Neuron_Core_Tools::putIntoText ($this->getText ('duration', 'upgrade', 'building'), array ('duration' => $duration)); ?>
	</p>

	<input type="hidden" class="hidden" name="page" value="upgrade" />
	<input type="hidden" class="hidden" name="upgrade" value="confirm" />

	<button type="submit"><?=$upgradeLink?></button>
</form>

<p>
<?php
if (isset ($back))
{
	echo $back[0].'<a href="javascript:void(0)" onclick="windowAction (this, \'page=home\');">'.$back[1].'</a>'.$back[2];
}
?>
</p>
