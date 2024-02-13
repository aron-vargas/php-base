<?php
global $session, $dbh;

$is_admin = $session->user->isAdmin();
$protected = ($is_admin) ? "" : " hidden";

$tr = "";
$sth = $dbh->query("SELECT
	u.*,
	b.*
FROM user u
INNER JOIN (
	SELECT
		b.user_id,
		COUNT(*) as bet_count,
		SUM(amount) as bet_total
	FROM bet b
	GROUP BY user_id
) b on b.user_id = u.pkey
ORDER BY u.pkey DESC");
while ($row = $sth->fetch(PDO::FETCH_OBJ))
{
	$num = $row->pkey - 1;

	$tr .= "<tr>
		<td class='text-right'>{$num}</td>
		<td class='text-left'>{$row->first_name} {$row->last_name}</td>
		<td class='text-left'>{$row->bet_count}</td>
		<td class='text-left'>{$row->bet_total}</td>
	</tr>";
}
?>

<div role='main' class='container'>
	<div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
		<h3>Ignore All The Neigh Sayers</h3>
	</div>
	<table id='user_list' class='data stripe'>
		<thead>
			<tr>
				<th class='text-right'>User #</th>
				<th class='text-left'>Full Name</th>
				<th class='text-left'>Number of Bets</th>
				<th class='text-left'>Total Bet Amount</th>
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
		order: [[2, 'asc']]
    });
});
</script>