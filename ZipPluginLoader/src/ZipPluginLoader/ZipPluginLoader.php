<?php
namespace ZipPluginLoader;
use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\plugin\PluginEnableEvent;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginDescription;
use pocketmine\plugin\PluginLoader;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class ZipPluginLoader implements PluginLoader{

	/** @var Server */
	private $server;

	/**
	 * @param Server $server
	 */
	public function __construct(Server $server){
		$this->server = $server;
	}

	private function findBasePath($file) {
		$za = new \ZipArchive();
		if($za->open($file) !== true) return null;
		// Look for plugin data...
		$basepath = null;

		for ($i=0;$i < $za->numFiles;$i++) {
			$st = $za->statIndex($i);
			if (!isset($st["name"])) continue;
			if (basename($st["name"]) == "plugin.yml") {
				$basepath = dirname($st["name"]);
				break;
			}
		}
		$za->close();
		unset($za);
		if ($basepath === null) return $basepath;
		return "myzip://".$file."#".($basepath == "." ? "" : $basepath.DIRECTORY_SEPARATOR);
	}


	/**
	 * Loads the plugin contained in $file
	 *
	 * @param string $file
	 *
	 * @return Plugin
	 */
	public function loadPlugin($file){
		$basepath = $this->findBasePath($file);
		if ($basepath === null) {
			$this->server->getLogger()->error(TextFormat::RED."Unable to load zip $file");
			$this->server->getLogger()->error(TextFormat::RED."plugin.yml not found");
		}
		$descr = $this->getPluginDescription($file);
		if (!($descr instanceof PluginDescription)) {
			$this->server->getLogger()->error(TextFormat::RED."Unable to load zip $file");
			$this->server->getLogger()->error(TextFormat::RED."unable to read plugin.yml");
			return null;
		}
		$this->server->getLogger()->info(TextFormat::AQUA."Loading zip plugin " . $descr->getFullName());
		$dataFolder = dirname($file) . DIRECTORY_SEPARATOR . $descr->getName();
		if(file_exists($dataFolder) and !is_dir($dataFolder)){
			trigger_error("Projected dataFolder '" . $dataFolder . "' for " . $descr->getName() . " exists and is not a directory", E_USER_WARNING);

			return null;
		}
		$className = $descr->getMain();
		$this->server->getLoader()->addPath($basepath . "src");
		if(!class_exists($className, true)){
			trigger_error("Couldn't load zip plugin " . $descr->getName() . ": main class not found", E_USER_WARNING);
			return null;
		}
		$plugin = new $className();
		$this->initPlugin($plugin, $descr, $dataFolder, $file);

		return $plugin;
	}

	/**
	 * Gets the PluginDescription from the file
	 *
	 * @param string $file
	 *
	 * @return PluginDescription
	 */
	public function getPluginDescription($file){
		$basepath = $this->findBasePath($file);
		if ($basepath === null) return null;
		$yaml = @file_get_contents($basepath . "plugin.yml");
		if($yaml == "") return null;
		return new PluginDescription($yaml);
	}

	/**
	 * Returns the filename patterns that this loader accepts
	 *
	 * @return array
	 */
	public function getPluginFilters(){
		return "/\\.zip$/i";
	}

	/**
	 * @param PluginBase        $plugin
	 * @param PluginDescription $description
	 * @param string            $dataFolder
	 * @param string            $file
	 */
	private function initPlugin(PluginBase $plugin, PluginDescription $description, $dataFolder, $file){
		$plugin->init($this, $this->server, $description, $dataFolder, $file);
		$plugin->onLoad();
	}

	/**
	 * @param Plugin $plugin
	 */
	public function enablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and !$plugin->isEnabled()){
			$this->server->getLogger()->info("Enabling " . $plugin->getDescription()->getFullName());

			$plugin->setEnabled(true);

			Server::getInstance()->getPluginManager()->callEvent(new PluginEnableEvent($plugin));
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function disablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and $plugin->isEnabled()){
			$this->server->getLogger()->info("Disabling " . $plugin->getDescription()->getFullName());

			Server::getInstance()->getPluginManager()->callEvent(new PluginDisableEvent($plugin));

			$plugin->setEnabled(false);
		}
	}
}
