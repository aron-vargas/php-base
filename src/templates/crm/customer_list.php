<?php
$tr = "";

if ($this->data)
{
    foreach ($this->data as $i => $row)
    {
        $num = $row->pkey;

        $tr .= "<tr>
            <td class='text-right'>{$num}</td>
            <td class='text-center'>{$row->account_code}</td>
            <td class='text-left'>{$row->customer_name}</td>
            <td class='text-left'>{$row->customer_status}</td>
            <td class='text-left'>{$row->customer_type}</td>
            <td class='text-left'>{$row->description}</td>
            <td class='text-center'>{$row->created_on}</td>
            <td class='text-center'>{$row->last_mod}</td>
            <td class='text-right'><a class='btn btn-primary btn-xs' href='/crm/customer/edit/{$row->pkey}'>Edit</a></td>
        </tr>";
    }
}
?>
<div role='main' class='container'>
    <div class='mt-4 text-left'>
        <a class='btn btn-primary' href="customer/0" alt="Add New Company" title="Add New Company">New</a>
    </div>
    <div class='mt-4 p-2 bg-secondary text-white text-center rounded shadow'>
        <h3>Administration: Customer listing</h3>
    </div>
    <table id='user_list' class='table table-striped table-bordered cell-border'>
        <thead>
            <tr>
                <th class='text-right'>Customer #</th>
                <th class='text-right'>Cust ID</th>
                <th class='text-left'>Company Name</th>
                <th class='text-left'>Status</th>
                <th class='text-left'>Type</th>
                <th class='text-left'>Description</th>
                <th class='text-center'>Created</th>
                <th class='text-center'>Modified</th>
                <th class='text-right'>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php echo $tr; ?>
        </tbody>
    </table>
</div>
<script type='text/javascript'>
    $(function ()
    {
        $('.table').DataTable({
            paging: false,
            info: false,
            order: [[0, 'asc']]
        });
    });
</script>