<?php

/**
 * Simple commands
 *
 * @name myCmds
 * @main aliuly\script\MyCmds
 * @version 1.0.0
 * @api 1.12.0
 * @description Simple command implementations
 * @author aliuly
 */


namespace aliuly\script{
	use pocketmine\plugin\PluginBase;
	use pocketmine\command\ConsoleCommandSender;
	use pocketmine\command\CommandExecutor;
	use pocketmine\command\CommandSender;
	use pocketmine\command\Command;

	use aliuly\common\MPMU;


	class MyCmds extends PluginBase implements CommandExecutor{
		public function onEnable(){
			MPMU::addCommand($this,$this,"x1",[
					"description" => "x1 test",
					"usage" => "/x1 blah",
				]);
		}
		public function onCommand(CommandSender $sender,Command $cmd,$label, array $args) {
			switch($cmd->getName()) {
				case "x1":
					return true;
				case "x2":
					return true;
			}
			return false;

		}
	}
}
