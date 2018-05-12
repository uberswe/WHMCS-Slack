# WHMCS-Slack
A WHMCS addon for sending messages to Slack based on WHMCS hooks. Version 3.0 has been updated for WHMCS 7.5.1 and should still be compatible with older releases back to 6.0.0.

![WHMCS Slack - Version 3.0 Adds a feature to test hooks](https://raw.githubusercontent.com/markustenghamn/WHMCS-Slack/master/whmcs_slack.png)

# Upgrading from 2.0 to 3.0

As always, it's good to make a backup just in case something goes wrong.

1.) First write down your token and "post as" name as these will be removed. All hook configurations should be automatically migrated.

2.) I am changing the addon folder and the main php file from `anveto_slack` to `whmcs_slack`. You can remove all old 2.0 files when upgrading.

3.) Copy the folder `whmcs_slack` to `src/modules/addons/`

4.) Go to `Setup->Addon Modules` in WHMCS and activate the addon WHMCS Slack again. The database tables will be migrated from from `anveto_slack` to `whmcs_slack`.

Done, everything should work again.

# Configuration and Installation

[Download the latest release from the releases page](https://github.com/markustenghamn/WHMCS-Slack/releases)

1. In your slack admin console, go to "Your Apps" and create a new app called "WHMCS"
2. Under "Features" select "Bot Users" and create one called @whmcs 
3. Go to "OAuth & Permissions" to obtain your "Bot User OAuth Access Token"
4. In slack, invite your new bot to each channel under which it will be broadcasting info from WHMCS
5. Activate this plugin in WHMCS and under its config enter the botname as whmcs and provide the Access Token
6. In WHMCS go to Addons > WHMCS Slack and add and configure each hook that you wish to integrate with Slack.

# Contributing

This project is open source and I don't charge any money. If you would like to contribute feel free to make a pull request or send an email/open a ticket and ask me about it.

By contributing code to this project you agree that that code falls under the license in the LICENSE file of this project unless otherwise stated in the source code.

See [CONTRIBUTING.md](https://github.com/markustenghamn/WHMCS-Slack/blob/master/CONTRIBUTING.md) for more details.

## Donations

Have I helped in some way? Buy me a cup of coffee by sending to one of my [cryptocurrency wallets here](https://ma.rkus.io/wallets).

## Note about the original addon

This was originally sold by via Anveto but I have decided to make it free and open source the addon. You are free to contribute and use the code as long as you give credit to me and abide by the MIT license.

If you have purchased this script previously and need support please contact me for premium support. If you are using the free version of this script please open an issue.

[Download the latest release from the releases page](https://github.com/markustenghamn/WHMCS-Slack/releases)