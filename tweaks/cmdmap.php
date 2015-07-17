<?php

/**
 * Override commands example
 *
 * @name cmdmap
 * @main aliuly\example\CmdReMapper
 * @version 1.0.0
 * @api 1.12.0
 * @description Change default command implementations
 * @author aliuly
 * @softdepend libcommon
 */


namespace aliuly\example{
	use pocketmine\plugin\PluginBase;
	use pocketmine\command\ConsoleCommandSender;
	use pocketmine\command\CommandExecutor;
	use pocketmine\command\CommandSender;
	use pocketmine\command\Command;


	use aliuly\common\MPMU;
	use aliuly\common\Cmd;


	class CmdReMapper extends PluginBase implements CommandExecutor{
		public function onEnable(){
			MPMU::rmCommand($this->getServer(),"give");
			MPMU::addCommand($this,$this,"give",[
					"description" => "Give stuff",
					"usage" => "/gift [player] [object:meta] [amount]",
				]);
		}
		public function onCommand(CommandSender $sender,Command $cmd,$label, array $args) {
			switch($cmd->getName()) {
				case "give":
					Cmd::exec($sender,["gift ".implode(" ",$args)],false);
					return true;
			}
			return false;
		}
	}
}
