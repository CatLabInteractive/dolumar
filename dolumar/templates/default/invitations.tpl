<p class="maybe">Your invitation code is:<br />
	<input type="text" value="<?=$invKey?>" readonly="readonly" onClick="javascript:this.focus();this.select();" />
</p>

<?php if ($invLeft > 0) { ?>
	<p class="true">You can use it <?=$invLeft?> more times.</p>
<?php } else { ?>
	<p class="true">You can use it <?=$invLeft?> more times.</p>
<?php } ?>