<?php $this->load->view('crudjs/header'); ?>

<div class="card">
    <div class="header-actions">
        <h2>All Records</h2>
        <button type="button" class="btn btn-success" onclick="openCreateModal()">+ Create New Record</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
                <th style="width: 100px;">Image</th>
                <th>Title</th>
                <th>Author</th>
                <th style="width: 250px;">Article Preview</th>
                <th style="width: 200px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<?php $this->load->view('crudjs/footer'); ?>
