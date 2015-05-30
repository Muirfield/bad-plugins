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

### Configuration

    ---
    version: 1.0.0
    settings:
      # log: Either server or file
      log: server
      # dest: If file, this is a filename, otherwise
      #    emergency|alert|critical|error|warning|notice|info|debug
      dest: info
      # default: If true, will start logging by default
      default: false
      # listener: Set to early or late
      listener: early
    # privacy: regular expressions and replacements used for ensuring privacy
    privacy:
      /\/login\s*.*/: /login **CENSORED**
      /\/register\s*.*/: /register **CENSORED**
    ...


### Permissions

* chatscribe.cmd: Enable logging
* chatscribe.privacy: No logging

Changes
-------

* 1.0.1:
  * Fixed leak
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
