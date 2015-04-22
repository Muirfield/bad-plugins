
SpawnMgr
=======

* Summary: Better control of how players spawn
* Dependency Plugins: n/a
* PocketMine-MP version: 1.4 - API 1.10.0
* DependencyPlugins: -
* OptionalPlugins: -
* Categories: Admin Tools
* Plugin Access: Commands
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/SpawnMgr)

Overview
--------

Let's you control how your players spawn on your server.

Documentation
-------------

Control spawn settings in your server.

### Configuration

Configuration is through the `config.yml` file:

	settings:
	  tnt: true
	  pvp: true
	  spawn-mode: default
	  keep-inventory: false
	  home-cmd: /home
	spawnarmor:
	  head: '-'
	  body: chainmail
	  legs: leather
	  boots: leather
	spawnitems:
	- "272:0:1"
	- "17:0:16"
	- "364:0:5"

* `settings`: Basic spawn settings
   * `tnt` : if *true* allows tnt explosions in spawn area.
   * `pvp` : if *true* allows PvP in spawn area.
   * `spawn-mode`: can be one of the following:
     * *default* : when joining will start at the last location.
     * *world* : when joining will always start at the last world
       spawn point.
     * *always* : when joining will always start at the default world
       spawn point.
     * *home* : when joining will start at your home location.
  * `keep-inventory` : players get to keep their stuff when they die.
  * `home-cmd` : Configure the command to go *home*.  This is for the
    *home* *spawn-mode*.  This is executed in the player's context so
    make sure all players have permissions to execute this command.
* `spawnarmor`: defines the list of armor that players will spawn with.
* `spawnitems`: lists the `item_id`:`damage`:`count` for initial items that
  will be placed in the players inventory at spawn time.

**NOTE**: The *home* *spawn-mode* requires you to have a */home*
plugin that provides with a `/home` command.  This command is executed
when the player joins.


### Permission Nodes:

* spawncontrol.spawnarmor.receive: allows player to receive armor when spawning
* spawncontrol.spawnitems.receive: allows player to receive items when spawning
* spawncontrol.keepinv: allow player to keep inventory
* spawncontrol.spawnmode: player will follow spawn-control setting

Changes
-------
* 1.0.0 : First public release

Copyright
---------

    SpawnMgr
    Copyright (C) 2015 Alejandro Liu  
    All Rights Reserved.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

* * *
