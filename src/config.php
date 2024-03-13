<?php
return [
    "exit_on_error" => false,
    "use_db" => true,
    "base_url" => "localhost:8000",
    "message" => array(),
    "Controllers" => [
        "\Freedom\Controllers\HomeController",
        "\Freedom\Controllers\API1",
        "\Freedom\Controllers\CalController"
    ],
    "CompanyName" => "Railside Kitchen",
    "CompanyAddress" => "401 Main St., Fernley, NV 89408",
    "pages" => [
        [
            "name" => "home",
            "text" => "Home",
            "href" => "/home"
        ],
        [
            "name" => "membership",
            "text" => "Membership",
            "href" => "/membership/signup",
            "children" => [
                [
                    "name" => "member-signup",
                    "text" => "Become a Member",
                    "href" => "/membership/signup"
                ],
                [
                    "name" => "membership-info",
                    "text" => "Information",
                    "href" => "/membership/info"
                ],
                [
                    "name" => "membership-rates",
                    "text" => "Rates",
                    "href" => "/membership/rates"
                ],
                [
                    "name" => "membership-list",
                    "text" => "Our Members",
                    "href" => "/membership/list"
                ]
            ]
        ],
        [
            "name" => "events",
            "text" => "Events",
            "href" => "calendar",
            "children" => [
                ["name" => "event-list", "text" => "Community Events", "href" => "/calendar/list"],
                ["name" => "calendar", "text" => "Event Calendar", "href" => "/calendar/event"],
                [
                    "name" => "myschedule",
                    "text" => "My Schedule",
                    "href" => "/calendar/schedule",
                    "permissions" => "calendar-view"
                ]
            ]
        ],
        [
            "name" => "about",
            "text" => "About",
            "href" => "/about",
            "children" => [
                ["name" => "about-blog", "text" => "Our Blog", "href" => "/blog/blogpost/show"],
                ["name" => "about-company", "text" => "Our Company", "href" => "/about/company"]
            ]
        ],
        [
            "name" => "nvprime",
            "text" => "NVPrime Pages",
            "href" => "nvprime",
            "children" => [
                [
                    "name" => "home-resources",
                    "text" => "Resources",
                    "children" => [
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
        ],
        [
            "name" => "crm",
            "text" => "CRM",
            "href" => "/crm/",
            "permissions" => "site-admin",
            "children" => [
                [
                    "name" => "companies",
                    "text" => "Companies",
                    "href" => "/crm/company/list",
                    "permissions" => "company-view"
                ],
                [
                    "name" => "customers",
                    "text" => "Customers",
                    "href" => "/crm/customer/list",
                    "permissions" => "customer-view"
                ],
                [
                    "name" => "locations",
                    "text" => "Locations",
                    "href" => "/crm/location/list",
                    "permissions" => "location-view"
                ],
                [
                    "name" => "blog",
                    "text" => "Blog Post Admin",
                    "href" => "/blog/blogpost/list"
                ]
            ]
        ],
        [
            "name" => "admin",
            "text" => "Admin",
            "href" => "/admin/",
            "permissions" => "site-admin",
            "children" => [
                [
                    "name" => "users",
                    "text" => "Users",
                    "href" => "/admin/user/list",
                    "permissions" => "site-admin"
                ],
                [
                    "name" => "permissions",
                    "text" => "Permissions",
                    "href" => "/admin/permission/list",
                    "permissions" => "site-admin"
                ],
                [
                    "name" => "roles",
                    "text" => "Roles",
                    "href" => "/admin/usergroup/list",
                    "permissions" => "site-admin"
                ],
                [
                    "name" => "modules",
                    "text" => "Modules",
                    "href" => "/admin/module/list",
                    "permissions" => "site-admin"
                ]
            ]
        ]
    ]
];