<div class="battlefield">
	<div class="innerbattlefield">
		<div class="battlefield-decoration-left">&nbsp;</div>
		
		<?php $first = true; foreach ($slots as $slot) { 
			if ($first)
			{
				echo '<div class="first battlefield-slot '.$slot['sName'].'">';
				$first = false;
			}
			else
			{
				echo '<div class="battlefield-slot '.$slot['sName'].'">';
			}
	
			foreach ($slot['units'] as $v)
			{
				echo '<div class="unit '.$v['side'].' ' . $v['status'] . '">'.
					'<img src="'.$v['img'].'" title="'.$v['squad'].' (frontage '.$v['frontage'].')" /></div>';
			}
	
			echo '&nbsp;';
	
			echo '</div>';

		} ?>
		
		<div class="battlefield-decoration-right">&nbsp;</div>
	</div>

</div>
