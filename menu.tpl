{if $menu.items|@count > 0}
    {if $menu.hook == 'top'}</div>{/if}
    <!-- MODULE JBX_MENU -->
    <div class="sf-contener">
        <ul class="sf-menu">
          {foreach from=$menu.items item=item name=menuTree}
              {include file=$menu_tpl_tree}
          {/foreach}
          {if $menu.searchable_active}
          <li class="sf-search noBack" style="float:right">
              <form id="searchbox" action="search.php" method="get">
                  <input type="hidden" value="position" name="orderby" />
                  <input type="hidden" value="desc" name="orderway" />
                  <input type="text" name="search_query" id="search_query_menu" class="search" value="{if isset($smarty.get.search_query)}{$smarty.get.search_query}{/if}" autocomplete="off" />
                  {if $menu.searchable_button}
                      <input type="submit" value="ok" class="search_button" />
                  {/if}
              </form>
          </li>
          {/if}
        </ul>
        <!-- /MODULE JBX_MENU -->
{/if}
{if $menu.hook == 'menu' && $menu.items|@count > 0}</div>{/if}