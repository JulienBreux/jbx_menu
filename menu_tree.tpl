{if !$item.logged || ($item.logged && $menu.logged)}
<li {if ($item.type eq $page_name && $item.id eq $menu.id)}class="sfHoverForce"{/if}{if $item.css} id="{$item.css}"{/if}>
  <a href="{$item.link|escape:htmlall:'UTF-8'}" title="{$item.title|escape:htmlall:'UTF-8'}"{if $item.new_window > 0} target="_blank"{/if}>
    {if $menu.icons}
      {if file_exists($menu.icons_path|cat:$item.id_menu|cat:'.jpg')}
        <img src="{$menu.icons_url|cat:$item.id_menu|cat:'.jpg'}" alt="{$item.title|escape:htmlall:'UTF-8'}" />
        {assign var='haveIcon' value='1'}
      {/if}
      {if file_exists($menu.icons_path|cat:$item.id_menu|cat:'.gif')}
        <img src="{$menu.icons_url|cat:$item.id_menu|cat:'.gif'}" alt="{$item.title|escape:htmlall:'UTF-8'}" />
        {assign var='haveIcon' value='1'}
      {/if}
      {if file_exists($menu.icons_path|cat:$item.id_menu|cat:'.png')}
        <img src="{$menu.icons_url|cat:$item.id_menu|cat:'.png'}" alt="{$item.title|escape:htmlall:'UTF-8'}" />
        {assign var='haveIcon' value='1'}
      {/if}
    {/if}
    &nbsp;{if isset($haveIcon)}<span>{/if}{$item.title|escape:htmlall:'UTF-8'}{if isset($haveIcon)}</span>{/if}
    {if isset($item.numProducts) && $menu.categories_num && (!$menu.categories_zero && $item.numProducts > 0 || $menu.categories_zero)}&nbsp;<i>({$item.numProducts})</i>{/if}
  </a>
  {if $item.childrens|@count > 0}
  	<ul>
  	{assign var='childrens' value=$item.childrens}
  	{foreach from=$childrens item=item name=menuTreeChildrens}
  		{include file=$menu_tpl_tree}
  	{/foreach}
  	</ul>
  {/if}
</li>
{/if}