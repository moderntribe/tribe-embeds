# Providers & credentials

## Supported providers

| Provider | Accepted URLs | Credentials |
|---|---|---|
| YouTube | `youtube.com/watch?v=ID`, `youtube.com/shorts/ID`, `youtu.be/ID` | none |
| Vimeo | `vimeo.com/ID`, `vimeo.com/ID/hash`, `vimeo.com/channels/name/ID`, `player.vimeo.com/video/ID` | access token |
| Dailymotion | `dailymotion.com/video/ID`, `dai.ly/ID` | none |
| Wistia | `account.wistia.com/medias/ID` | API key |

Video IDs are validated per provider (for example YouTube IDs are 11 characters of `A-Z a-z 0-9 _ -`). A URL with an invalid ID is not turned into a facade.

A host rule matches the host itself and its subdomains (`wistia.com` also accepts `account.wistia.com`, `youtube.com` also accepts `m.youtube.com`). Add hosts or providers with the [hooks](hooks.md#provider-and-host-filters).

## Credentials

### Vimeo

Vimeo thumbnails need an access token: [how to generate a personal access token](https://help.vimeo.com/hc/en-us/articles/12427789081745-How-to-generate-a-personal-access-token).

### Wistia

Wistia thumbnails need an API key from your Wistia account.

### Where to store them

Either:

- **Settings → Tribe Embeds** in the admin (stored in the database), or
- constants in `wp-config.php`, which take precedence and keep secrets out of the database:

```php
define( 'VIMEO_ACCESS_TOKEN', '…' );
define( 'WISTIA_API_KEY', '…' );
```

Without a token, Vimeo and Wistia URLs are left as the normal embed.

## Thumbnail caching

Thumbnail data is cached in transients (`tribe-embed_<provider>_<video id>`) for one day. A failed lookup is cached for five minutes so a broken API is not called on every page view. All remote requests have a 5 second timeout. Deleting the plugin removes the stored tokens and these transients.

## Adding a provider

Extend `Tribe\Tribe_Embed\Providers\Provider` and register the class with the [`tribe_embed_provider_classes`](hooks.md#provider-and-host-filters) filter. A custom provider is created with the parsed URL only (`new CustomProvider( $video_url_data )`), so it does its own HTTP requests and caching.

```php
use Tribe\Tribe_Embed\Providers\Provider;

final class Example_Provider extends Provider {

    public const SLUG          = 'example';
    public const ALLOWED_HOSTS = [ 'videos.example.com' ];

    // Thumbnails keyed by size: [ 'url' => string, 'width' => int, 'height' => int ].
    public function get_thumbnail_data(): array {
        return [
            'large' => [
                'url'    => 'https://videos.example.com/thumbs/' . $this->video_id . '.jpg',
                'width'  => 1280,
                'height' => 720,
            ],
        ];
    }

    // Called with the parsed URL in $this->video_url (host, path, query, …). Return '' for an unsupported URL.
    protected function set_video_id(): string {
        return $this->valid_video_id( trim( $this->video_url['path'] ?? '', '/' ), '/^[a-z0-9]+$/' );
    }
}

add_filter( 'tribe_embed_provider_classes', function ( array $classes ) {
    $classes[] = Example_Provider::class;
    return $classes;
} );
```

- `ALLOWED_HOSTS`: hosts the provider accepts (subdomains included). Adjust them with the `tribe_embed_allowed_provider_hosts_<slug>` filter, where `<slug>` is `SLUG`.
- `valid_video_id( $id, $pattern )` returns the ID only if it matches the pattern, so odd input never reaches API URLs or markup.
- Override `uses_inline_embed()` to return `true` if the original embed should be rendered inside the facade instead of in a `<template>`.

