<?php
class Admin extends CDController
{
	private $act = "view";
	private $target = "home";
	private $target_pkey;

	public function __construct()
	{
		# TODO: Define this method
	}

	public function process($req)
	{
		# Perform the action
		if (isset($req['act']))
		{
			$action = strtolower(BaseClass::Clean($req['act']));

			if (isset($req['target']))
			   $ClassName = BaseClass::Clean($req['target']);

			if (isset($req['pkey']))
				$this->target_pkey = BaseClass::Clean($req['pkey']);
		}
	}
}
