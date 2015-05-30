<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/hud.jpg" style="width:64px;height:64px" width="64" height="64"/>

BasicHUD
========

* Summary: A configurable heads up display
* Dependency Plugins: n/a
* PocketMine-MP version: 1.5 - API 1.12.0
* DependencyPlugins: -
* OptionalPlugins: -
* Categories: Informational
* Plugin Access: Other Plugins
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/BasicHUD)

Overview
--------

This plugin lets you configure a basic Head-Up Display (HUD) for
players.

### Configuration

In the `config.yml` you can configure the following:

* ticks: how quickly to show the pop-up.  Lower the number updates
  faster but introduces lags.
* format: Text to display

The displayed text can be:

* A fixed string.
* A string containing {variables}
* A string containing <?php and <?=.  This allows you to embed
  arbitrary PHP code in the format.  This is similar to how web pages
  are done.

The default variables are:

* {player}
* {world}
* {x}
* {y}
* {z}
* {yaw}
* {pitch}
* {bearing}
* {BLACK}
* {DARK_BLUE}
* {DARK_GREEN}
* {DARK_AQUA}
* {DARK_RED}
* {DARK_PURPLE}
* {GOLD}
* {GRAY}
* {DARK_GRAY}
* {BLUE}
* {GREEN}
* {AQUA}
* {RED}
* {LIGHT_PURPLE}
* {YELLOW}
* {WHITE}
* {OBFUSCATED}
* {BOLD}
* {STRIKETHROUGH}
* {UNDERLINE}
* {ITALIC}
* {RESET}

You can add more variables by creating a `vars.php` in the plugin
directory.  For your convenience, there is `vars-example.php`
available that you can use as a starting point.  Copy this file to
`vars.php`.

The example `vars.php` will create a `{score}` and `{money}` variable
if you have the relevant plugins.

By default, if you have `SimpleAuth` installed, the HUD will be
inactive until you log-in.  If you are using something other than
`SimpleAuth` you can copy the `message-example.php` to `message.php`
and do whatever checks you need to do.

Changes
-------

* 1.0.0: First release

Copyright
---------

    BasicHUD
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
