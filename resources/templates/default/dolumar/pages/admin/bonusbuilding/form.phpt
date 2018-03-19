<form method="post" enctype="multipart/form-data">
	<fieldset>
		<ol>
			<li>
				<label for="imagefile">Image file:</label>
				<input type="file" name="imagefile" id="imagefile" />
			</li>
			
			<li>
				<label for="startdate">Start date:</label>
				<input type="text" name="startdate" id="startdate">
			</li>
			
			<li>
				<label for="enddate">End date:</label>
				<input type="text" name="enddate" id="enddate">
			</li>
		</ol>
	</fieldset>
			
	<?php foreach ($list_languages as $v) { ?>
		<fieldset>
			<legend><?=strtoupper ($v)?></legend>
			<ol>
				<li>
					<label for="title_<?=$v?>">Title <?=strtoupper ($v)?>:</label>
					<input type="text" id="title_<?=$v?>" name="title_<?=$v?>" />
				</li>
		
				<li>
					<label for="description_<?=$v?>">Description <?=strtoupper ($v)?>:</label>
					<textarea id="description_<?=$v?>" name="description_<?=$v?>"></textarea>
				</li>
			</ol>
		</fieldset>
	<?php } ?>
	
	<fieldset>
		<ol>
			<li>
				<button type="submit">Add Bonus Building</button>
			</li>
		</ol>
	</fieldset>
</form>
