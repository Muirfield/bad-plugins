<img src="https://raw.githubusercontent.com/alejandroliu/bad-plugins/master/Media/AutoHeal-icon.png" style="width:64px;height:64px" width="64" height="64"/>
# AutoHeal

Fast coded plugin that heals players on a timely basis.

Useful for servers with VIPs.

## Configuration

In config.yml:

	ranks:
	  vip1: [ 40, 1]
	  vip2: [ 80, 2]
	  vip3: [ 800, 1]

Where ranks defines the different VIP levels. In there, you can define
several VIP groups with different healing rates. The two numbers are:


1. Ticks between heals. 20 ticks is one second. The higher the number,
   the slower the healing rate.
2. The amount of 1/2 hearts that heal every time. The higher the
   number the faster the healing rate.

## Permission nodes

The following permissions are defined:

    autoheal : Players with this permission will auto heal.
    autoheal.<rank_name> : Players with this permission will auto heal at the given rate in config.yml.

You need a permissions plugin for this to work properly!
