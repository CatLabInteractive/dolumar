<?php
class Dolumar_Windows_Disclaimer extends Neuron_GameServer_Windows_Window
{
	public function setSettings ()
	{
		$text = Neuron_Core_Text::__getInstance ();
	
		// Window settings
		$this->setSize ('250px', '300px');
		$this->setTitle ('Village Overview');
		
		$this->setAllowOnlyOnce ();
	}
	
	public function getContent ()
	{
		//return false;
		return '<h1>The future of Dolumar</h1>'.
			'<p>Hello everyone! I have great news! I\'m working on the next version of Dolumar, which I will launch on '.
			'newyears eve. I\'m taking the old version offline until then, so that is why your village has been remove.</p>'.
			'<p>You will not be able to register / start a new village until '.date (DATETIME, GAME_LAUNCHDATE).'<br />(time zone: '.TIME_ZONE.').</p>'.
			'<p>I hope you can understand my decision. A reset was required anyway, so let\'s hope the next version of the '.
			'game will be better then the last one!</p>'.
			'<p>See you soon!<br />The Dolumar Development team.</p>';
	}
}
?>
