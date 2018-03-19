<?php $this->setTextSection ('gifts', 'gifts'); ?>

<h2><?=$this->getText ('gifts'); ?></h2>
<p><?=$this->getText ('about'); ?></p>

<?=Neuron_URLBuilder::getInstance()->getUpdateUrl ('gifts', $this->getText ('send'), array ('send' => 'send')); ?>