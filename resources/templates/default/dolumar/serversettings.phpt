<p>Game speed: X<?=SPEED_FACTOR?></p>
<ul>
	<li>
		Resources: X<?=GAME_SPEED_RESOURCES?>
	</li>

	<li>
		Buildings: X<?=GAME_SPEED_BUILDINGS?>
	</li>

	<li>
		Troop movement: X<?=GAME_SPEED_MOVEMENT?>
	</li>

	<li>
		Effects: X<?=GAME_SPEED_EFFECTS?>
	</li>

    <li>
        <a href="<?php echo WIKI_GUIDE_URL; ?>">Wiki</a>
    </li>
</ul>
<?php if (Dolumar_Players_Server::isNeverending ()) { ?>
	<?php $neverending_key = 'serversettings'; include (TEMPLATE_DIR.'blocks/neverending.phpt'); ?>
<?php } else { ?>
	<p>End game starts in: <?=$endgame_start?>.</p>
<?php } ?>