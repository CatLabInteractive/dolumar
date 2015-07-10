<?php $this->setTextSection ('clearmultis', 'admin'); ?>

<h2><?=$this->getText ('title'); ?></h2>

<h3><?=$this->getText ('newclearances'); ?></h3>

<p class="information"><span class="info-icon"></span><?=$this->getText ('about'); ?></p>

<form method="post" action="<?=$action_url?>">
	<input type="hidden" class="hidden" name="action" value="clearmultis" />

	<fieldset>
		<legend><?=$this->getText ('clear'); ?></legend>
		
		<ol>
			<?php if (isset ($list_players)) { ?>
				<?php foreach ($list_players as $v) { ?>
					<li>
						<input name="clear_chk_player_<?=$v['id']?>" type="checkbox" class="checkbox" value="clear" />
						<label class="checkbox"><?=$v['name']?></label>
					</li>
				<?php } ?>
			<?php } ?>
			
			<li>
				<label for="reason"><?=$this->getText ('reason'); ?></label>
				<input type="text" name="reason" id="reason" />
			</li>
			
			<li>
				<button type="submit"><?=$this->getText ('submit'); ?></button>
			</li>
		</ol>
	</fieldset>
</form>

<h3><?=$this->getText ('clearances'); ?></h3>
<?php if (isset ($list_clearances)) { ?>

	<ul class="clearances">
		<?php foreach ($list_clearances as $v) { ?>
		<li>
			<?=$v['player1'] ?> - <?=$v['player2']?> 
			(<a href="<?=$v['remove_url']?>"><?=$this->getText ('remove'); ?></a>)<br />
			<?=$v['reason']?>
		</li>
		<?php } ?>
	</ul>

<?php } else { ?>
	<p><?=$this->getText ('noclearances'); ?></p>
<?php } ?>
