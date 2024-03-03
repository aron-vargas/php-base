<?php
spl_autoload_register(function ($class)
{
    echo "<div>";
    echo "Current Dir ";
    echo __DIR__;
    echo "</div>";
    echo "<div>";
    echo "Looking for $class";
    echo "</div>";
});