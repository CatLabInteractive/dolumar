<?php $this->setTextSection ('login', 'admin'); ?>

<div id="container" class="login">
	<?php if (isset ($error)) { ?>
		<p class="false"><?= $this->getText ($error); ?></p>
	<?php } ?>

	<form method="post" action="<?=$action?>" id="login">
		<fieldset>
			<legend><?= $this->getText ('login'); ?></legend>
		
			<ol>
				<li>
					<label for="username"><?= $this->getText ('username'); ?></label>
					<input type="text" name="username" id="username" />
				</li>
			
				<li>
					<label for="password"><?= $this->getText ('password'); ?></label>
					<input type="password" name="password" id="password" />
				</li>
			
				<li class="button">
					<button type="submit"><?= $this->getText ('submit'); ?></button>
				</li>
			</ol>
		</fieldset>
	</form>
</div>
