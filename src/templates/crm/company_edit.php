<?php
use Freedom\Models\Company;
use Freedom\Views\CDView;

$co = $this->model;
$this->Crumb("/home", "Home");
$this->Crumb("/crm/company/list", "Companies");
$this->Crumb(null, "Edit", true);

$opt_array = Company::StatusOptions();
$status_options = CDView::OptionsList($co->status, $opt_array);

$primary_address_state_options = CDView::OptionsStateList($co->primary_address->state);
$billing_address_state_options = CDView::OptionsStateList($co->billing_address->state);
$shipping_address_state_options = CDView::OptionsStateList($co->shipping_address->state);

# TODO: Add breadcrumb
echo "
<style>
form
{
    margin: 0;
    padding: 0;
}
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
.form-label
{
    margin-bottom: 0;
}

</style>
{$this->render_trail()}
<div role='main' class='container'>
    <div class='row'>
        <div class='col'>
            <main class='w-100 m-auto'>
                <div class='card-body p-md-5 mx-md-4'>
                    <form action='/crm/company/save' method='POST'>
                        <input type='hidden' name='pkey' value='{$co->pkey}'>
                        <h4>Company Information:</h4>
                        <div class='mb-1'>
                            <label class='form-label' for='company_name' style='margin-left: 0px;'>Name</label>
                            <input type='text' id='company_name' name='company_name' class='form-control' placeholder='Company Name' value='{$co->company_name}'>
                        </div>
                        <div class='mb-1'>
                            <label class='form-label' for='status' style='margin-left: 0px;'>Status</label>
                            <select id='status' name='status' class='form-control'>
                                {$status_options}
                            </select>
                        </div>
                        <div class='mb-1'>
                            <label class='form-label' for='description' style='margin-left: 0px;'>Description</label>
                            <input type='text' id='description' name='description' class='form-control' placeholder='Tell me more' value='{$co->description}'>
                        </div>
                        <div class='text-center pt-1 pb-1'>
                            <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
                            <button type='submit' class='btn btn-warning' name='act' value='-1' onClick='SubmitFrom(this)'>Delete</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
    <div class='row m-3'>
        <div class='col'>
            <div class='card-body p-md-2'>
                <form action='/crm/company/save' method='POST'>
                <input type='hidden' name='pkey' value='{$co->pkey}' />
                <input type='hidden' name='child' value='primary_address' />
                <h5 class='card-title'>Primary Address</h5>
                <div class='mb-1'>
                    <label class='form-label' for='primary_address[address_1]' style='margin-left: 0px;'>Line 1</label>
                    <input type='text' id='primary_address[address_1]' name='primary_address[address_1]' class='form-control form-control-sm' value='{$co->primary_address->address_1}' />
                </div>
                <div class='mb-1'>
                    <label class='form-label' for='primary_address[address_2]' style='margin-left: 0px;'>Line 2</label>
                    <input type='text' id='primary_address[address_2]' name='primary_address[address_2]' class='form-control form-control-sm' value='{$co->primary_address->address_2}' />
                </div>
                <div class='mb-1'>
                    <label class='form-label' for='primary_address[city]' style='margin-left: 0px;'>City</label>
                    <input type='text' id='primary_address[city]' name='primary_address[city]' class='form-control form-control-sm' value='{$co->primary_address->city}' />
                </div>
                <div class='mb-1 row'>
                    <div class='col'>
                        <label class='form-label' for='primary_address[state]' style='margin-left: 0px;'>State</label>
                        <select id='primary_address[state]' name='primary_address[state]' class='form-control form-control-sm'>
                        $primary_address_state_options
                        </select>
                    </div>
                    <div class='col'>
                        <label class='form-label' for='primary_address[zip]' style='margin-left: 0px;'>Zip</label>
                        <input type='text' id='primary_address[zip]' name='primary_address[zip]' class='form-control form-control-sm' value='{$co->primary_address->zip}' />
                    </div>
                </div>
                <div class='text-center'>
                    <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Update</button>
                </div>
                </form>
            </div>
        </div>
        <div class='col'>
            <div class='card-body p-md-2'>
                <form action='/crm/company/save' method='POST'>
                <input type='hidden' name='pkey' value='{$co->pkey}' />
                <input type='hidden' name='child' value='billing_address' />
                <h5 class='card-title'>Billing Address</h5>
                <div class='mb-1'>
                    <label class='form-label' for='billing_address[address_1]' style='margin-left: 0px;'>Line 1</label>
                    <input type='text' id='billing_address[address_1]' name='billing_address[address_1]' class='form-control form-control-sm' value='{$co->billing_address->address_1}' />
                </div>
                <div class='mb-1'>
                    <label class='form-label' for='billing_address[address_2]' style='margin-left: 0px;'>Line 2</label>
                    <input type='text' id='billing_address[address_2]' name='billing_address[address_2]' class='form-control form-control-sm' value='{$co->billing_address->address_2}' />
                </div>
                <div class='mb-1'>
                    <label class='form-label' for='billing_address[city]' style='margin-left: 0px;'>City</label>
                    <input type='text' id='billing_address[city]' name='billing_address[city]' class='form-control form-control-sm' value='{$co->billing_address->city}' />
                </div>
                <div class='mb-1 row'>
                    <div class='col'>
                        <label class='form-label' for='billing_address[state]' style='margin-left: 0px;'>State</label>
                        <select id='billing_address[state]' name='billing_address[state]' class='form-control form-control-sm'>
                            $billing_address_state_options
                        </select>
                    </div>
                    <div class='col'>
                        <label class='form-label' for='billing_address[zip]' style='margin-left: 0px;'>Zip</label>
                        <input type='text' id='billing_address[zip]' name='billing_address[zip]' class='form-control form-control-sm' value='{$co->billing_address->zip}' />
                    </div>
                </div>
                <div class='text-center'>
                    <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Update</button>
                </div>
                </form>
            </div>
        </div>
        <div class='col'>
            <div class='card-body p-md-2'>
                <form action='/crm/company/save' method='POST'>
                <input type='hidden' name='pkey' value='{$co->pkey}' />
                <input type='hidden' name='child' value='shipping_address' />
                <h5 class='card-title'>Shipping Address</h5>
                <div class='mb-1'>
                    <label class='form-label' for='shipping_address[address_1]' style='margin-left: 0px;'>Line 1</label>
                    <input type='text' id='shipping_address[address_1]' name='shipping_address[address_1]' class='form-control form-control-sm' value='{$co->shipping_address->address_1}' />
                </div>
                <div class='mb-1'>
                    <label class='form-label' for='shipping_address[address_2]' style='margin-left: 0px;'>Line 2</label>
                    <input type='text' id='shipping_address[address_2]' name='shipping_address[address_2]' class='form-control form-control-sm' value='{$co->shipping_address->address_2}' />
                </div>
                <div class='mb-1'>
                    <label class='form-label' for='shipping_address[city]' style='margin-left: 0px;'>City</label>
                    <input type='text' id='shipping_address[city]' name='shipping_address[city]' class='form-control form-control-sm' value='{$co->shipping_address->city}' />
                </div>
                <div class='mb-1 row'>
                    <div class='col'>
                        <label class='form-label' for='shipping_address[state]' style='margin-left: 0px;'>State</label>
                        <select id='shipping_address[state]' name='shipping_address[state]' class='form-control form-control-sm'>
                            $shipping_address_state_options
                        </select>
                    </div>
                    <div class='col'>
                        <label class='form-label' for='shipping_address[zip]' style='margin-left: 0px;'>Zip</label>
                        <input type='text' id='shipping_address[zip]' name='shipping_address[zip]' class='form-control form-control-sm' value='{$co->shipping_address->zip}' />
                    </div>
                </div>
                <div class='text-center'>
                    <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Update</button>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>";
