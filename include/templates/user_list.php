<?php
global $session, $dbh;

$is_admin = $session->user->isAdmin();
$protected = ($is_admin) ? "" : " hidden";

$tr = "";
$sth = $dbh->query("SELECT
	u.*
FROM user u
WHERE pkey > 1
ORDER BY u.pkey DESC");
while ($row = $sth->fetch(PDO::FETCH_OBJ))
{
	$num = $row->pkey - 1;

	$tr .= "<tr>
		<td class='text-right'>{$num}</td>
		<td class='text-left'>{$row->user_name}<span class='rounded-circle avatar-sm float-start {$row->avatar}'>&nbsp;</span></td>
		<td class='text-left'>{$row->first_name} {$row->last_name}</td>
		<td class='text-left'>{$row->nickname}</td>
		<td class='text-left'>{$row->email}</td>
		<td class='text-left'>{$row->phone}</td>
		<td class='text-left'>{$row->status}</td>
		<td class='text-right{$protected}'>{$row->permissions}</td>
		<td class='text-right{$protected}'><a class='btn btn-primary btn-xs' href='index.php?act=edit&target=User&pkey={$row->pkey}&v=user_edit'>Edit</a></td>
	</tr>";
}
?>
<div role='main' class='container'>
	<div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
		<h3>There's No One Like You! Thankfully</h3>
	</div>
	<table id='user_list' class='data stripe'>
		<thead>
			<tr>
				<th class='text-right'>User #</th>
				<th class='text-left'>Username</th>
				<th class='text-left'>Full Name</th>
				<th class='text-left'>Nickname</th>
				<th class='text-left'>Email</th>
				<th class='text-left'>Phone</th>
				<th class='text-left'>Status</th>
				<th class='text-right<?php echo $protected; ?>'>Perm</th>
				<th class='text-right<?php echo $protected; ?>'>Action</th>
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