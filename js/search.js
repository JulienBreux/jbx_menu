$('document').ready( function() {
	$("#search_query_menu")
		.autocomplete(
			'http://prestashop/1.4.0.13/fr/recherche', {
				minChars: 3,
				max: 10,
				width: 500,
				selectFirst: false,
				scroll: false,
				dataType: "json",
				formatItem: function(data, i, max, value, term) {
					return value;
				},
				parse: function(data) {
					var mytab = new Array();
					for (var i = 0; i < data.length; i++)
						mytab[mytab.length] = { data: data[i], value: data[i].cname + ' > ' + data[i].pname };
					return mytab;
				},
				extraParams: {
					ajaxSearch: 1,
					id_lang: 2
				}
			}
		)
		.result(function(event, data, formatted) {
			$('#search_query_menu').val(data.pname);
			document.location.href = data.product_link;
		})
});
