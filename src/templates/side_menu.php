<?php
$user = $this->config->get("session")->user;
$profile = $user->get('profile', false, false);

# Create a mega menu from the page configuration
$menu = "
<div id='side-menu' class='d-flex flex-column flex-shrink-0 text-white bg-dark' style='width: 280px;'>
    <button class='btn btn-toggle ms-1 mb-0 me-auto text-white' data-bs-toggle='collapse' data-bs-target='#sidebar-collapse' aria-expanded='true'>
       <span class='fs-4'>Admin Menu</span>
       <i class='fa fa-caret-down'></i>
    </button>
    <hr />
    <ul id='sidebar-collapse' class='nav nav-pills flex-column mb-auto collapse show'>";
$menu .= ListItem(0, $side_menu, $this->active_page, $user);
$menu .= "
    </ul>
    <hr>
    <div class='dropdown'>
        <a href='#' class='d-flex align-items-center text-white text-decoration-none dropdown-toggle' id='dropdownUser1' data-bs-toggle='dropdown' aria-expanded='false'>
            <img class='headshot rounded-circle me-2' src=\"{$profile->Img('headshot')}\" width='32' height='32'/>
            <strong>{$user->nick_name}</strong>
        </a>
        <ul class='dropdown-menu dropdown-menu-dark text-small shadow' aria-labelledby='dropdownUser1'>
            <li><a class='dropdown-item' href='#'>New project...</a></li>
            <li><a class='dropdown-item' href='#'>Settings</a></li>
            <li><a class='dropdown-item' href='#'>Profile</a></li>
            <li><hr class='dropdown-divider'></li>
            <li><a class='dropdown-item' href='/logout'>Sign out</a></li>
        </ul>
    </div>
</div>
<script type='text/javascript'>
$('#side-menu').resizable({handles: 'w'});
</script>";

return $menu;

function ListItem($num, $item, $active_page, $user)
{
    if (is_array($item))
    {
        $html = "";
        foreach ($item as $i => $sub_item)
        {
            $html .= ListItem($num + (int) $i, (object) $sub_item, $active_page, $user);
        }
        return $html;
    }

    # Check Permissions on the item
    /*
    if (isset($item->permissions))
    {
        if ($user->HasPermission($item->permissions) == false)
            return "";
    }
    */
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
        $innerHTML = "<a class='nav-link p-2 text-white {$active}' href='{$item->href}' alt='{$item->name}' title='{$item->name}'>{$item->text}</a>";
    }

    $html = "<li id='sb-item-{$num}' class='{$class_list}'>
        {$innerHTML}";

    # Add anychildren
    if (isset($item->children))
    {
        $child_html = ListItem($num + 1, $item->children, $active_page, $user);
        $html .= "<i class='fa fa-caret-right' data-bs-toggle='collapse' data-bs-target='#side_dropdown{$num}' aria-expanded='true'></i>
        <div class='submenu collapse show' id='side_dropdown{$num}'>
            <ul class='nav nav-pills flex-column mb-auto'>
                {$child_html}
            </ul>
        </div>";
    }

    $html .= "
    </li>";

    return $html;
}