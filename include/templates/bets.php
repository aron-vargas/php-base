<?php
global $session, $dbh;

$is_admin = $session->user->isAdmin();
$protected = ($is_admin) ? "" : " hidden";
$can_bet = ($session->user->pkey > 0) ? "" : "hidden";

$tr = "";
$sth = $dbh->query("SELECT
	b.pkey,
	b.timestamp,
	u.first_name,
	u.last_name,
	u.avatar,
	b.type,
	b.game,
	b.amount,
	e.pkey - 1 as num,
	e.nickname,
	b.paid
FROM bet b
INNER JOIN user u on b.user_id = u.pkey
INNER JOIN user e on b.entry_id = e.pkey
ORDER BY b.timestamp DESC");
while ($row = $sth->fetch(PDO::FETCH_OBJ))
{
	$amount = number_format($row->amount, 0);
	$time = date("h:m A", $row->timestamp); 
	$paid = ($row->paid) ? "Yes" : "No";

	$tr .= "<tr>
		<td class='text-right'>{$row->pkey}</td>
		<td class='text-right'>{$time}</td>
		<td class='text-left'>{$row->first_name} {$row->last_name}<span class='rounded-circle avatar-sm float-start {$row->avatar}'>&nbsp;</span></td>
		<td class='text-left'>{$row->type}</td>
		<td class='text-right'>{$amount}</td>
		<td class='text-left'>($row->num) {$row->nickname}</td>
		<td class='text-right'>$paid</td>
		<td class='text-right{$protected}'><a class='btn btn-primary btn-xs' href='index.php?act=edit&target=Bet&pkey={$row->pkey}&v=bet_edit'>Edit</a></td>
	</tr>";
}

?>
<div role='main' class='container'>
	<div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
		<h3>Here's The Mane Event</h3>
	</div>
	<div class='alert text-center<?php echo $can_bet; ?>'>
		<a class='btn btn-warning px-5' href='index.php?target=Bet&act=edit&pkey=0'><b>Place a Bet!</b></a>
	</div>
	<table id='all_bets' class='data stripe'>
		<thead>
			<tr>
				<th class='text-right'>BET #</th>
				<th class='text-right'>Time</th>
				<th class='text-left'>Better Name</th>
				<th class='text-left'>Type</th>
				<th class='text-right'>Amount</th>
				<th class='text-left'>Entry</th>
				<th class='text-left'>Paid</th>
				<th class='text-right<?php echo $protected; ?>'>Edit</th>
			</tr>
		</thead>
		<tbody>
			<?php echo $tr; ?>
		</tbody>
	</table>
</div>
<script type='text/javascript'>
// Shorthand for $( document ).ready()
$(function() {

	$('.data').DataTable({
        paging: false,
        info: false,
    });
});
</script>