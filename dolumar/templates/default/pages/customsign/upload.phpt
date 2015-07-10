<p class="details">
	Improve the looks of your village by building signs. 
	Upload an image and our system will convert it to a 
	nice sign you can build in your village. 
</p>

<form method="post" action="<?=$action?>" enctype="multipart/form-data">

	<fieldset>
		<ol>
			<li>
				<label for="uploadfile">Upload file:</label>
				<input type="file" name="uploadfile" id="uploadfile" />
			</li>
			
			<li>
				<button type="submit">Upload image</button>
			</li>
		</ol>
	</fieldset>
</form>
