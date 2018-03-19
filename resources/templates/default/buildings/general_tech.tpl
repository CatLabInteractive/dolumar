<h3><?php echo $title; ?></h3>

<?php if (isset ($notFound)) { ?>

	<p><?php echo $notFound; ?></p>
	
<?php } elseif (isset ($done)) { ?>

	<p><?php echo $done; ?></p>

<?php } else { ?>

	<?php if (isset ($error)) { ?>
		<p class="false"><?php echo $error; ?></p>
	<?php } ?>

	<p><?php echo $youSure; ?></p>

	<?php if (!empty ($description)) { ?>
		<p class="maybe"><?php echo $description; ?></p>
	<?php } ?>
	
	<p class="maybe"><?php echo $cost; ?><br /><?php echo $duration; ?></p>

	<p><?php echo $confirm[0]; ?><a href="javascript:void(0);" onclick="windowAction(this,{'page':'technology','technology':'<?php echo $technology; ?>','confirm':'<?php echo $confirmation; ?>'});"><?php echo $confirm[1]; ?></a><?php echo $confirm[2]; ?></p>

<?php } ?>

<p><?php echo $return[0].'<a href="javascript:void(0);" onclick="windowAction(this,{\'page\':\'home\'});">'.$return[1].'</a>'.$return[2]; ?></p>
