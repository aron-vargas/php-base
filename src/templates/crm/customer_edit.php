<?php
use Freedom\Models\Customer;
use Freedom\Views\CDView;

$cust = $this->model;
$this->Crumb("/home", "Home");
$this->Crumb("/crm/customer/list", "Customers");
if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb(null, "Edit", true);

$opt_array = Customer::StatusOptions();
$status_options = CDView::OptionsList($cust->customer_status, $opt_array);

$primary_address_state_options = CDView::OptionsStateList($cust->primary_address->state);
$billing_address_state_options = CDView::OptionsStateList($cust->billing_address->state);
$shipping_address_state_options = CDView::OptionsStateList($cust->shipping_address->state);

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

.address label, .address .buttons
{
    display: none;
}
.address.edit label
{
    display: inline-block;
}
.address.edit .buttons
{
    display: block;
}
.btn-sm
{
    font-size: 10px;
}

</style>
{$this->render_trail()}
<div role='main' class='container'>
    <div class='row'>
            <div class='col'>
                <main class='w-100 m-auto'>
                    <div class='card-body p-md-5'>
                        <form action='/crm/customer/save' method='POST'>
                        <input type='hidden' name='pkey' value='{$cust->pkey}'>
                            <h4>Customer Information:</h4>
                            <div class='mb-1'>
                                <label class='form-label' for='account_code' style='margin-left: 0px;'>Cust ID</label>
                                <input type='text' id='account_code' name='account_code' class='form-control' placeholder='A.K.A.' value='{$cust->account_code}'>
                            </div>
                            <div class='mb-1'>
                                <label class='form-label' for='customer_name' style='margin-left: 0px;'>Name</label>
                                <input type='text' id='customer_name' name='customer_name' class='form-control' placeholder='Customer Name' value='{$cust->customer_name}'>
                            </div>
                            <div class='mb-1'>
                                <label class='form-label' for='customer_status' style='margin-left: 0px;'>Status</label>
                                <select id='customer_status' name='customer_status' class='form-control'>
                                    $status_options
                                </select>
                            </div>
                            <div class='mb-1'>
                                <label class='form-label' for='customer_type' style='margin-left: 0px;'>Type</label>
                                <input type='text' id='customer_type' name='customer_type' class='form-control' placeholder='Type of Business' value='{$cust->customer_type}'>
                            </div>
                            <div class='mb-1'>
                                <label class='form-label' for='description' style='margin-left: 0px;'>Description</label>
                                <input type='text' id='description' name='description' class='form-control' placeholder='Tell me more' value='{$cust->description}'>
                            </div>
                            <div class='text-center'>
                                <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
                                <button type='submit' class='btn btn-primary' name='act' value='-1' onClick='SubmitFrom(this)'>Delete</button>
                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </div>
    </div>
    <div class='row m-3'>
        <div class='col'>
            <div id='primary-div' class='address card-body p-md-2'>
                <form action='/crm/customer/save' method='POST'>
                    <input type='hidden' name='pkey' value='{$cust->pkey}' />
                    <input type='hidden' name='child' value='primary_address' />
                    <button class='btn btn-primary btn-sm float-end' type='button' onClick=\"$('#primary-div').toggleClass('edit')\">
                        <i class='fa fa-pencil'></i>
                    </button>
                    <h5 class='card-title'>Primary Address</h5>
                    <div class='mb-1'>
                        <label class='form-label' for='primary_address[address_1]' style='margin-left: 0px;'>Line 1</label>
                        <input type='text' id='primary_address[address_1]' name='primary_address[address_1]' class='form-control form-control-sm' value='{$cust->primary_address->address_1}' />
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='primary_address[address_2]' style='margin-left: 0px;'>Line 2</label>
                        <input type='text' id='primary_address[address_2]' name='primary_address[address_2]' class='form-control form-control-sm' value='{$cust->primary_address->address_2}' />
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='primary_address[city]' style='margin-left: 0px;'>City</label>
                        <input type='text' id='primary_address[city]' name='primary_address[city]' class='form-control form-control-sm' value='{$cust->primary_address->city}' />
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
                            <input type='text' id='primary_address[zip]' name='primary_address[zip]' class='form-control form-control-sm' value='{$cust->primary_address->zip}' />
                        </div>
                    </div>
                    <div class='text-center buttons'>
                        <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Update</button>
                    </div>
                </form>
            </div>
        </div>
        <div class='col'>
            <div id='billing-div' class='address card-body p-md-2'>
                <form action='/crm/customer/save' method='POST'>
                    <input type='hidden' name='pkey' value='{$cust->pkey}' />
                    <input type='hidden' name='child' value='billing_address' />
                    <button class='btn btn-primary btn-sm float-end' type='button' onClick=\"$('#billing-div').toggleClass('edit')\">
                        <i class='fa fa-pencil'></i>
                    </button>
                    <h5 class='card-title'>Billing Address</h5>
                    <div class='mb-1'>
                        <label class='form-label' for='billing_address[address_1]' style='margin-left: 0px;'>Line 1</label>
                        <input type='text' id='billing_address[address_1]' name='billing_address[address_1]' class='form-control form-control-sm' value='{$cust->billing_address->address_1}' />
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='billing_address[address_2]' style='margin-left: 0px;'>Line 2</label>
                        <input type='text' id='billing_address[address_2]' name='billing_address[address_2]' class='form-control form-control-sm' value='{$cust->billing_address->address_2}' />
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='billing_address[city]' style='margin-left: 0px;'>City</label>
                        <input type='text' id='billing_address[city]' name='billing_address[city]' class='form-control form-control-sm' value='{$cust->billing_address->city}' />
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
                            <input type='text' id='billing_address[zip]' name='billing_address[zip]' class='form-control form-control-sm' value='{$cust->billing_address->zip}' />
                        </div>
                    </div>
                    <div class='buttons text-center'>
                        <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Update</button>
                    </div>
                </form>
            </div>
        </div>
        <div class='col'>
            <div id='shipping-div' class='address card-body p-md-2'>
                <form action='/crm/customer/save' method='POST'>
                    <input type='hidden' name='pkey' value='{$cust->pkey}' />
                    <input type='hidden' name='child' value='shipping_address' />
                    <button class='btn btn-primary btn-sm float-end' type='button' onClick=\"$('#shipping-div').toggleClass('edit')\">
                        <i class='fa fa-pencil'></i>
                    </button>
                    <h5 class='card-title'>Shipping Address</h5>
                    <div class='mb-1'>
                        <label class='form-label' for='shipping_address[address_1]' style='margin-left: 0px;'>Line 1</label>
                        <input type='text' id='shipping_address[address_1]' name='shipping_address[address_1]' class='form-control form-control-sm' value='{$cust->shipping_address->address_1}' />
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='shipping_address[address_2]' style='margin-left: 0px;'>Line 2</label>
                        <input type='text' id='shipping_address[address_2]' name='shipping_address[address_2]' class='form-control form-control-sm' value='{$cust->shipping_address->address_2}' />
                    </div>
                    <div class='mb-1'>
                        <label class='form-label' for='shipping_address[city]' style='margin-left: 0px;'>City</label>
                        <input type='text' id='shipping_address[city]' name='shipping_address[city]' class='form-control form-control-sm' value='{$cust->shipping_address->city}' />
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
                            <input type='text' id='shipping_address[zip]' name='shipping_address[zip]' class='form-control form-control-sm' value='{$cust->shipping_address->zip}' />
                        </div>
                    </div>
                    <div class='buttons text-center'>
                        <button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>";
