<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/floatchat.jpg" style="width:64px;height:64px" width="64" height="64"/>

# FloatChat

* Summary: Float chat messages
* PocketMine-MP version: 1.5 - API 1.12.0
* DependencyPlugins: -
* OptionalPlugins: -
* Categories: Chat, Fun, Mechanics
* Plugin Access: Entities
* WebSite: [github](https://github.com/alejandroliu/pocketmine-plugins/tree/master/LocalChat)

Overview
--------

Make chats to appear in text above the player.
If you want to broadcast a message to all players in the Level use:

	.text

While if you want to broacast a message to all players in the Server
use:

	:text


Documentation
-------------

By prefixing your text with a "." to a message you can *shout* your
message to everybody in the same level.

By prefixing your text with a ":" to a message you can *broadcast*
your message to everybody in the same server.


### Permission Nodes:

* floatchat.brodcast: Allow access to `.` and `:` to broadcast messages.
* floatchat.brodcast.level: Allow access to `.` messages
* floatchat.brodcast.server: Allow access to `:` messages
* floatchat.spy: Users with this permission are always able to hear
  all messages.

### TODO


Changes
-------

* 1.0.0 : First public release

Copyright
---------

    FloatChat
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
