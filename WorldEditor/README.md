# WorldEditor *for PocketMine-MP (1.4)*

WorldEditor Plugin is a port of the WorldEdit, done for
[PocketMine-MP](https://github.com/shoghicp/PocketMine-MP).  This was
originally from
[ryuzaki01](https://github.com/ryuzaki01/pocketmine-WorldEditor) but
hacked until it worked for me...

## Installation

- Drop the correct WorldEditor version into the PocketMine's `plugins/` folder.
- Restart the server. The plugin will be loaded


## Commands
| Command | Parameters | Description |
| :---: | :---: | :--- |
| __//limit__ | `<limit>` | Set a maximum number of blocks to change at most for all operations. This only affects yourself. Use this to prevent catastrophic accidents. |
| __//wand__ | | Gives you the "edit wand". Break a block with this tool to select position 1 and touch a block to selection position 2. |
| __//pos1__ | | Set selection position #1 to the block above the one that you are standing on. |
| __//pos2__ | | Set selection position #2 to the block above the one that you are standing on. |
| __//set__ | `<blocks>` | Set all blocks inside the selection region to a specified block(s). |
| __//fill__ | `<blocks>` | Fill the selection region with the specified block(s). |
| __//hregion__ | `<blocks>` | Create a hollow cuboid for selection region with the specified block(s). |
| __//replace__ | `<from-block> <to-block>` | Replace all blocks of the specified block with another block(s) inside the region. |
| __//copy__ | | Copies the currently selected region. Be aware that it stores your position relative to the selection when copying. |
| __//cut__ | | Cuts the currently selected region. |
| __//paste__ | | Pastes the clipboard. |
| __//sphere__ | `<blocks> <radius>` | Create a sphere. |
| __//hsphere__ | `<blocks> <radius>` | Create a hollow sphere. |
| __//desel__ | | Deselects the current selection. |
| __//selection__ | | Shows the current selection. |
| __//toggleeditwand__ | | Toggles the edit wand selection mode, allowing you to use the edit wand item normally. |
| __//save__ | `<file>` | Save the clipboard to a file. |
| __//load__ | `<file>` | Load the clipboard from a file. |

### Permission nodes

* worldeditor:
 * description: "All the permissions to use the World Editor"
* worldeditor.command:
 * description: "Allow the usage of the WorldEditor command"

### TODO

1. rotate clip

Changes
-------

* 1.0.3-0.1 : Hacked Version
  * Works for me now
  * Added new commands: //fill, //hregion, //selection, //save, //load
* 1.0.3 : Original version from
  [ryuzaki01](https://github.com/ryuzaki01/pocketmine-WorldEditor)
