<?php
namespace aliuly\hud;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;

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

class Main extends PluginBase {
	protected $_getMessage;
	protected $_getVars;

	protected $format;
	protected $formatter;

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

	public function defaultGetMessage($player) {
		$fmt = $this->formatter;
		$txt = $fmt::formatString($this,$this->format,$player);
		return $txt;
	}

	public function onEnable(){
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
		$this->format = $cf["format"];
		if (strpos($this->format,"<?php") !== false
			 || strpos($this->format,"<?=") !== false) {
			$this->formatter = __NAMESPACE__."\\PhpFormat";
		} elseif (strpos($this->format,"{") !== false &&
					 strpos($this->format,"}")) {
			$this->formatter = __NAMESPACE__."\\StrtrFormat";
		} else {
			$this->formatter = __NAMESPACE__."\\FixedFormat";
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

	}
}
