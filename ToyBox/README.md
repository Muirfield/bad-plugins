<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/icon-toy-box.png" style="width:64px;height:64px" width="64" height="64"/>

ToyBox
======

* Summary: A box full of fun toys and tools
* Dependency Plugins: N/A
* PocketMine-MP version: 1.4 - API 1.10.0
* DependencyPlugins: -
* OptionalPlugins: N/A
* Categories: Fun
* Plugin Access: Blocks, Commands
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/ToyBox)

Overview
--------

Provide additional items with special functionality to PocketMine.

* TreeCapitator - axe that destroys trees quickly
* CompassTP - Teleporting compass
* Trampoline - Jump and down blocks

Documentation
-------------


### Commands

* *treecapitator* _[on|off]_  
  If nothing is provider it will show you if TreeCapitator is active
  or not.  Use *on* to enable, *off* to disable.

### Configuration

    modules:
      treecapitator: true
      compasstp: true
      trampoline: true
    treecapitator:
	ItemIDs:
	- 258
	- 271
	- 275
	- 279
	- 286
	need-item: true
	break-leaves: true
	item-wear: 1
	broadcast-use: true
	creative: true
    trampoline:
      blocks: [ 19 ]

* modules
  * Allows you to enable/disable specific toys
* treecapitator - configuration for the treecapitator module.
  - ItemIDs: list of treecapitator tools (defaults to axes)
  - need-item: will require the use of the Items in ItemIDs.
  - break-leaves: destroy tree trunk and leaves.
  - item-wear: how much wear and tear this causes to your tool.
  - broadcast-use: Tell everybody that you are using treecapitator.
  - creative: Enable treecapitator in creative.
* trampoline - configuration for the trampoline module.
  - blocks: List of blocks that well bounce you up/down.

### Permission Nodes:

* toybox.treecapitator: Allow treecapitator
* toybox.compasstp: Allow treecapitator

Todo
----

* Configure compassTP to other item-ids
* MagicClock - invisibility (enable by hitting air, disable by changing item)
        foreach($this->m->getServer()->getOnlinePlayers() as $online){
            $online->hidePlayer($p);
        }
        foreach($this->getOwner()->getServer()->getOnlinePlayers() as $online){
            $online->showPlayer($this->p);
* MagicTorch - light
   (If eyelevel is AIR)
				$pk = new UpdateBlockPacket();
				$pk->x = $pos->x;
				$pk->y = $pos->y;
				$pk->z = $pos->z;
				$pk->block = $block->getId();
				$pk->meta = $block->getDamage();

				Server::broadcastPacket(<level>->getUsingChunk($pos->x >> 4, $pos->z >> 4), $pk);



* PowerPickAxe - instant break block
* Magic Carpet - ?
* Ball - ??

Changes
-------

* 1.0.0 : First submission

Copyright
---------

    ToyBox
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
