<?php
global $session, $dbh;

$is_admin = $session->user->isAdmin();
$protected = ($is_admin) ? "" : " hidden";

$tr = "";
$sth = $dbh->query("SELECT
	r.pkey,
	r.timestamp,
	u.pkey - 1 as num,
	u.first_name,
	u.last_name,
	r.bet_type,
	r.points
FROM result r
INNER JOIN user u on r.user_id = u.pkey
ORDER BY r.timestamp DESC");
while ($row = $sth->fetch(PDO::FETCH_OBJ))
{
	$time = date("h:m A", $row->timestamp); 

	$tr .= "<tr>
		<td class='text-right'>{$row->pkey}</td>
		<td class='text-right'>{$time}</td>
		<td class='text-left'>($row->num) {$row->first_name}</td>
		<td class='text-left'>{$row->bet_type}</td>
		<td class='text-right'>{$row->points}</td>
		<td class='text-right{$protected}'>
			<a class='btn btn-primary btn-xs' href='index.php?act=edit&target=Result&pkey={$row->pkey}'>Edit</a>
			<a class='btn btn-danger btn-xs' href='index.php?act=delete&target=Result&pkey={$row->pkey}'>Delete</a>
		</td>
	</tr>";
}

?>
<div role='main' class='container'>
	<div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
		<h3>People Say I'm Revengful. We'll See About That</h3>
	</div>
	<div class='alert'>
		<a class='btn btn-primary px-6 <?php echo $protected; ?>' href='index.php?target=Result&pkey=0&act=edit'>Add Result</a>
	</div>
	<table id='all_bets' class='data stripe'>
		<thead>
			<tr>
				<th class='text-right'>Result #</th>
				<th class='text-right'>Time</th>
				<th class='text-left'>Entry</th>
				<th class='text-left'>Position</th>
				<th class='text-right'>Points</th>
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
		order: [[2, 'asc']]
    });
});
</script>