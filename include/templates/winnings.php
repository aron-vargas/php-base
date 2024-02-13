<?php
global $session, $dbh;

$is_admin = $session->user->isAdmin();
$protected = ($is_admin) ? "" : " hidden";

$tr = "";

$base_result = new stdClass;
$base_result->user_id = 0;
$base_result->nickname = '--No one--';
$base_result->points = 0;
# Fill at least 3
$resuls[0] = $base_result;
$resuls[1] = $base_result;
$resuls[2] = $base_result;

# Get Top Three points leaders
$i = 0;
$sth = $dbh->query("SELECT
	r.user_id,
	u.nickname,
	SUM(points) as points
FROM result r
INNER JOIN user u on r.user_id = u.pkey
GROUP BY r.user_id,	u.nickname
ORDER BY 3 DESC
LIMIT 3");
while ($res = $sth->fetch(PDO::FETCH_OBJ))
{
	$resuls[$i++] = $res;
}

# Get the totals from the bet table
$base_result = new stdClass;
$base_result->total_bets = 0;
$base_result->total_bet_amount = 0;
$all_bets[Bet::$TYPE_WIN] = $base_result;
$all_bets[Bet::$TYPE_PLACE] = $base_result;
$all_bets[Bet::$TYPE_SHOW] = $base_result;
$sth = $dbh->query("SELECT
	b.type,
	count(*) as total_bets,
	SUM(amount) as total_bet_amount
FROM bet b
WHERE b.paid = 1
GROUP BY b.type");
while ($res = $sth->fetch(PDO::FETCH_OBJ))
{
	$all_bets[$res->type] = $res;
}

# Get the entry totals from the bet table
$base_result = new stdClass;
$base_result->entry_id = 0;
$base_result->type = "";
$base_result->total_bets = 0;
$base_result->total_bet_amount = 0;
$user_bets[Bet::$TYPE_WIN] = $base_result;
$user_bets[Bet::$TYPE_PLACE] = $base_result;
$user_bets[Bet::$TYPE_SHOW] = $base_result;
$sth = $dbh->query("SELECT
	b.entry_id,
	b.type,
	count(*) as total_bets,
	SUM(amount) as total_bet_amount
FROM bet b
WHERE b.paid = 1
GROUP BY b.entry_id, b.type");
while ($res = $sth->fetch(PDO::FETCH_OBJ))
{
	$user_bets[$res->entry_id][$res->type] = $res;
}

$tr_results = $tr_payouts = "";
foreach($resuls AS $pos => $winner)
{
	# WIN
	if (isset($user_bets[$winner->user_id][Bet::$TYPE_WIN]) && $user_bets[$winner->user_id][Bet::$TYPE_WIN]->total_bet_amount > 0)
	{
		$payout_per_dollar = $all_bets[Bet::$TYPE_WIN]->total_bet_amount / $user_bets[$winner->user_id][Bet::$TYPE_WIN]->total_bet_amount;
		$win_twod_payout = "\$ ".number_format(round(2 * $payout_per_dollar, 2),2);
	}
	else
	{
		$win_twod_payout = "No bets";
	}

	# Place
	if (isset($user_bets[$winner->user_id][Bet::$TYPE_PLACE]) && $user_bets[$winner->user_id][Bet::$TYPE_PLACE]->total_bet_amount > 0)
	{
		$payout_per_dollar = $all_bets[Bet::$TYPE_PLACE]->total_bet_amount / $user_bets[$winner->user_id][Bet::$TYPE_PLACE]->total_bet_amount;
		$place_twod_payout = "\$ ".number_format(round(2 * $payout_per_dollar, 2),2);
	}
	else
	{
		$place_twod_payout = "No bets";
	}

	# Show
	if (isset($user_bets[$winner->user_id][Bet::$TYPE_SHOW]) && $user_bets[$winner->user_id][Bet::$TYPE_SHOW]->total_bet_amount)
	{
		$payout_per_dollar = $all_bets[Bet::$TYPE_SHOW]->total_bet_amount / $user_bets[$winner->user_id][Bet::$TYPE_SHOW]->total_bet_amount;
		$show_twod_payout = "\$ ".number_format(round(2 * $payout_per_dollar, 2),2);
	}
	else
	{
		$show_twod_payout = "No bets";
	}

	$num = $winner->user_id - 1;
	if ($pos == 0)
	{
		$tr_results .= "<tr>
			<td class='text-left'>WIN</td>
			<td class='text-right'>{$num}</td>
			<td class='text-left'>{$winner->nickname}</td>
		</tr>";

		$tr_payouts .= "<tr>
			<td class='text-right'>{$win_twod_payout}</td>
			<td class='text-right'>{$place_twod_payout}</td>
			<td class='text-right'>{$show_twod_payout}</td>
		</tr>";
	}
	else if ($pos == 1)
	{
		$tr_results .= "<tr>
			<td class='text-left'>PLACE</td>
			<td class='text-right'>{$num}</td>
			<td class='text-left'>{$winner->nickname}</td>
		</tr>";

		$tr_payouts .= "<tr>
			<td class='text-right'></td>
			<td class='text-right'>{$place_twod_payout}</td>
			<td class='text-right'>{$show_twod_payout}</td>
		</tr>";
	}
	else if ($pos == 2)
	{
		$tr_results .= "<tr>
			<td class='text-left'>SHOW</td>
			<td class='text-right'>{$num}</td>
			<td class='text-left'>{$winner->nickname}</td>
		</tr>";

		$tr_payouts .= "<tr>
			<td class='text-right'></td>
			<td class='text-right'></td>
			<td class='text-right'>{$show_twod_payout}</td>
		</tr>";
	}
}

echo "
<div role='main' class='container'>
	<div class='mt-4 p-2 bg-secondary text-center text-white rounded shadow'>
		<h4>What Smells Bad? Defeat</h4>
		<div>Payouts Based on $2 Dollar Bet</div>
	</div>
	<div class='row my-3'>
		<div class='col-md-6'>
			<table id='all_bets' class='table table-info table-bordered'>
				<thead>
					<tr class='table-light'>
						<th class='text-left' colspan='3'>Results</th>
					</tr>
					<tr>
						<th class='text-left'>POS</th>
						<th class='text-right'>Entry #</th>
						<th class='text-left'>Name</th>
					</tr>
				</thead>
				<tbody>
					$tr_results
				</tbody>
			</table>
		</div>
		<div class='col-md-6'>
			<table id='all_bets' class='table table-success table-bordered'>
				<thead>
					<tr class='table-light'>
						<th class='text-left' colspan='3'>Payouts</th>
					</tr>
					<tr>
						<th class='text-right'>Win</th>
						<th class='text-right'>Place</th>
						<th class='text-right'>Show</th>
					</tr>
				</thead>
				<tbody>
					$tr_payouts
				</tbody>
			</table>
		</div>
	</div>
";

if ($is_admin)
{
	/**
	 * Go through each bet
	 * Find winners and show the amout they won
	 */
	$sth = $dbh->query("SELECT
		b.pkey,
		b.user_id,
		u.first_name,
		u.last_name,
		u.avatar,
		b.entry_id,
		e.nickname,
		b.type,
		b.amount
	FROM bet b
	INNER JOIN user u on b.user_id = u.pkey
	INNER JOIN user e on b.entry_id = e.pkey
	WHERE b.paid = 1
	ORDER BY b.pkey");
	while ($bet = $sth->fetch(PDO::FETCH_OBJ))
	{
		foreach($resuls AS $pos => $winner)
		{
			if ($winner->user_id == $bet->entry_id)
			{
				if ($bet->type == Bet::$TYPE_WIN)
				{
					if ($pos == 0) // First possition
					{
						$payout_per_dollar = $all_bets[Bet::$TYPE_WIN]->total_bet_amount / $user_bets[$winner->user_id][Bet::$TYPE_WIN]->total_bet_amount;

						$win_amount = number_format($bet->amount * $payout_per_dollar, 2);

						$tr .= "<tr>
							<td class='text-right'>{$bet->pkey}</td>
							<td class='text-left'><span class='rounded-circle avatar-sm {$bet->avatar}'>&nbsp;</span> {$bet->first_name} {$bet->last_name}</td>
							<td class='text-left'>{$bet->type}</td>
							<td class='text-left'>{$bet->nickname}</td>
							<td class='text-right'>$win_amount</td>
						</tr>";
					}
				}
				else if ($bet->type == Bet::$TYPE_PLACE)
				{
					if ($pos == 0 || $pos == 1) // First or Second possition
					{
						$payout_per_dollar = $all_bets[Bet::$TYPE_PLACE]->total_bet_amount / $user_bets[$winner->user_id][Bet::$TYPE_PLACE]->total_bet_amount;

						$win_amount = number_format($bet->amount * $payout_per_dollar, 2);

						$tr .= "<tr>
							<td class='text-right'>{$bet->pkey}</td>
							<td class='text-left'><span class='rounded-circle avatar-sm {$bet->avatar}'>&nbsp;</span> {$bet->first_name} {$bet->last_name}</td>
							<td class='text-left'>{$bet->type}</td>
							<td class='text-left'>{$bet->nickname}</td>
							<td class='text-right'>$win_amount</td>
						</tr>";
					}
				}
				else if ($bet->type == Bet::$TYPE_SHOW)
				{
					if ($pos == 0 || $pos == 1 || $pos == 2) // First or Second possition
					{
						$payout_per_dollar = $all_bets[Bet::$TYPE_SHOW]->total_bet_amount / $user_bets[$winner->user_id][Bet::$TYPE_SHOW]->total_bet_amount;

						$win_amount = number_format($bet->amount * $payout_per_dollar, 2);

						$tr .= "<tr>
							<td class='text-right'>{$bet->pkey}</td>
							<td class='text-left'><span class='rounded-circle avatar-sm {$bet->avatar}'>&nbsp;</span> {$bet->first_name} {$bet->last_name}</td>
							<td class='text-left'>{$bet->type}</td>
							<td class='text-left'>{$bet->nickname}</td>
							<td class='text-right'>$win_amount</td>
						</tr>";
					}
				}
			}
		}
	}

	echo "<div class='mt-4 p-2 bg-light text-center text-dark rounded shadow'>
		<h3>Individual Winners</h3>
	</div>
	<table id='all_bets' class='table table-bordered table-striped'>
		<thead>
			<tr>
				<th class='text-right'>Bet #</th>
				<th class='text-left'>Winner Name</th>
				<th class='text-left'>Type</th>
				<th class='text-left'>Entry Name</th>
				<th class='text-right'>Win Amount</th>
			</tr>
		</thead>
		<tbody>
			$tr
		</tbody>
	</table>";
}
echo "\n</div>\n";

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
