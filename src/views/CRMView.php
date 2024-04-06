<?php
namespace Freedom\Views;

class CRMView extends CDView
{
    public function InitDisplay($section, $page, $display)
    {
        if ($page == "company")
            $this->template = "src/templates/crm/company_{$display}.php";
        else if ($page == "customer")
            $this->template = "src/templates/crm/customer_{$display}.php";
        else if ($page == "location")
            $this->template = "src/templates/crm/location_{$display}.php";
    }

    public function InitModel($section, $page, $pkey)
    {
        $this->model = false;

        if ($page == "customer")
            $this->model = new \Freedom\Models\Customer($pkey);
        else if ($page == "company")
            $this->model = new \Freedom\Models\Company($pkey);
        else if ($page == "location")
            $this->model = new \Freedom\Models\Location($pkey);
        else
        {
            $this->model = new \Freedom\Models\CDModel();
        }

        return $this->model;
    }
}
