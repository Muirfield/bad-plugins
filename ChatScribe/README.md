<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/ChatScribe-icon.png" style="width:64px;height:64px" width="64" height="64"/>

ChatScribe
==========

* Summary: Logs chat and commands to file
* Dependency Plugins: n/a
* PocketMine-MP version: 1.4 - API 1.10.0
* DependencyPlugins: -
* OptionalPlugins: -
* Categories: Admin
* Plugin Access: -
* WebSite: [github](https://github.com/alejandroliu/bad-plugins/tree/master/ChatScribe)

Overview
--------

Let's you log all commands and chat's to files

Usage:

* /log [on|off]
  * with no arguments shows logging status
  * on : enables logging
  * off : disables logging

To ensure user's privacy, there is a set of regular expressions that
will remove passwords before logging the line.  You can add additional
regular expressions if needed.

Also, for certain users a "chatscribe.privacy" permission is provided.
Users with that permission will not be logged.


### Configuration

* _log_: Either _server_ or _file_.
* _dest_: Log destination
  * If _log_ is _server_ can be one of:  
    emergency, alert, critical, error, warning, notice, info, debug
  * Otherwise it is a file name.
* _default_: If _true_ will start logging when the plugin is enabled.
  Otherwise you need to activate by command.
* _listener_: When to log the line.  Can be:
  * _early_ : Will log at the beginning of the
    PlayerCommandPreprocessEvent or
  * _late_ : Will log at the end of the PlayerCommandPreprocessEvent.
* privacy: Additonal regular expressions and their replacement strings
  to add to clean-up privacy concerns.

### Permissions

* chatscribe.cmd: Enable logging
* chatscribe.privacy: No logging

Changes
-------

* 1.0.1:
  * Fixed leak
  * Hard-coded some rules to avoid logging SimpleAuth passwords
* 1.0.0: First release

Copyright
---------

    ChatScribe
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
