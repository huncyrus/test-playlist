# test-playlist
Playlist test task


# Requirements:
 - PHP 5.x
 - MySql 4.x
 - Browser, web-kit based (chrome, firefox, opera, safari)


# Used tech:
 - LAMP stack
 - CodeIgniter 3.0.3
 - Bootstrap 3.3.6
 - jQuery
 - jQuery UI
 - Bootstrap datatable
 - Spotify Web API (without token) 
 
 
# Install
 - Copy the files into the host
 - Add database (/doc folde, scheme-playlist.sql file)
 - Setup your configuration:
    - /backend/application/config/database.php ----------- database creds
    - /backend/application/config/config.php ------------- base url
    - /public/js/main.js --------------------------------- url
 
 
# Cache / mimed load|bandwidth balance
 - Theory:
    - what happened if the API server cannot provide all the requests? 
    - What about the same requests? Should we trigger all the time the API and generate bandwidth? 
 - Solution: mimed cache
    - Requests results stored in mysql database
    - Every time the request firstly checked in the local database
 - More optimal solution: 
    - Varnish / OpCache / Zendcache
    - RDS / Memcache / NoSQL db usage (redis, firebase or mongo)
 - Security ideas:
    - Flood control
    - Better CRSF auth between frontend and backend
    - IP check for clients (for possible bots & spammers & blacklisteds)
    
    
# Note
 - Missing features:
    - Authentication
    - Flood control
    - Real cache system (nosql/varnish/memcache/opcache/zendcache)
    - 'Back' function for listing and searching
 - Possible glitch
    - Double click on any button and get 'empty' or 'error' message
 
