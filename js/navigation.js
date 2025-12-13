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

    $(document).on('click', '.edit-admin', function() {
    const adminId = $(this).data('id');
    loadPage('admin/admin_edit', {id: adminId});
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
