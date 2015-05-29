<?php
namespace aliuly\chatscribe;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use LogLevel;

class FileLogger {
	private $owner;
	private $file;
	public function __construct(PluginBase $owner,$target) {
		$this->owner = $owner;
		$fp = @fopen($target, "a");
		if ($fp === false) {
			$owner->getServer()->getLogger()->error("Error writing to $target");
			throw new \RuntimeException("$target: unable to open");
			return;
		}
		$this->file = $target;
	}
	public function logMsg(CommandSender $pl,$msg) {
		$txt =
			  date("Y-m-d H:i:s",time())." ".
			  "[".$pl->getName()."]: ".
			  $msg.
			  "\n";
		file_put_contents($this->file,$txt,FILE_APPEND);
	}
}
