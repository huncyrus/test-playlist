// Config for backend
var url = 'http://test.cyrusmagus.hu/playlist/backend',
    token = '';


$( document ).ready(function() {
    console.log( "ready!" );

    // init loading for playlist after get the token
    getToken();

    $('table.sortable').dataTable();


    /**
     * Message helper for differnt statuses
     * @param message the message, can be text or html
     * @param type for laoding, load|1
     * @param place css class name, example: '.results'
     */
    function statusMessage(message, type, place) {
        var classHelper = 'glyphicon glyphicon-info-sign';

        if ('load' == type) {
            classHelper = 'glyphicon glyphicon-refresh glyphicon-refresh-animate';
        }
        $(place).html('<i class="' + classHelper + '"></i> ' + message);
    }


    /**
     * Load Playlist
     */
    function loadList() {
        statusMessage('Loading...', 'load', '.playlistBox');
        var token = $('input#mytoken').val();

        ajaxRequest = $.ajax({
            url: url + "/getPlaylist/",
            type: "post",
            data: 'token=' + token
        });
        ajaxRequest.done(function (response, textStatus, jqXHR){
            statusMessage('<span class="bg-success">Loading...</span>', 'load', '.playlistBox');
            if(!response.error) {
                result = JSON.parse(response);

                if (result) {
                    if (result.error) {
                        $('.playlistBox').html('<div class="error">Empty playlist</div>');
                    } else {
                        $('.playlistBox').html(buildPlaylistTable(result));
                        $('.sortable').DataTable();
                    }
                }
            }
            console.info(response, '>>>>> token: ' + token);
        });
        ajaxRequest.fail(function (response){
            statusMessage('<span class="bg-warning">Error at loading...</span>', '1', '.playlistBox');
            console.error(response);
        });
    }


    /**
     * Get crsf token
     */
    function getToken() {
        $.getJSON(
            url + '/getToken',
            function (result) {
                if (result) {
                    if (result.error) {
                        console.info('Token error...');
                    } else {
                        $('input#mytoken').val(result);
                        token = result;
                        loadList();
                    }
                }
                console.log(result);
            }
        );
    }


    /**
     * Table builder helper method for Artist table row
     * @param artistName
     * @param artistHash
     * @returns {string}
     */
    function buildArtistRow(artistName, artistHash) {
        var buildedRow = '';

        if (artistName && artistHash) {
            buildedRow = '<tr><td>' + artistHash + '</td><td>' + artistName + '</td><td>';
            buildedRow += '<button data-id="' + artistHash + '" class="btn btn-primary btn-xs" ';
            buildedRow += ' name="getArtistTracks">Get tracks</button>';
            buildedRow += '</td></tr>';
        }

        return buildedRow;
    }


    /**
     * Table builder helper method for artist table
     * @param data
     * @returns {*}
     */
    function buildArtistTable(data) {
        if (data) {
            var temp_start = '<table class="sortable table table-striped table-hover table-responsive">' +
                '<thead><tr><th>ID</th><th>Artist Name</th><th>Options</th></tr></thead>' +
                '<tbody>';
            var temp_end = '</tbody></table>';
            var temp = '';

            $.each(data, function (index, val) {
                if (val.name && val.id) {
                    temp += buildArtistRow(val.name, val.id);
                }
            });

            return temp_start + temp + temp_end;
        }

        return '';
    }


    /**
     * Table build helper method fo song/track table row
     * @param songHash
     * @param songTitle
     * @param songId
     * @returns {string}
     */
    function buildTrackRow(songHash, songTitle, songId) {
        var buildedRow = '';

        if (!songId) { songId = 0; }

        if (songTitle && songHash) {
            buildedRow = '<tr><td>' + songHash + '</td><td>' + songTitle + '</td><td>';
            buildedRow += '<button data-id="' + songHash + '" data-info="' + songId + '" class="btn btn-success btn-xs" ';
            buildedRow += ' name="addToPlaylist">+Add to playlist</button>';
            buildedRow += '</td></tr>';
        }

        return buildedRow;
    }


    /**
     * Table build helper method for song/track table
     * @param data the track data object
     * @returns {*}
     */
    function buildTrackTable(data) {
        if (data) {
            var temp_start = '<table class="sortable table table-striped table-hover table-responsive">' +
                '<thead><tr><th>Song ID</th><th>Song Title</th><th>Options</th></tr></thead>' +
                '<tbody>';
            var temp_end = '</tbody></table>';
            var temp = '';

            $.each(data, function (index, val) {
                if (val.name && val.id) {
                    if (!val.myid) {
                        val.myid = 0;
                    }
                    temp += buildTrackRow(val.id, val.name, val.myid);
                }
            });

            return temp_start + temp + temp_end;
        }

        return '';
    }


    /**
     * Table build helper method for playlist table row
     * @param songHash
     * @param songTitle
     * @param songId
     * @returns {string}
     */
    function buildPlaylistRow(songHash, songTitle, songId) {
        var buildedRow = '';

        if (!songId) { songId = 0; }

        if (songTitle && songHash) {
            buildedRow = '<tr><td>' + songHash + '</td><td>' + songTitle + '</td><td>';
            buildedRow += '<button data-id="' + songHash + '" data-info="' + songId + '" class="btn btn-danger btn-xs" ';
            buildedRow += ' name="removeFromPlaylist">- Remove</button>';
            buildedRow += '</td></tr>';
        }

        return buildedRow;
    }


    /**
     * Table helper for playlist table
     * @param data
     * @returns {*}
     */
    function buildPlaylistTable(data) {
        if (data) {
            var temp_start = '<table class="sortable table table-striped table-hover table-responsive">' +
                '<thead><tr><th>Song ID</th><th>Song Title</th><th>Options</th></tr></thead>' +
                '<tbody>';
            var temp_end = '</tbody></table>';
            var temp = '';

            $.each(data, function (index, val) {
                if (val.song_title && val.id && val.myid) {
                    temp += buildPlaylistRow(val.id, val.song_title, val.myid);
                }
            });

            return temp_start + temp + temp_end;
        }

        return '';
    }


    /**
     * Add track to playlist
     */
    $('.results').delegate('button[name=addToPlaylist]', 'click', function (e) {
        e.preventDefault();
        console.log('add to playlist this element!');
        var itemId = $(this).attr('data-id'),
            token = $('input#mytoken').val();

        ajaxRequest = $.ajax({
            url: url + "/addToPlaylist/",
            type: "post",
            data: 'song_hash=' + itemId + '&token=' + token
        });
        ajaxRequest.done(function (response, textStatus, jqXHR){
            statusMessage('<span class="bg-success">Updating...</span>', 'load', '.playlistBox');
            console.info('------------------------------------------ Tracks adding... --------------------------------');
            var result = JSON.parse(response);

            if (true == result) {
                loadList();
            }
        });
        ajaxRequest.fail(function (response){
            statusMessage('<span class="bg-warning">There is error while adding new track to the list...</span>', '1', '.playlistBox');
            console.error(response);
        });
    });


    /**
     * Remove track to playlist
     */
    $('.playlistBox').delegate('button[name=removeFromPlaylist]', 'click', function (e) {
        e.preventDefault();
        console.log('Remove this element from the playlist!');
        var itemId = $(this).attr('data-info'),
            token = $('input#mytoken').val();

        ajaxRequest = $.ajax({
            url: url + "/removeFromPlaylist/",
            type: "post",
            data: 'song_id=' + itemId + '&token=' + token
        });
        ajaxRequest.done(function (response, textStatus, jqXHR){
            statusMessage('<span class="bg-success">Updating...</span>', 'load', '.playlistBox');
            console.info('------------------------------------------ Track removing... --------------------------------');
            var result = JSON.parse(response);

            if (true == result) {
                loadList();
            } else {
                statusMessage('<span class="bg-warning">Error due removing a track... </span>', '1', '.playlistBox');
            }
        });
        ajaxRequest.fail(function (response){
            statusMessage('<span class="bg-warning">There is error while adding new track to the list...</span>', '1', '.playlistBox');
            console.error(response);
        });
    });


    /**
     * Generate track list by Artist
     */
    $('.results').delegate('button[name=getArtistTracks]', 'click', function (e) {
        e.preventDefault();
        var itemId = $(this).attr('data-id'),
            token = $('input#mytoken').val();

        statusMessage('Fetching...', 'load', '.results');

        ajaxRequest = $.ajax({
            url: url + '/getTracks/',
            type: 'post',
            data: 'artist_hash=' + itemId + '&token=' + token
        });
        ajaxRequest.done(function (response, textStatus, jqXHR){
            statusMessage('<span class="bg-success">Submitted successfully</span>', '1', '.results');
            $('input[name=term]').val('');
            console.info('------------------------------------------ Tracks fetching... --------------------------------');
            var temp = JSON.parse(response); //$.parseJSON(response);

            if (temp.tracks) {
                $('.results').html(buildTrackTable(temp.tracks));
                $('.sortable').DataTable();
            } else {
                statusMessage('<span class="bg-danger col-xs-12 text-center">Fetch error @ tracks.. </span>', '1', '.results');
            }
        });
        ajaxRequest.fail(function (response){
            statusMessage('<span class="bg-warning">There is error while submit</span>', '1', '.results');
            console.error(response);
        });
    });


    /**
     * Search artists & trigger build table for artist list
     */
    $('button#searchButton').on('click', function (e) {
        e.preventDefault();
        var itemName = $('input[name=term]').val(),
            token = $('input#mytoken').val();

        if (itemName) {
            statusMessage('Loading...', 'load', '.results');

            ajaxRequest = $.ajax({
                url: url + '/search/',
                type: 'post',
                data: 'term=' + itemName + '&token=' + token
            });
            ajaxRequest.done(function (response, textStatus, jqXHR){
                statusMessage('<span class="bg-success col-xs-12 text-center">Submitted successfully</span>', '1', '.results');
                $('input[name=term]').val('');
                var temp = JSON.parse(response); //$.parseJSON(response);

                if (!temp.artists.items) {
                    statusMessage('<span class="bg-danger col-xs-12 text-center">Fetch error...</span>', '1', '.results');
                } else {
                    $('.results').html(buildArtistTable(temp.artists.items));
                    $('.sortable').DataTable();
                }
            });
            ajaxRequest.fail(function (response){
                statusMessage('<span class="bg-warning col-xs-12 text-center">There is error while submit</span>', '1', '.results');
                console.error(response);
            });
        } else {
            statusMessage('<span class="bg-warning">Missing parameter...</span>', '1', '.results');
        }
    });


    /**
     * Refreshing the playlist
     */
    $('button#refresh').on('click', function (e) {
        e.preventDefault();
        loadList();
    });
});
