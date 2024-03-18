<?php
$loc = $this->model;
$this->Crumb("/home", "Home");
$this->Crumb("/crm/location/list", "Locations\\Addresses");
if (isset ($_SERVER['HTTP_REFERER']))
    $this->Crumb($_SERVER['HTTP_REFERER'], " <i class='fa fa-angle-left'></i>Back");
$this->Crumb(null, "Edit", true);

$state_options = \Freedom\Views\CDView::OptionsStateList($loc->state);

# TODO: Add breadcrumb
echo "
<style>
.card-body
{
	border: 1px solid #CCC;
	border-radius: 8px;
}
</style>
{$this->render_trail()}
<div role='main' class='container'>
<main class='w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='/save/location/crm' method='POST'>
		<input type='hidden' name='pkey' value='{$loc->pkey}'>
		<h4>Location #{$loc->pkey}:</h4>
		<div class='mb-4'>
			<label class='form-label' for='short_name' style='margin-left: 0px;'>Common Name</label>
			<input type='text' id='short_name' name='short_name' class='form-control' placeholder='Name' value='{$loc->short_name}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='title' style='margin-left: 0px;'>Title</label>
			<input type='text' id='title' name='title' class='form-control' placeholder='Title' value='{$loc->title}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='role' style='margin-left: 0px;'>Role</label>
			<input type='text' id='role' name='role' class='form-control' placeholder='Role' value='{$loc->role}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='address_1' style='margin-left: 0px;'>Address Line 1</label>
			<input type='text' id='address_1' name='address_1' class='form-control' placeholder='Line 1' value='{$loc->address_1}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='address_2' style='margin-left: 0px;'>Address Line 2</label>
			<input type='text' id='address_2' name='address_2' class='form-control' placeholder='Line 2' value='{$loc->address_2}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='city' style='margin-left: 0px;'>City</label>
			<input type='text' id='city' name='city' class='form-control' placeholder='City' value='{$loc->city}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='state' style='margin-left: 0px;'>State</label>
			<select id='state' name='state' class='form-control'>
                $state_options
            </select>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='zip' style='margin-left: 0px;'>Zip</label>
			<input type='text' id='zip' name='zip' class='form-control' placeholder='zip' value='{$loc->zip}'>
		</div>
        <div class='hidden mb-4'>
			<label class='form-label' for='country' style='margin-left: 0px;'>Country</label>
			<input type='text' id='country' name='country' class='form-control' placeholder='country' value='{$loc->country}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='phone' style='margin-left: 0px;'>Phone #</label>
			<input type='tel' id='phone' name='phone' class='form-control' placeholder='(000) 000-0000' value='{$loc->phone}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='mobile' style='margin-left: 0px;'>Mobile #</label>
			<input type='tel' id='mobile' name='mobile' class='form-control' placeholder='(000) 000-0000' value='{$loc->mobile}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='url' style='margin-left: 0px;'>Url</label>
			<input type='url' id='url' name='url' class='form-control' placeholder='www.domain.com' value='{$loc->url}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='email' style='margin-left: 0px;'>Email</label>
			<input type='email' id='email' name='email' class='form-control' placeholder='name@domain.com' value='{$loc->email}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='location_status' style='margin-left: 0px;'>Status</label>
			<input type='text' id='location_status' name='location_status' class='form-control' value='{$loc->location_status}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='location_type' style='margin-left: 0px;'>Type</label>
			<input type='text' id='location_type' name='location_type' class='form-control' value='{$loc->location_type}'>
		</div>
        <div class='mb-4'>
			<label class='form-label' for='description' style='margin-left: 0px;'>Description</label>
			<input type='text' id='description' name='description' class='form-control' placeholder='Helpful Information' value='{$loc->description}'>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' name='act' value='1' onClick='SubmitFrom(this)'>Submit</button>
            <button type='button' class='hidden btn btn-primary' name='act' value='-1'  onClick='SubmitFrom(this)'>Delete</button>
		</div>
	</form>
</div>
</main>
</div>";
