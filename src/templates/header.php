<!DOCTYPE=html>
    <html>

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="PHP custom framework or scafolding for all kinds of cool stuff">
        <meta name="author" content="Aron Vargas">
        <?php

        foreach ($css as $link)
            echo "$link\n";

        foreach ($js as $script)
            echo "$script\n";
        ?>
    </head>

    <body>