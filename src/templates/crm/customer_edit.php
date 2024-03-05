<?php
$cust = $this->model;

# TODO: Add breadcrumb
echo "
<style>
.form-signin
{
	max-width: 600px;
	padding: 15px;
}
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
</style>
<div role='main' class='container'>
<main class='form-signin w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='/save/customer/crm' method='POST'>
		<input type='hidden' name='pkey' value='{$cust->pkey}'>
		<h4>Customer Information:</h4>
		<div class='mb-4'>
			<label class='form-label' for='account_code' style='margin-left: 0px;'>Cust ID</label>
			<input type='text' id='account_code' name='account_code' class='form-control' placeholder='A.K.A.' value='{$cust->account_code}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='customer_name' style='margin-left: 0px;'>Name</label>
			<input type='text' id='customer_name' name='customer_name' class='form-control' placeholder='Customer Name' value='{$cust->customer_name}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='customer_status' style='margin-left: 0px;'>Status</label>
			<input type='text' id='customer_status' name='customer_status' class='form-control' placeholder='Status' value='{$cust->customer_status}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='customer_type' style='margin-left: 0px;'>Type</label>
			<input type='text' id='customer_type' name='customer_type' class='form-control' placeholder='Type of Business' value='{$cust->customer_type}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='description' style='margin-left: 0px;'>Description</label>
			<input type='text' id='description' name='description' class='form-control' placeholder='Tell me more' value='{$cust->description}'>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' name='act' value='1'>Submit</button>
            <button type='submit' class='btn btn-primary' name='act' value='-1'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";
