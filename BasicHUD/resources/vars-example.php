/*
 * This code is used to create new format variables
 *
 * The following variables are available:
 *
 * $plugin - the HUD plugin
 * $vars - array containing format variables
 * $player - current player
 */
$pm = $plugin->getServer()->getPluginManager();

if (($kr = $pm->getPlugin("KillRate")) !== null) {
	if (version_compare($kr->getDescription()->getVersion(),"1.1") >= 0) {
		$vars["{score}"] = $kr->getScore($player);
	}
}
if (($mm = $pm->getPlugin("PocketMoney")) !== null) {
	$vars["{money}"] = $mm->getMoney($player);
} elseif (($mm = $pm->getPlugin("MassiveEconomy")) !== null) {
	$vars["{money}"] = $mm->getMoney($player);
} elseif (($mm = $pm->getPlugin("EconomyAPI")) !== null) {
	$vars["{money}"] = $mm->mymoney($player);
} elseif (($mm = $pm->getPlugin("GoldStd")) !== null) {
	$vars["{money}"] = $mm->getMoney($player->getName());
}
