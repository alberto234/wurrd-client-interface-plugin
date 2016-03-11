# Client Interface plugin for Mibew by Wurrd

This plugin provides an interface for third party apps to communicate with a Mibew chat server.
The plugin uses the [Wurrd Auth API plugin](https://github.com/alberto234/wurrd-auth-api-plugin) to provide authentication to the chat server.

## Wurrd App

Wurrd for Mibew is an app that provides operators the ability to chat with website visitors from their mobile devices. This plugin exposes an API that the Wurrd app uses to communicate with a Mibew 2.x chat server. The app can be downloaded from [Google Play](https://play.google.com/store/apps/details?id=com.scalior.wurrd) and from the [App Store](https://itunes.apple.com/us/app/wurrd/id1017128684?mt=8). 


## Installation
Full install and update instructions with pictures can be found on the [Wurrd website](http://wurrdapp.com/how-to-install-a-plugin-in-mibew/)

1. This plugin depends on the Wurrd Auth API plugin. Follow the directions to install this plugin from [here](https://github.com/alberto234/wurrd-auth-api-plugin).
1. Get the built archive for this plugin from [here](http://wurrdapp.com/get-it-now).
1. Untar/unzip the plugin's archive.
1. Copy the entire directory structure for the plugins into the `<Mibew root>/plugins`  folder.
1. Navigate to "`<Mibew Base URL>`/operator/plugin" page and enable the plugin.
1. Navigate to `<Mibew root>/cache` and delete the stash folder. There is a [bug](https://github.com/Mibew/mibew/issues/143) in Mibew core.

## Updating

1. Get the built archive for this plugin from [here](http://wurrdapp.com/get-it-now).
1. Untar/unzip the plugin's archive.
1. Copy the entire directory structure for the plugins into the `<Mibew root>/plugins`  folder.
1. Navigate to "`<Mibew Base URL>`/operator/plugin" page and update the plugin.
1. Navigate to `<Mibew root>/cache` and delete the stash folder. There is a [bug](https://github.com/Mibew/mibew/issues/143) in Mibew core.

## Plugin's configurations

The plugin can be configured with values in "`<Mibew root>`/configs/config.yml" file. Example:
  ```yaml
  plugins:
      "Wurrd:ClientInterface": # Plugin's configurations are described below
          use_http_post: true
  ```
Note: The configuration hierarchy is built through by parsing the indentation of the config.yml file, so the number of spaces before each line matters. See this [issue](https://github.com/alberto234/wurrd-auth-api-plugin/issues/2) for symptoms of a bad config.yml file.

### config.use_http_post

Type: `Boolean`

This is needed only if you attempt to login from your device and you receive error 501 or null. This is caused by this issue where some hosting providers block or redirect PUT and DELETE requests. Add the section above to your config.yml file and set this to true if you experience an error logging in. 


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
