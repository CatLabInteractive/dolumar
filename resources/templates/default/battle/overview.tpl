<!-- <h1><?=$title?></h1> -->

<p class="maybe">
	<?=$this->getText ('setFormation1');?>
	<a href="javascript:void(0);" onclick="openWindow('Formation', {'vid':<?=$vid?>}); closeThisWindow(this);"><?=$this->getText ('setFormation2');?></a>
	<?=$this->getText ('setFormation3');?>
</p>

<?=$loghtml?>

<h2><?=$this->getText ('actions'); ?></h2>
<ul class="actions">
	<li>
		<a href="javascript:void(0);" onclick="windowAction(this, {'action':'attack','command':'attack'});"><?=$this->getText ('attack'); ?></a>
	</li>

	<li>
		<a href="javascript:void(0);" onclick="windowAction(this, {'action':'support','command':'support'});"><?=$this->getText ('support'); ?></a>
	</li>
</ul>
