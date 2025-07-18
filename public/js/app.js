function renderProduct(product) {
    $('<div class="col-lg-4 col-sm-6 articleitem" id="article-pattern">' +
        '<div class="b-article">' +
            '<div class="v-img">' +
                '<a href="/product/'+ product.id + '">' +
                '<img src="/storage/'+ product.picture +'" alt=""></a>' +
            '</div>' +
            '<div class="v-desc">' +
            '<a href="/product/'+ product.id + '">'+ product.name +'</a>' +
            '</div>' +
            '<p>' + (product.description ? product.description.slice(0, 20) : '') + '...</p>' +
            '<div class="v-views">' +
            product.views + ' vues' +
            '</div>' +
            '<div class="v-views">' +
            'date' + product.published_date +
            '</div>' +
        '</div>' +
        '</div>')
        .appendTo($('#articlelist'));
}

function getProductsAndRender(option = '') {
    $.ajax({
        url: "/api/products" + '?sort=' + option,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            $('#articlelist').empty();
            data.forEach(product => renderProduct(product));
        },
        error: function(err) {
            console.error('Erreur chargement produits:', err);
        }
    }).done(function(result) {
        $('#articlelist').empty();

        // Si on trie par date, inverser l'ordre pour avoir le plus récent en premier
        if(option === 'published_date') {
            result.reverse();
        }

        for(let i = 0; i < result.length; i++){
            renderProduct(result[i])
        }
    });
}

function searchArticles(query) {
    if ($('#articlelist').length === 0) return;

    $.ajax({
        url: '/api/search',
        type: 'GET',
        data: { q: query },
        dataType: 'json',
        success: function(data) {
            $('#articlelist').empty();
            if (data.length > 0) {
                data.forEach(article => renderProduct(article));
            } else {
                $('#articlelist').html('<p>Aucun résultat trouvé.</p>');
            }
        },
        error: function(err) {
            console.error('Erreur AJAX recherche:', err);
            $('#articlelist').html('<p>Erreur lors de la recherche.</p>');
        }
    });
}

$(document).ready(function() {
    console.log('JS global chargé');

    if ($('#articlelist').length) {
        getProductsAndRender();
    }

    $('#searchInput').on('keyup', function() {
        const query = $(this).val().trim();

        if (query.length > 0) {
            searchArticles(query);
        } else {
            if ($('#articlelist').length) {
                getProductsAndRender();
            }
        }
    });
});
