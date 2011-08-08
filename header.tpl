
        <!-- MODULE JBX_MENU -->
{if $menu.searchable_autocomplete}
        <script type="text/javascript">
        //<![CDATA[
        var menu_path = '{$menu.path}';
        var id_lang = '{$menu.id_lang}';
        //]]>
        </script>
        {if $smarty.const._PS_VERSION_|substr:0:3 eq '1.3'}
        <script type="text/javascript" src="{$menu.path}js/search.js"></script>
		{elseif $smarty.const._PS_VERSION_|substr:0:3 eq '1.4'}
		<script type="text/javascript">
		//<!--
		{literal}
		$('document').ready( function() {
			$("#search_query_menu")
				.autocomplete(
					'{/literal}{if $menu.search_ssl == 1}{$link->getPageLink('search.php', true)}{else}{$link->getPageLink('search.php')}{/if}{literal}', {
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
		{/literal}
		//-->
		</script>
		{/if}
{/if}
        <!-- /MODULE JBX_MENU -->