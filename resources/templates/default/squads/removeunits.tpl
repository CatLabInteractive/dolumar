<?php 
	$current_tab = 'removeunits';
	include ('tabs.phpt'); 
?>

<h3><?=$title?></h3>
<p><?=$about?></p>

<?php if (isset ($error)) { echo '<p class="false">'.$error.'</p>'; } ?>

<form onsubmit="return submitForm(this);">

	<fieldset>

		<input type="hidden" class="hidden" name="id" value="<?=$squadId?>" />
		<input type="hidden" class="hidden" name="action" value="removeUnits" />

			<?php if (isset ($list_units)) { ?>
				<?php foreach ($list_units as $v) { ?>
					<label>
						<?=$v[0]?> 
						<span class="sidenote">(<?php echo Neuron_Core_Tools::putIntoText ($this->getText ('available'), array ($v[1])); ?>)</span>
					</label>
					<input type="text" style="width: 50px;" name="unit_<?=$v[2]?>" />
			<?php } ?>
		<?php } else { ?>
			<label><?=$noUnits?></label>
		<?php } ?>

	<button type="submit"><?=$this->getText ('addUnits')?></button>
	
	</fieldset>

</form>

<!--
<p>
	<?=$toReturn[0]?><a href="javascript:void(0);" onclick="windowAction (this, {'page':'squad','id':<?=$squadId?>});"><?=$toReturn[1]?></a><?=$toReturn[2]?>
</p>
-->
