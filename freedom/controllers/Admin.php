<?php
use Psr\Container\ContainerInterface;

class Admin extends CDController {
    protected $act = "view";
    protected $target = "home";
    protected $target_pkey;

    /**
     * Create a new instance
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
}
