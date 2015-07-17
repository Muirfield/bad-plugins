<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/Mobsters-icon.png" style="width:64px;height:64px" width="64" height="64"/>

Mobsters
=======

* Summary: Spawn mobs
* Dependency Plugins: n/a
* PocketMine-MP version: 1.4 - API 1.6.0
* DependencyPlugins: -
* OptionalPlugins: -
* Categories: Fun, Mechanics
* Plugin Access: Entities, Items/Blocks
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/)

Overview
--------

Commands:

- /mobster [spawn] &lt;id or name&gt; &lt;x,y,z[,yaw,pitch][:world]&gt;

Will spawn a new mob:

- id or name:  Use the network id number (e.g. 32 for zombies) or saveId
  (e.g. Zombie, Chicken, Pig, etc).
- x,y,z : position of new mob
- yaw,pitch : optional, direction mob will be facing.
- world : name of the level to place the mob.

Signs:

You can create a mob spawner sign.  The following text needs to be:

- [spawner]
- mob name or id
- radius,max-number,freq,odds
- time-time or time|time or day or night

Where the following applies:
- The mob name or id is the same as in the spawn command.
- radius : is the area around the sign where mobs will spawn.
- max-number : max number of mobs in the level.  If there are this many mobs
  on that level, the spawner will stop spawning mobs.
- freq : how often mobs are generated. The larger the number the less often
  mobs will be spawned.
- odds : random chance that mobs will be generated.  A larger number means
  less chances for this to be generated
- time-time : Time restrictions.   Mobs will only be generated within these
  times.  (Blank times are allowed)
- time|time : Time restrictions.  Mobswill only be generated outside these
  times.  (Blank times are allowed)
- day : only spawn during day time
- night : only spawn during night time


Documentation
-------------


Changes
-------

* 0.6.0: Spawning...
* 0.5.0 : Maintenance
  - fixed knockback
  - Added spawn command
* 0.4.0 : Updated for PM1.5 (API: 1.12.0)
* 0.3.0 : Fire up Motion events
* 0.2.0 : Bug fixes
* 0.1.0 : First release

Copyright
---------

    Mobsters
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
