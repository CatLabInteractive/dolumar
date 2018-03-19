<h2>Reset account <?=$username?></h2>

<?php if ($isDone) { ?>
	<p class="true">Your reset action has been submitted. An administrator will review it and execute it.</p>
<?php } else { ?>
	<form method="post" action="<?=$action?>">
	
		<input type="hidden" class="hidden" name="seckey" value="<?=$seckey?>" />
	
		<fieldset>
			<legend>Reset message</legend>
			<p>This message will be sent to the player. Please be friendly and correct. Explain why his account has been reset.</p>
			<ol>
				<li>
					<label for="title">Message title</label>
					<input type="text" name="title" id="title" value="Your account has been reset" />
				</li>
			
				<li>
					<label for="message">Message</label>
					<textarea id="message" name="message">Hello <?=$username?>,
						
Due to terms of service violations we were forced to reset your account on <?=ABSOLUTE_URL?>.

I hope you understand our decision. You are welcome again as long as you respect the rules.

Greetings,
<?=$myname?>
</textarea>
				</li>
				
				<li>
					<label for="reason">Reason (private)</label>
					<textarea id="reason" name="reason" class="small"></textarea>
				</li>
			
				<li>
					<button type="submit">Reset <?=$username?></button>
				</li>
			</ol>
		</fieldset>
	</form>
<?php } ?>
