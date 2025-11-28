<<<<<<< HEAD
// navigation.js
export function initNavigation() {
    $('.menu-list li').on('click', function() {
        const page = $(this).data('page');
        loadPage(page);
    });

    $(document).on('click', '.edit-player', function() {
        const playerId = $(this).data('id');
        loadPage('admin/player_edit', {id: playerId});
    });
}

export function loadPage(page, params = {}) {
    let url = 'pages/' + page + '.php';

    if(Object.keys(params).length > 0) {
        url += '?' + $.param(params);
    }

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#center-content').html(response);
        },
        error: function(xhr, status, error) {
            $('#center-content').html('<h2>Fehler</h2><p>Seite konnte nicht geladen werden: ' + url + '</p>');
        }
    });
}
=======
// navigation.js
export function initNavigation() {
    $('.menu-list li').on('click', function() {
        const page = $(this).data('page');
        loadPage(page);
    });

    $(document).on('click', '.edit-player', function() {
        const playerId = $(this).data('id');
        loadPage('admin/player_edit', {id: playerId});
    });
}

export function loadPage(page, params = {}) {
    let url = 'pages/' + page + '.php';

    if(Object.keys(params).length > 0) {
        url += '?' + $.param(params);
    }

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            $('#center-content').html(response);
        },
        error: function(xhr, status, error) {
            $('#center-content').html('<h2>Fehler</h2><p>Seite konnte nicht geladen werden: ' + url + '</p>');
        }
    });
}
>>>>>>> 971ab47689bd561bd08c6e4d77cea7f516414d66
