<?php
namespace aliuly\chatscribe;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\utils\Config;

class Main extends PluginBase implements CommandExecutor{
	protected $logdest;
	protected $privacy;
	protected $logging;
	protected $spySession;

	public function onEnable(){
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		$defaults = [
			"version" => $this->getDescription()->getVersion(),
			"settings" => [
				"# log" => "Either server or file",
				"log" => "server",
				"# dest" => "If file, this is a filename, otherwise emergency|alert|critical|error|warning|notice|info|debug",
				"dest" => "info",
				"# default" => "If true, will start logging by default",
				"default" => false,
				/*
				  "# listener" => "Set to early or late",
				  "listener" => "late",
				*/
				"# spy" => "Allow logging in-game",
				"spy" => false,
			],
			"# privacy" => "regular expressions and replacements used for ensuring privacy",
			"privacy" => [
				'/\/login\s*.*/' => '/login **CENSORED**',
			],
			"# warning" => "Text to show warning that logging is available",
			"warning" => ["Activities may be logged on this server"],
		];
		if (file_exists($this->getDataFolder()."config.yml")) {
			$defaults["privacy"] = [];
		}
		$cf = (new Config($this->getDataFolder()."config.yml",
								Config::YAML,$defaults))->getAll();
		switch(strtolower($cf["settings"]["log"])) {
			case "server":
				// main logger
				$this->logdest = new ServerLogger($this,$cf["settings"]["dest"]);
				break;
			case "file":
				// file logger
				$this->logdest = new FileLogger($this,$cf["settings"]["dest"]);
				break;
			default:
				$this->getServer()->getLogger()->error("Invalid log type");
				$this->getServer()->getLogger()->error("Defaults to \"server\"");
				$this->logdest = new syslog;
		}
		$this->privacy = $cf["privacy"];
		$this->logging = $cf["settings"]["default"];
		if ($this->logging) $this->getServer()->getLogger()->info("Logging started");
		/*
		  switch ($cf["settings"]["listener"]) {
		  case "early":
		  $listener = new EarlyListener($this);
		  break;
		  case "late":
		  $listener = new LateListener($this);
		  break;
		  default:
		  $this->getServer()->getLogger()->error("Invalid listener type");
		  $this->getServer()->getLogger()->error("Defaults to \"late\"");
		  }
		*/
		$listener = new LateListener($this);
		$this->getServer()->getPluginManager()->registerEvents($listener,$this);
		$this->spySession = $cf["settings"]["spy"] ? new SpySession($this) : null;
	}
	private function cleanText($msg) {
		//
		// First we apply hard-coded white-washing rules
		//
		foreach ([
			// SimpleAuth related commands
			'/\/login\s*.*/' => '/login **CENSORED**',
			'/\/register\s*.*/' => '/register **CENSORED**',
			'/\/unregister\s*.*/' => '/register **CENSORED**',
			// SimpleAuthHelper related commands
			'/\/chpwd\s*.*/' => '/chpwd **CENSORED**',
		] as $re => $txt) {
			$msg = preg_replace($re,$txt,$msg);
		}
		foreach ($this->privacy as $re => $txt) {
			$msg = preg_replace($re,$txt,$msg);
		}
		return $txt;
	}

	public function logMsg($pl,$msg,$forced = false) {
		if (!$this->logging && $this->spySession == null) return;
		if ($forced) {
			$msg = $this->cleanTxt($msg);
			if ($this->spySession) $this->spySession->logMsg($pl,$msg);
			$this->logdest->logMsg($pl,$msg);
			return;
		}
		if ($pl instanceof Player) {
			if ($pl->hasPermission("chatscribe.privacy")) return;
		}
		$msg = $this->cleanTxt($msg);
		if ($this->spySession) $this->spySession->logMsg($pl,$msg);
		$this->logdest->logMsg($pl,$msg);
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		switch ($cmd->getName()) {
			case "log":
				return $this->cmdLog($sender,$args);
			case "spy":
				if ($this->spySession) return $this->spySession->onCmd($sender,$args);
				$sender->sendMessage("That feature is NOT enabled");
				return true;
		}
		return false;
	}
	private function cmdLog(CommandSender $sender, array $args) {
		if (count($args) == 0) {
			$sender->sendMessage($this->logging ? "Logging ON" : "Logging OFF");
			return true;
		}
		if (count($args) != 1) return false;
		switch(strtolower($args[0])) {
			case "on":
				$sender->sendMessage("Logging starting");
				$this->logging = true;
				$this->logMsg($sender,">>>Logging started",true);
				break;
			case "off":
				$this->logMsg($sender,">>>Logging stopped",true);
				$sender->sendMessage("Logging stopping");
				$this->logging = false;
				break;
			default:
				return false;
		}
		return true;
	}
}
