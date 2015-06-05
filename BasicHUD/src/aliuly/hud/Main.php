<?php
namespace aliuly\hud;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\permission\Permission;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;


interface Formatter {
	static public function formatString(Main $plugin,$format,Player $player);
}
abstract class FixedFormat implements Formatter {
	static public function formatString(Main $plugin,$format,Player $player) {
		return $format;
	}
}
abstract class PhpFormat implements Formatter {
	static public function formatString(Main $plugin,$format,Player $player) {
		ob_start();
		eval("?>".$format);
		return ob_get_clean();
	}
}
abstract class StrtrFormat implements Formatter {
	static public function formatString(Main $plugin,$format,Player $player) {
		$vars = $plugin->getVars($player);
		return strtr($format,$vars);
	}
}

class PopupTask extends PluginTask{
	public function __construct(Main $plugin){
		parent::__construct($plugin);
	}

	public function getPlugin(){
		return $this->owner;
	}

	public function onRun($currentTick){
		$plugin = $this->getPlugin();
		if ($plugin->isDisabled()) return;

		foreach ($plugin->getServer()->getOnlinePlayers() as $pl) {
			$msg = $plugin->getMessage($pl);
			if ($msg != "") $pl->sendPopup($msg);
		}
	}

}

class Main extends PluginBase implements Listener,CommandExecutor {
	protected $_getMessage;
	protected $_getVars;

	protected $format;
	protected $sendPopup;
	protected $disabled;

	static public function pickFormatter($format) {
		if (strpos($format,"<?php") !== false|| strpos($format,"<?=") !== false) {
			return __NAMESPACE__."\\PhpFormat";
		}
		if (strpos($format,"{") !== false && strpos($format,"}")) {
			return __NAMESPACE__."\\StrtrFormat";
		}
		return __NAMESPACE__."\\FixedFormat";
	}

	static public function bearing($deg) {
		// Determine bearing
		if (22.5 <= $deg && $deg < 67.5) {
			return "NW";
		} elseif (67.5 <= $deg && $deg < 112.5) {
			return "N";
		} elseif (112.5 <= $deg && $deg < 157.5) {
			return "NE";
		} elseif (157.5 <= $deg && $deg < 202.5) {
			return "E";
		} elseif (202.5 <= $deg && $deg < 247.5) {
			return "SE";
		} elseif (247.5 <= $deg && $deg < 292.5) {
			return "S";
		} elseif (292.5 <= $deg && $deg < 337.5) {
			return "SW";
		} else {
			return "W";
		}
		return (int)$deg;
	}

	/**
	 * Gets the contents of an embedded resource on the plugin file.
	 *
	 * @param string $filename
	 *
	 * @return string, or null
	 */
	public function getResourceContents($filename){
		$fp = $this->getResource($filename);
		if($fp === null){
			return null;
		}
		$contents = stream_get_contents($fp);
		fclose($fp);
		return $contents;
	}

	public function getMessage($player) {
		$fn = $this->_getMessage;
		return $fn($this,$player);
	}

	public function getVars($player) {
		$vars = [
			"{BasicHUD}" => $this->getDescription()->getFullName(),
			"{MOTD}" => $this->getServer()->getMotd(),
			"{player}" => $player->getName(),
			"{world}" => $player->getLevel()->getName(),
			"{x}" => (int)$player->getX(),
			"{y}" => (int)$player->getY(),
			"{z}" => (int)$player->getZ(),
			"{yaw}" => (int)$player->getYaw(),
			"{pitch}" => (int)$player->getPitch(),
			"{bearing}" => self::bearing($player->getYaw()),
			"{BLACK}" => TextFormat::BLACK,
			"{DARK_BLUE}" => TextFormat::DARK_BLUE,
			"{DARK_GREEN}" => TextFormat::DARK_GREEN,
			"{DARK_AQUA}" => TextFormat::DARK_AQUA,
			"{DARK_RED}" => TextFormat::DARK_RED,
			"{DARK_PURPLE}" => TextFormat::DARK_PURPLE,
			"{GOLD}" => TextFormat::GOLD,
			"{GRAY}" => TextFormat::GRAY,
			"{DARK_GRAY}" => TextFormat::DARK_GRAY,
			"{BLUE}" => TextFormat::BLUE,
			"{GREEN}" => TextFormat::GREEN,
			"{AQUA}" => TextFormat::AQUA,
			"{RED}" => TextFormat::RED,
			"{LIGHT_PURPLE}" => TextFormat::LIGHT_PURPLE,
			"{YELLOW}" => TextFormat::YELLOW,
			"{WHITE}" => TextFormat::WHITE,
			"{OBFUSCATED}" => TextFormat::OBFUSCATED,
			"{BOLD}" => TextFormat::BOLD,
			"{STRIKETHROUGH}" => TextFormat::STRIKETHROUGH,
			"{UNDERLINE}" => TextFormat::UNDERLINE,
			"{ITALIC}" => TextFormat::ITALIC,
			"{RESET}" => TextFormat::RESET,
		];
		if ($this->_getVars !== null) {
			$fn = $this->_getVars;
			$fn($this,$vars,$player);
		}
		return $vars;
	}

	public function sendPopup($player,$msg,$length=3) {
		if ($this->isEnabled()) {
			$n = strtolower($player->getName());
			$this->sendPopup[$n] = [ $msg, microtime(true)+$length ];
			$msg = $this->getMessage($player);
		}
		$player->sendPopup($msg);
	}

