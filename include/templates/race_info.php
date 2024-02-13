<?php
global $dbh;

$position = 1;
$tr = "";
$bars = "";
$total_points = null;

$sth = $dbh->query("SELECT
	u.pkey - 1 as num,
	u.nickname,
	sum(r.points) as points
FROM result r
INNER JOIN user u on r.user_id = u.pkey
GROUP BY 1, 2
ORDER BY 3 DESC");
while ($row = $sth->fetch(PDO::FETCH_OBJ))
{
	# Used to determine progress width
	if (is_null($total_points))
		$total_points = $row->points + 20;

	if ($position == 1)
		$img = "<img src='images/1st.png' height='20'>";
	else if ($position == 2)
		$img = "<img src='images/2nd.png' height='20'>";
	else if ($position == 3)
		$img = "<img src='images/3rd.png' height='20'>";
	else
		$img = "";

	$tr .= "<tr>
		<td class='text-right'>{$position} {$img}</td>
		<td class='text-left'>($row->num) {$row->nickname}</td>
		<td class='text-right'>{$row->points}</td>
	</tr>";

	$width = round($row->points / $total_points * 100, 0);
	$bars .= "<div class='progress mb-2'>
		<div id='pb{$position}' class='progress-bar'><span>{$row->nickname}</span></div>
		<span class='badge'><img src='images/horseman.png' height='20' style='margin-top: -5px;'></span>
	</div>
	<script type'text/javascript'>$(function() { $('#pb{$position}').css('width', '{$width}%'); })</script>";

	$position++;
}
?>

<div role='main' class='container'>
	<div class='row my-4'>
		<div class='col-md-3'>
			<div class="card">
				<div class="card-header">
					<h5>General Information</h5>
				</div>
				<div class="card-body">
					Birthday Derby
					<div class='w-50'>
						<div>Post Time: 3:30 PM</div>
						<div>Date: 8/6/2022</div>
					</div>
					<div class='w-50'>
						<div>Paricipants: 999</div>
						<div>Total Pot: $199,000.12</div>
					</div>
				</div>
			</div>
		</div>
		<div class='col-md-9'>
			<div class='p-2 mb-2 bg-light text-black text-center rounded shadow'>
				<h5>And The Winner Is...</h5>
			</div>	
	
			<table id='all_bets' class='table table-striped table-bordered'>
				<thead>
					<tr>
						<th class='text-right'>Place</th>				
						<th class='text-left'>Nickname</th>
						<th class='text-right'>Points</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $tr; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php echo $bars; ?>
</div>
