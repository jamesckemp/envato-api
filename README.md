# Envato API Wrapper for WordPress

Modified version of the Envato API included in the new Envato Update script. Uses API v3, which means you can fetch some quality item data, like WordPress theme/plugin meta.

Currently you can only get an items details, but should be easy enough to add more methods. See the example.php for more info on how to use it.

Uses WordPress transients to cache the API results.

## Personal Token

Head over to https://build.envato.com/ to get yourself a personal token.

## Methods

`item_details( $id )` Get full item details

`item_prices( $id )` Get all prices for an item
