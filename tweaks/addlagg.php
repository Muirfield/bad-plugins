<?php

/**
 * Makes your server more laggy :)
 *
 * @name addlagg
 * @main aliuly\hack\AddLagg
 * @version 1.0.0
 * @api 1.12.0
 * @description Adds lagg to a server
 * @author aliuly
 */


namespace aliuly\hack{
	use pocketmine\plugin\PluginBase;
	use pocketmine\scheduler\PluginTask;

	class LaggTask extends PluginTask{
		private $usecs;
		public function __construct(PluginBase $plugin,$usecs){
			parent::__construct($plugin);
			$this->usecs = $usecs;
		}

		public function getPlugin(){
			return $this->owner;
		}

		public function onRun($currentTick){
			$plugin = $this->getPlugin();
			if ($plugin->isDisabled()) return;
			usleep($this->usecs);
		}

	}

	class AddLagg extends PluginBase{
		public function onEnable(){
			$usleep = 100 * 1000;
			$ticks = 5;
			$this->getServer()->getScheduler()->scheduleRepeatingTask(
				new LaggTask($this,$usleep), $ticks);
		}
	}
}
