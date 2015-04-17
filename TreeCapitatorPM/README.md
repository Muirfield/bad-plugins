<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/treecapitator.png" style="width:64px;height:64px" width="64" height="64"/>

TreeCapitatorPM
===============

* Summary: TreeCapitator plugin for PocketMine
* Dependency Plugins: N/A
* PocketMine-MP version: 1.4 - API 1.10.0
* DependencyPlugins: -
* OptionalPlugins: N/A
* Categories: Fun
* Plugin Access: Blocks, Commands
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/TeeCapitatorPM)

Overview
--------

A TreeCapitator plugin done for PocketMine.

Basic Usage:

* treecapitator [on|off]

You must be holding an axe.


Documentation
-------------

A TreeCapitator plugin for PocketMine.

### Commands

* *treecapitator* _[on|off]_  
  If nothing is provider it will show you if TreeCapitator is active
  or not.  Use *on* to enable, *off* to disable.

### Configuration

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

* *ItemIDs*: Default to all axes.  List of items that need to be holding
  for TreeCapitator to work.
* *need-item*: If enabled, an item is needed.
* *break-leaves*: Will break leaves, otherwise only the tree trunk is
   cut.
* *item-wear*: How much wear and tear the item received.
* *broadcast-use*: Advertise the fact that TreeCapitator was used.


### Permission Nodes:

* treecapitator.use: Allow treecapitator

Changes
-------

* 1.0.0 : First submission

Copyright
---------

    TreeCapitatorPM
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
