<?php
return
    [
        "exit_on_error" => false,
        "use_db" => true,
        "base_url" => "localhost:8000",
        "message" => array(),
        "active_page" => "home",
        "pages" =>
        [
            [
                "name" => "home",
                "text" => "Home",
                "href" => "/home"
            ],
            [
                "name" => "membership",
                "text" => "Membership",
                "href" => "/membership",
                "children" =>
                [
                    ["name" => "member-child1", "text" => "member-child-1", "href" => "/membership/child1"],
                    ["name" => "member-child2", "text" => "member-child-2", "href" => "/membership/child2"],
                    [
                        "name" => "member-child3",
                        "text" => "member-child-3",
                        "href" => "/membership/child3",
                        "children" =>
                        [
                            ["name" => "member-child3-child1", "text" => "member-child3-child1", "href" => "/membership/child3/child1"],
                            ["name" => "member-child3-child2", "text" => "member-child3-child2", "href" => "/membership/child3/child2"]
                        ]
                    ]
                ]
            ],
            [
                "name" => "events",
                "text" => "Events",
                "href" => "calendar",
                "children" =>
                [
                    ["name" => "event-list", "text" => "Community Events", "href" => "/calendar/list"],
                    ["name" => "calendar", "text" => "Event Calendar", "href" => "/calendar/event"]
                ]
            ],
            [
                "name" => "about",
                "text" => "About",
                "href" => "/about",
                "children" =>
                [
                    ["name" => "about-blog", "text" => "Our Blog", "href" => "/about/blog"],
                    ["name" => "about-company", "text" => "Our Company", "href" => "/about/company"],
                    ["name" => "about-child3", "text" => "about-child-3", "href" => "/about/child3"]
                ]
            ],
            [
                "name" => "nvprime",
                "text" => "NVPrime Pages",
                "href" => "nvprime",
                "children" =>
                [
                    [
                        "name" => "home-resources",
                        "text" => "Resources",
                        "children" =>
                        [
                            ["name" => "tips-for-buyers", "text" => "Tips For Buyers", "href" => "/chime/tips-for-buyers"],
                            ["name" => "tips-for-sellers", "text" => "Tips For Sellers", "href" => "/chime/tips-for-sellers"],
                            ["name" => "homeowner-information", "text" => "Homeowner Information", "href" => "/chime/homeowner-information"],
                            ["name" => "mortgage-information", "text" => "Mortgage Information", "href" => "/chime/mortgage-information"],
                            ["name" => "mortgage-rates", "text" => "Mortgage Rates", "href" => "/chime/mortgage-rates"],
                            ["name" => "title-and-escrow", "text" => "Title and Escrow", "href" => "/chime/title-and-escrow"],
                            ["name" => "real-estate-glossary", "text" => "Real Estate Glossary", "href" => "/chime/real-estate-glossary"]
                        ]
                    ]
                ]
            ]
        ]
    ];