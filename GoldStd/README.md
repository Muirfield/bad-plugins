GoldStd
=======

* Summary: A different economy plugin
* Dependency Plugins: N/A
* PocketMine-MP version: 1.4 - API 1.10.0
* DependencyPlugins: -
* OptionalPlugins: N/A
* Categories: Economy
* Plugin Access: 
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/GoldStd)

Overview
--------

Implements an economy plugin based on Gold Ingots (by default) as the
currency.

Basic Usage:

* pay $$
* balance

To pay people you tap on them while holding a gold ingot.

Documentation
-------------

GoldStd implements an economy plugin based on Gold Ingots (by default)
as the currency.  This allows to add game mechanics to the game
without artificial commands or other artificial constructs. 

You can then pay people without using the chat console.  Also, you may
lose all your money if you get killed.  Players can stash their gold
on Chests, but they would need to guard them (just like in real life),
etc.  You can see how much money you have directly in the inventory
window, etc.

### Commands

The chat console commands are there for convenience but are not needed
for regular gameplay:

* pay $$  
  By default when you tap on another player, only 1 gold ingot get
  transferred.  This command can be used to facilitate larger
  transactions.  If you use this command the next tap will transfer
  the desired amount in one go.
* balance  
  If you are rich enough, your money will be in multiple stacks.  This
  commands will add the stacks for you.

### API

* API
  - getMoney
  - setMoney
  - grantMoney

### Configuration

These can be configured from `config.yml`:

    settings:
	currency: 266
    defaults:
	payment: 1

### Notes/Issues

* Creative players do not take part in the economy as they have
  infinite resources.

### Permission Nodes:

* goldstd.cmd.pay:
  * default: true
  * description: "Access to pay command"
* goldstd.cmd.balance:
  * default: true
  * description: "Show your current balance"

Changes
-------

* 1.0.0 : First submission

Copyright
---------

    GoldStd
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
