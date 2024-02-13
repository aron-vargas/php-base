<?php

	global $session;

	$result = $session->controller->model;

	$user_list = User::OptionList($result->user_id);

	$sel_win = ($result->bet_type == Result::$TYPE_WIN) ? " selected" : "";
	$sel_place = ($result->bet_type == Result::$TYPE_PLACE) ? " selected" : "";
	$sel_show = ($result->bet_type == Result::$TYPE_SHOW) ? " selected" : "";

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
		<input type='hidden' name='pkey' value='{$result->pkey}'/>
		<input type='hidden' name='target' value='Result'/>
		<h4>Result # {$result->pkey}</h4>
		<div class='mb-4'>
			<label class='form-label' for='user_id' style='margin-left: 0px;'>Entry</label>
			<select id='user_id' name='user_id' class='form-control'>
				$user_list
			</select>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='bet_type' style='margin-left: 0px;'>WIN/PLACE/SHOW</label>
			<select id='bet_type' name='bet_type' class='form-control'>
				<option value='WIN'{$sel_win}>WIN</option>
				<option value='PLACE'{$sel_place}>PLACE</option>
				<option value='SHOW'{$sel_show}>SHOW</option>
			</select>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='game' style='margin-left: 0px;'>Game</label>
			<input type='text' id='game' name='game' class='form-control' placeholder='Game/Event' value='{$result->game}'>
		</div>
		<div class='mb-4'>
			<label class='form-label' for='points' style='margin-left: 0px;'>Points Awarded</label>
			<input type='text' id='points' name='points' class='form-control text-right' placeholder='Points Awarded' value='{$result->points}'>
		</div>
		<div class='text-center pt-1 mb-4 pb-1'>
			<button type='submit' class='btn btn-primary' type='button'>Submit</button>
		</div>
	</form>
</div>
</main>
</div>";
