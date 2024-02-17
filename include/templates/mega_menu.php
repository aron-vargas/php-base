<?php

$session = $_SESSION["APPSESSION"];

# Create a mega menu from the page configuration
$menu = "<div class='mega_menu navbar-nav'>";
$menu .= AppItem(0, $session->config->pages);
$menu .= "</div>";

return $menu;

function AppItem($num, $item)
{
    if (is_array($item))
    {
        $html = "";
        foreach ($item as $i => $sub_item)
        {
            $html .= AppItem($num + $i, $sub_item);
        }
        return $html;
    }

    # Add class
    $class_list = "menu-item";
    if (isset($item->menuclass))
    {
        if (is_array($item->class))
            $class_list = " ".implode(" ", $item->class);
        else
            $class_list .= " {$item->class}";
    }

    # SET the item contents
    # name and text are required
    if (!isset($item->name)) $item->name = "Page Link";
    if (!isset($item->text)) $item->text = "--Missing-Text--";
    $innerHTML = $item->text;
    if (isset($item->href))
    {
        $active = ($item->name == $_SESSION['ACTIVE_PAGE']) ? "active" : "";
        $innerHTML = "<a class='nav-link p-2 {$active}' href='{$item->href}' alt='{$item->name}' title='{$item->name}'>{$item->text}</a>";
    }

    $html = "<div id='mm-item-{$num}' class='{$class_list}'>
        {$innerHTML}";

    # Add anychildren
    if (isset($item->children))
    {
        $child_html = AppItem($num + 1, $item->children);
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