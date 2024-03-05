<?php
$co = $this->model;

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
            <form action='/save/company/crm' method='POST'>
                <input type='hidden' name='pkey' value='{$co->pkey}'>
                <h4>Company Information:</h4>
                <div class='mb-4'>
                    <label class='form-label' for='company_name' style='margin-left: 0px;'>Name</label>
                    <input type='text' id='company_name' name='company_name' class='form-control' placeholder='Company Name' value='{$co->company_name}'>
                </div>
                <div class='mb-4'>
                    <label class='form-label' for='status' style='margin-left: 0px;'>Status</label>
                    <input type='text' id='status' name='status' class='form-control' placeholder='Status' value='{$co->status}'>
                </div>
                <div class='mb-4'>
                    <label class='form-label' for='description' style='margin-left: 0px;'>Description</label>
                    <input type='text' id='description' name='description' class='form-control' placeholder='Tell me more' value='{$co->description}'>
                </div>
                <div class='text-center pt-1 mb-4 pb-1'>
                    <button type='submit' class='btn btn-primary' name='act' value='1'>Submit</button>
                    <button type='submit' class='btn btn-warning' name='act' value='-1'>Delete</button>
                </div>
            </form>
        </div>
    </main>
</div>";
