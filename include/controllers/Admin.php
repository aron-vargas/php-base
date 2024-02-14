<?php
class Admin extends CDController
{
	private $act = "view";
	private $target = "home";
	private $target_pkey;

    /**
     * Create a new instance
     */
	public function __construct()
	{
		# TODO: Define this method
	}

	public function process($req)
	{
		# Perform the action
		if (isset($req['act']))
		{
			$action = strtolower(CDModel::Clean($req['act']));

			if (isset($req['target']))
			   $ClassName = CDModel::Clean($req['target']);

			if (isset($req['pkey']))
				$this->target_pkey = CDModel::Clean($req['pkey']);
		}
	}
}
