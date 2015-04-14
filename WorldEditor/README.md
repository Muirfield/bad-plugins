# pocketmine-WorldEditor <em>for PocketMine-MP (New API)</em>

WorldEditor Plugin is a port of the WorldEdit, done for [PocketMine-MP](https://github.com/shoghicp/PocketMine-MP)


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
| __//replace__ | `<from-block> <to-block>` | Replace all blocks of the specified block with another block(s) inside the region. |
| __//copy__ | | Copies the currently selected region. Be aware that it stores your position relative to the selection when copying. |
| __//cut__ | | Cuts the currently selected region. |
| __//paste__ | | Pastes the clipboard. |
| __//sphere__ | `<blocks> <radius>` | Create a sphere. |
| __//hsphere__ | `<blocks> <radius>` | Create a hollow sphere. |
| __//desel__ | | Deselects the current selection. |
| __/toggleeditwand__ | | Toggles the edit wand selection mode, allowing you to use the edit wand item normally. |
