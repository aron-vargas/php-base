<?php
global $session, $dbh;

$is_admin = $session->user->isAdmin();
$protected = ($is_admin) ? "" : " hidden";

$tr = "";

# Get the totals from the bet table
$sth = $dbh->query("SELECT
	count(*) as total_bets,
	SUM(amount) as total_bet_amount
FROM bet b
WHERE b.paid = 1");
$all_bets = $row = $sth->fetch(PDO::FETCH_OBJ);

# Get the totals for each entry
$sth = $dbh->query("SELECT
	u.pkey - 1 as num,
	u.nickname,
	b.bet_count,
	b.bet_total
FROM user u
INNER JOIN (
	SELECT
		b.entry_id,
		COUNT(*) as bet_count,
		SUM(amount) as bet_total
	FROM bet b
	WHERE b.paid = 1
	GROUP BY b.entry_id
) b on b.entry_id = u.pkey
ORDER BY u.pkey");
while ($row = $sth->fetch(PDO::FETCH_OBJ))
{
	$real_odds = ($all_bets->total_bet_amount / $row->bet_total) - 1;
	$payout_per_dollar = $all_bets->total_bet_amount / $row->bet_total;

	$display_odds = float2rat(round($real_odds,1));

	$tr .= "<tr>
		<td class='text-right'>{$row->num}</td>
		<td class='text-left'>{$row->nickname}</td>
		<td class='text-left'>{$display_odds}</td>
		<td class='text-right'>{$real_odds}</td>
	</tr>";
}

?>
<div role='main' class='container'>
	<div class='mt-4 p-2 bg-secondary text-center text-white rounded shadow'>
		<h3>You Know What's Odd? Every Other Number</h3>
	</div>
	<table id='all_bets' class='data stripe'>
		<thead>
			<tr>
				<th class='text-right'>Entry #</th>
				<th class='text-left'>Entry Name</th>
				<th class='text-left'>Display Odds</th>
				<th class='text-right'>Real Odds</th>
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
<?php
function float2rat($n)
{
	$tolerance = 1.e-2;
    $h1=1; $h2=0;
    $k1=0; $k2=1;
    $b = 1/$n;
    do
	{
        $b = 1/$b;
        $a = floor($b);
        $aux = $h1; $h1 = $a*$h1+$h2; $h2 = $aux;
        $aux = $k1; $k1 = $a*$k1+$k2; $k2 = $aux;
        $b = $b-$a;
    }
	while (abs($n-$h1/$k1) > $n*$tolerance);

    return "$h1:$k1";
}
?>