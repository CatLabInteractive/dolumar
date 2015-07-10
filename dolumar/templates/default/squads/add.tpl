<h2><?=$title?></h2>

<?php if (isset ($warning)) { ?>
	<p class="false"><?=$warning; ?></p>
<?php } ?>

<?php if (isset ($list_units)) { ?>
	<form onsubmit="return submitForm(this);">
		<fieldset>
			<input type="hidden" class="hidden" name="action" value="add" />
		
			<label><?=$this->getText ('unit', 'addSquad', 'squads'); ?>:</label>
			<select name="squadUnit">
				<?php if (isset ($list_units)) { ?>
					<?php foreach ($list_units as $v) { ?>
						<option value="<?=$v['id']?>"><?=$v['name']?></option>
					<?php } ?>
				<?php } ?>
			</select>
		
			<label><?=$name?>:</label>
			<input type="text" name="squadName" />
			<button type="submit"><?=$submit?></button>
		</fieldset>
	</form>
<?php } else { ?>

	<p class="false"><?=$this->getText ('noUnits')?></p>

<?php } ?>

<p>
	<?=$toReturn[0]?><a href="javascript:void(0);" onclick="windowAction (this, {'page':'overview'});"><?=$toReturn[1]?></a><?=$toReturn[2]?>
</p>
