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
 - jQuery 2.x
 - Spotify Web API 
 
# Cache / mimed load|bandwidth balance
 - Theory:
    - what happened if the API server cannot provide all the requests? 
    - What about the same requests? Should we trigger all the time the API and generate bandwidth? 
 - Solution: mimed cache
    - Requests results stored in mysql database
    - Every time the request firstly checked in the local database
 - More optimal solution: 
    - RDS / Memcache / NoSQL db usage (redis, firebase or mongo)
