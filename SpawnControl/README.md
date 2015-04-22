
SpawnControl
=======

* Summary: Better control of how players spawn
* Dependency Plugins: n/a
* PocketMine-MP version: 1.4 - API 1.10.0
* DependencyPlugins: -
* OptionalPlugins: -
* Categories: Admin Tools
* Plugin Access: Commands
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/SpawnControl)

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
* `spawnarmor`: defines the list of armor that players will spawn with.
* `spawnitems`: lists the `item_id`:`damage`:`count` for initial items that
  will be placed in the players inventory at spawn time.

### Permission Nodes:

* spawncontrol.spawnarmor.receive: allows player to receive armor when spawning
* spawncontrol.spawnitems.receive: allows player to receive items when spawning

Todo
----

* AlwaysSpawn: off,false|world|home|default
  * home will dispatch /home.
* Keep Items when dieing or respawning

Changes
-------
* 1.0.0 : First public release

Copyright
---------

    SpawnControl
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
