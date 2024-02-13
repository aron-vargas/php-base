<?php

	global $session;

	$is_admin = $session->user->isAdmin();
	$protected = ($is_admin) ? "" : " hidden";

	$bet = $session->controller->model;
	if (empty($bet->user_id))	$bet->user_id = $session->user->pkey;

	$user_list = User::OptionList($bet->entry_id, 1);

	$sel_win = ($bet->type == Bet::$TYPE_WIN) ? " selected" : "";
	$sel_place = ($bet->type == Bet::$TYPE_PLACE) ? " selected" : "";
	$sel_show = ($bet->type == Bet::$TYPE_SHOW) ? " selected" : "";

	$sel_paid = ($bet->paid) ? " selected" : "";
	
	echo "
<style>
.main_card
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
<main class='main_card w-100 m-auto'>
<div class='card-body p-md-5 mx-md-4'>
	<form action='index.php' method='POST'>
		<input type='hidden' name='act' value='Save'/>
		<input type='hidden' name='pkey' value='{$bet->pkey}'/>
		<input type='hidden' name='target' value='Bet'/>
		<input type='hidden' name='user_id' value='{$bet->user_id}'/>
		<h4>Bet # {$bet->pkey}</h4>
		<div class='mb-4'>
			<label class='form-label' for='amount' style='margin-left: 0px;'>Wager Amount</label>
			<input type='number' id='amount' name='amount' class='form-control text-right' placeholder='Wager Amount' value='{$bet->amount}' min='1' max='1000' step='1'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='type' style='margin-left: 0px;'>WIN/PLACE/SHOW</label>
			<select id='type' name='type' class='form-control'>
				<option value='WIN'{$sel_win}>WIN</option>
				<option value='PLACE'{$sel_place}>PLACE</option>
				<option value='SHOW'{$sel_show}>SHOW</option>
			</select>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='entry_id' style='margin-left: 0px;'>Entry</label>
			<select id='entry_id' name='entry_id' class='form-control'>
				$user_list
			</select>
		</div>
		<div class='mb-4{$protected}'>
			<label class='form-label' for='paid' style='margin-left: 0px;'>Paid?</label>
			<select id='paid' name='paid' class='form-control'>
				<option value='0'>No</option>
				<option value='1'$sel_paid>Yes</option>
			</select>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button'>Submit</button>
		</div>
	</form>
</div>
</main>
</div>";
?>