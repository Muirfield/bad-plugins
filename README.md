# bad-plugins

PocketMine Plugins that are not good enough to be hosted in
plugins.pocketmine.net


This git repository contains my plugins that were rejected from the
official pocket mine web site.

## Notes

	specter spawn Playername # The full command to spawn a new dummy
	s s playername # Luckily there is shorthand
	s c playername /spawn # Execute /spawn as player

Give/Get items:

* zombie : spawn_egg:32
* villager : spawn_egg:15


# Todo/Ideas

* Player Interact Peacefully
  - command: attack/interact - defualts to interact
  - if holding a weapon attack (always)
  - if holding compass/clock/food/string/feather/seeds (never)
* Add a Snowball/Egg or something and use it as football..
* RuneGM:
* Adds a GameMaster:
  * Automatically spawned villager or a NPC (Player)
  * If LocalChat is active we use it... otherwise you need to use /rp
    command.
  * If attacked it will retaliate (or kill you...)
  * Implements the RuinPvP casino and shop functionality.
  * Provide rankings and stats...
  * Moves around randomly in his spawn area...
* PMScript:
  {{ something }} the something is a PHP expression.
  Some syntax sugar:
	$[_A-Za-z][_a-zA-Z0-9]*.[_A-Za-z][_A-Za-z0-9]*[^(] ---> converted
  to  $<something>->get<something>()

  @ php ... this is raw PHP code.. prefered altenate syntax.
  else goes to "PM command processor"
  Event handlers... per world.
  Use closures:
      $example = function ($arg) {
        echo($arg);
      }
      $example("hello");
  Gets called onLevelLoad
  registerEvents($obj,$plugin);
  Check what "reload" does, can it unregister event? there is no API
  for it...
	?Loader: disablePlugin($plugin)
	>>>HandlerList::unregisterAll($plugin);
	?removePermissions

# NEW PLUGIN IDEAS:

- plots: place a sign claim a plot.  Place a sign restrict the plot.
- per-level plugins.  Plugins only active on a per level basis
- async-task plugin?
- fork per-level
- creative restrict
  - do not allow PvP in creative, warning or stop
  - if somebody kills in creative, we switch gamemode

# MINI GAMES

## DeathSwap

After the game has started, run away from your opponents.
A random timer runs in the backround, and when it finishes after upto
2 Minutes, 2 players postitions will be swapped. The timer will be
restarted, and the next time it finishes, two new randomly chosen
players will get swapped. Try to be the last person alive and kill
other players without seeing them. It's up to you to find out how to
do that.

# Copyright

    bad-plugins
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
