# Tribe Embed

## Getting Started

This repo is setup to run either with lando by symlinking the `dev/public/wp-content/plugins/tribe-embed` folder with the project root. To get started make sure you have lando installed and run lando start.  You should be able to reach the site at [tribe-embed.lndo.site/](https://tribe-embed.lndo.site/wp-admin) and the login username is `admin` and password is `password`. 

If you need to rebuild the lando environment you will need to delete the `./dev/public` folder. **Do not use `rm -rf ./dev/public`.** The volume link will delete the root project as well.  If your `rm` command supports it, you can use the `-x` option to not cross mount points.

## Building Plugin

This repo is setup to use the [WP CLI dist-archive](https://developer.wordpress.org/cli/commands/dist-archive/) command.  To build the zip file for the make sure you have the dist-archive command package installed and run `wp dist-archive .` form the root folder. The zip file will be created one folder back form the root folder.