	public function defaultGetMessage($player) {
		$n = strtolower($player->getName());
		if (isset($this->sendPopup[$n])) {
			list($msg,$timer) = $this->sendPopup[$n];
			if (microtime(true) < $timer) return $msg;
			unset($this->sendPopup[$n]);
		}
		if (isset($this->disabled[$n])) return "";

		// Manage custom groups
		if (is_array($this->format[0])) {
			foreach ($this->format as $rr) {
				list($rank,$fmt,$formatter) = $rr;
				if ($player->hasPermission("basichud.rank.".$rank)) break;
			}
		} else {
			list($fmt,$formatter) = $this->format;
		}
		$txt = $formatter::formatString($this,$fmt,$player);
		return $txt;
	}

	public function onEnable(){
		$this->disabled = [];
		$this->sendPopup = [];
		if (!is_dir($this->getDataFolder())) mkdir($this->getDataFolder());
		/* Save default resources */
		$this->saveResource("message-example.php",true);
		$this->saveResource("vars-example.php",true);

		$defaults = [
			"version" => $this->getDescription()->getVersion(),
			"ticks" => 15,
			"format" => "{GREEN}{BasicHUD} {WHITE}{world} ({x},{y},{z}) {bearing}",
		];
		$cf = (new Config($this->getDataFolder()."config.yml",
								Config::YAML,$defaults))->getAll();
		if (is_array($cf["format"])) {
			$this->format = [];
			foreach ($cf["format"] as $rank=>$fmt) {
				$this->format[] = [ $rank, $fmt, self::pickFormatter($fmt) ];
				$p = new Permission("basichud.rank.".$rank,
										  "BasicHUD format ".$rank, false);
				$this->getServer()->getPluginManager()->addPermission($p);
			}
		} else {
			$this->format = [ $cf["format"], self::pickFormatter($cf["format"]) ];
		}
		$code = '$this->_getMessage = function($plugin,$player){';
		if (file_exists($this->getDataFolder()."message.php")) {
			$code .= file_get_contents($this->getDataFolder()."message.php");
		} else {
			$code .= $this->getResourceContents("message-example.php");
		}
		$code .= '};';
		eval($code);

		if (file_exists($this->getDataFolder()."vars.php")) {
			$code = '$this->_getVars = function($plugin,&$vars,$player){' ."\n".
					file_get_contents($this->getDataFolder()."vars.php").
					'};'."\n";
			//echo $code."\n";//##DEBUG
			eval($code);
		} else {
			$this->_getVars = null;
		}
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupTask($this), $cf["ticks"]);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onQuit(PlayerQuitEvent $ev) {
		$n = strtolower($ev->getPlayer()->getName());
		if (isset($this->sendPopup[$n])) unset($this->sendPopup[$n]);
		if (isset($this->disabled[$n])) unset($this->disabled[$n]);
	}

	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args) {
		if ($cmd->getName() != "hud") return false;
		if (!($sender instanceof Player)) {
			$sender->sendMessage("This command can only be used in-game");
			return true;
		}
		$n = strtolower($sender->getName());
		if (count($args) == 0) {
			if (isset($this->disabled[$n])) {
				$sender->sendMessage("HUD is OFF");
				return true;
			}
			if (is_array($this->format[0])) {
				foreach ($this->format as $rr) {
					list($rank,,) = $rr;
					if ($sender->hasPermission("basichud.rank.".$rank)) break;
				}
				$sender->sendMessage("HUD using format $rank");
				$fl = [];
				foreach ($this->format as $rr) {
					list($rank,,) = $rr;
					$fl[] = $rank;
				}
				if ($sender->hasPermission("basichud.cmd.switch")) {
					$sender->sendMessage("Available formats: ".
												implode(", ",$fl));
				}
				return true;
			}
			$sender->sendMessage("HUD is ON");
			return true;
		}
		if (count($args) != 1) return false;
		$mode = strtolower(array_shift($args));
		if (is_array($this->format[0])) {
			// Check if the input matches any of the ranks...
			foreach ($this->format as $rr1) {
				list($rank,,) = $rr1;
				if (strtolower($rank) == $mode) {
					// OK, user wants to switch to this format...
					if (!$sender->hasPermission("basichud.cmd.switch")) {
						$sender->sendMessage("You are not allowed to do that");
						return true;
					}
					foreach ($this->format as $rr2) {
						list($rn,,) = $rr2;
						if ($rank == $rn) {
							$sender->addAttachment($this,"basichud.rank.".$rn,true);
						} else {
							$sender->addAttachment($this,"basichud.rank.".$rn,false);
						}
					}
					$sender->sendMessage("Switching to format $rank");
					return true;
				}
			}
		}
		if (!$sender->hasPermission("basichud.cmd.toggle")) {
			$sender->sendMessage("You are not allowed to do that");
			return true;
		}
		$mode = filter_var($mode,FILTER_VALIDATE_BOOLEAN);
		if ($mode) {
			if (isset($this->disabled[$n])) unset($this->disabled[$n]);
			$sender->sendMessage("Turning on HUD");
			return true;
		}
		$this->disabled[$n] = $n;
		$sender->sendMessage("Turning off HUD");
		return true;
	}
}
