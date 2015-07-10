<?php
class Dolumar_Windows_AprilFool extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('350px', '300px');
		$this->setTitle ('News bulletin');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		//return false;
		return '<h2>Dolumar sold to Travian GmbH</h2>'.
			'<p class="false">April fool! Dolumar was not sold and we have no intentions to raise the '.
			'cost for premium accounts. We would never approve the changes listed here.'.
			'</p><div class="text" style="background: white;">'.
			'<p><img src="http://travian.com/img/en/travian0.gif" style="float: left;" />About 3 months ago, I got contacted by Travian GmbH, a leading '.
			'browser game developer in Germany. They were very interested in the '.
			'fresh gameplay and neat interface Dolumar provided and presented me an '.
			'offer I could not refuse.</p>'.
			'<p>Due to non disclosure agreements I couldn\'t make this upcomming deal public, '.
			'but I contacted a selected few players to discuss the future of Dolumar. They all '.
			'agreed I couldn\'t refuse the offer Travian GmbH made me, and thus Dolumar is now property '.
			'of Travian GmbH.</p>'.
			'<p>Our team (Vilmore and me) will keep maintaining Dolumar and development will continue as planned.<p>'.
			'<p>However, there will be a few changes to comply with Travians business plan and monetization model:</p>'.
			'<ul><li>The price of a premium account will be changed to 9.99 USD / month.</li>'.
			'<li>You will be able to use premium credits to buy runes.</li>'.
			'<li>Building times will be doubled for non premium users.</li>'.
			'<li>Scouting costs will depend on how much premium credits you have already used.</li>'.
			'<li>Honour does not count for premium players.</li>'.
			'<li>Premium players will steal double from non premium players.</li>'.
			'</ul><p>We believe that these changes will not influence the gameplay.</p>'.
			'<p>We thank all players for their support.</p>'.
			'<hr /><p>Thijs Van der Schaeghe<br />Ghent, 1st of April 2009.</p></div>';
	}
}
?>
