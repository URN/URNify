# URNify

This plugin pimps out WordPress to support radio-like features including:
- Support for Shows and Podcasts - shows/podcasts can be created (custom taxonomies) and users can be added to them
-  Committee membership - users can be added to committee and given a role

## API Keys
In order to keep the key for `CurrentSongEndpoint.php` private, it's set inside `wp-config.php`:
```php
define('API_UPDATE_KEY', '<key here>');
```

On the control server (http://int.urn1350.net), new song information is submitted to the http://urn1350.net/api/current_song inside `/var/www/stnmgr/studio/submit_song.php`

## Screenshots
![Shows](http://i.imgur.com/NMnPJGV.png)
![Podcasts](http://i.imgur.com/0NdQt6q.png)
![Use](http://i.imgur.com/WiH2zou.png)
