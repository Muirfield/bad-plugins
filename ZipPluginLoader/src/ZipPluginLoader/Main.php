<?php
namespace ZipPluginLoader;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginLoadOrder;

class Main extends PluginBase {
	public function onEnable(){
		if (!in_array("myzip",stream_get_wrappers())) {
			if (!stream_wrapper_register("myzip",__NAMESPACE__."\\MyZipStream")) {
				$this->getLogger()->error("Unable to register Zip wrapper");
				throw new \RuntimeException("Runtime checks failed");
				return;
			}
		}
		$this->getServer()->getPluginManager()->registerInterface("ZipPluginLoader\\ZipPluginLoader");
		$this->getServer()->getPluginManager()->loadPlugins($this->getServer()->getPluginPath(), ["ZipPluginLoader\\ZipPluginLoader"]);
		$this->getServer()->enablePlugins(PluginLoadOrder::STARTUP);
	}
	public function onDisable() {
		if (in_array("myzip",stream_get_wrappers())) {
			stream_wrapper_unregister("myzip");
		}
	}
}
