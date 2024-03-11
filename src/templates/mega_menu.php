<?php
$user = $this->config->get("session")->user;
# Create a mega menu from the page configuration
$menu = "<div class='mega_menu navbar-nav'>";
$menu .= AppItem(0, $pages, $this->active_page, $user);
$menu .= "</div>";

return $menu;

function AppItem($num, $item, $active_page, $user)
{
    if (is_array($item))
    {
        $html = "";
        foreach ($item as $i => $sub_item)
        {
            $html .= AppItem($num + (int) $i, (object) $sub_item, $active_page, $user);
        }
        return $html;
    }

    # Check Permissions on the item
    if (isset($item->permissions))
    {
        if ($user->HasPermission($item->permissions) == false)
            return "";
    }

    # Add class
    $class_list = "menu-item";
    if (isset($item->menuclass))
    {
        if (is_array($item->class))
            $class_list = " " . implode(" ", $item->class);
        else
            $class_list .= " {$item->class}";
    }

    # SET the item contents
    # name and text are required
    if (!isset($item->name))
        $item->name = "Page Link";
    if (!isset($item->text))
        $item->text = "--Missing-Text--";
    $innerHTML = $item->text;
    if (isset($item->href))
    {
        $active = ($item->name == $active_page) ? "active" : "";
        $innerHTML = "<a class='nav-link p-2 {$active}' href='{$item->href}' alt='{$item->name}' title='{$item->name}'>{$item->text}</a>";
    }

    $html = "<div id='mm-item-{$num}' class='{$class_list}'>
        {$innerHTML}";

    # Add anychildren
    if (isset($item->children))
    {
        $child_html = AppItem($num + 1, $item->children, $active_page, $user);
        $html .= "<div class='submenu'>
            <div class='navbar-nav'>
                {$child_html}
            </div>
        </div>";
    }

    $html .= "
    </div>";

    return $html;
}