# Client Interface plugin for Mibew by Wurrd

This plugin provides an interface for third party apps to communicate with a Mibew chat server.
The plugin uses the [Wurrd Auth API plugin](https://github.com/alberto234/wurrd-auth-api-plugin) to provide authentication to the chat server.

## Wurrd App

Wurrd for Mibew is an app that provides operators the ability to chat with website visitors from their mobile devices. This plugin exposes an API that the Wurrd app uses to communicate with a Mibew 2.x chat server. The app can be downloaded from [Google Play](https://play.google.com/store/apps/details?id=com.scalior.wurrd) and from the App Store (coming soon). 


## Installation

1. This plugin depends on the Wurrd Auth API plugin. Follow the directions to install this plugin from [here](https://github.com/alberto234/wurrd-auth-api-plugin).

2. Get the built archive for this plugin from [here](http://wurrd.scalior.com/get-it-now).

3. Untar/unzip the plugin's archive.

4. Copy the entire directory structure for the plugins into the `<Mibew root>/plugins`  folder.

5. Navigate to "`<Mibew Base URL>`/operator/plugin" page and enable the plugin.


## Plugin's configurations

There are currently no configurations for this plugin

## Build from sources

There are several actions one should do before use the latest version of the plugin from the repository:

1. Obtain a copy of the repository using `git clone`, download button, or another way.
2. Install [node.js](http://nodejs.org/) and [npm](https://www.npmjs.org/).
3. Install [Gulp](http://gulpjs.com/).
4. Install npm dependencies using `npm install`.
5. Run Gulp to build the sources using `gulp default`.

Finally `.tar.gz` and `.zip` archives of the ready-to-use Plugin will be available in `release` directory.


## License

[Apache License 2.0](http://www.apache.org/licenses/LICENSE-2.0.html)