# Tribe Embed

## What it Does

This plugin replaces the video embed with a facade and fetches additional image sizes which are more responsive. This all helps with the initial loading time of pages using the `core/embed`. It also replaces the default youtube URL with a no-cookie alternative.

## How it Works

This plugin filters the `core/embed` block code to make a few improvements:

1. Replaces the video embed with a facade, in this case a placeholder image
2. Fetches multiple image sizes from the video provider and adds `srcset` and `sizes` attributes to the new image element
3. Adds `width` and `height` attributes to the image element

## Limitations

Currently, only the providers listed below are supported. Even when one of these is used, the URL provided must match a specific format in order to be caught by our filters.

* YouTube
* Vimeo
* Dailymotion

## Local Development

### Getting Started

This repo is setup to run either with lando by symlinking the `dev/public/wp-content/plugins/tribe-embed` folder with the project root. To get started make sure you have lando installed and run lando start.  You should be able to reach the site at [tribe-embed.lndo.site/](https://tribe-embed.lndo.site/wp-admin) and the login username is `admin` and password is `password`. 

If you need to rebuild the lando environment you will need to delete the `./dev/public` folder. **Do not use `rm -rf ./dev/public`.** The volume link will delete the root project as well.  If your `rm` command supports it, you can use the `-x` option to not cross mount points.

### Building Plugin

This repo is setup to use the [WP CLI dist-archive](https://developer.wordpress.org/cli/commands/dist-archive/) command.  To build the zip file for the make sure you have the dist-archive command package installed and run `wp dist-archive .` form the root folder. The zip file will be created one folder back form the root folder.


## Hooks (filters & actions)

These are the **public** extension points intended for themes/plugins to customize behavior. Names and arguments are considered part of the API.

### Filters

#### `tribe-embeds_video_provider`
Choose/override the Provider instance for a given embed URL.

- **Signature:** `apply_filters( 'tribe-embeds_video_provider', $provider, $video_url_data, $block )`
- **Args:**
    - `$provider` — default or previously resolved provider instance (or `null`)
    - `$video_url_data` — result of `parse_url()` for the video URL
    - `$block` — full Gutenberg block array (name, attributes, innerBlocks, etc.)
- **Return:** A `Provider` instance or `null` to skip.
- **Example:**
```php
add_filter( 'tribe-embeds_video_provider', function ( $provider, $video_url_data, $block ) {
  // Force our custom provider for a specific host or path
  if ( isset( $video_url_data['host'] ) && $video_url_data['host'] === 'videos.example.com' ) {
      return new \Tribe\Tribe_Embed\Providers\Example_Provider( $video_url_data );
  }
  return $provider;
}, 10, 3 );
```

#### `tribe-embeds_allowed_provider_hosts`

Expand or restrict the whitelist of hostnames that can be handled by built-in or custom providers.

- **Signature:** `apply_filters( 'tribe-embeds_allowed_provider_hosts', $allowed_hosts, $host )`
- **Args:**
  - `$allowed_hosts` — array of allowed host strings (e.g. ['youtube.com', 'youtu.be', 'vimeo.com', 'dailymotion.com'])
  - `$host` — the currently detected host
- **Return:** Modified array of allowed hosts.
- **Example:**
```php
add_filter( 'tribe-embeds_allowed_provider_hosts', function ( array $hosts ) {
    $hosts[] = 'videos.example.com';
    return $hosts;
}, 10 );
```
