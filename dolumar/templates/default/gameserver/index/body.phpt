<div id="gui-preloading" style="display: none;">
	<?php
		foreach (scandir ("dolumar/theme/dolumar") as $v)
		{
			if (is_file ("dolumar/theme/dolumar/".$v))
			{
				echo '<img src="dolumar/theme/dolumar/'.$v.'" alt="preloaded-image" />' . "\n";
			}
		}
	?>
</div>
