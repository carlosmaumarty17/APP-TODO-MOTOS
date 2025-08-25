<div class="card card-outline card-dark shadow rounded-0">
    <div class="card-header">
        <h3 class="card-title"><b>Lista de Órdenes</b></h3>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-stripped table-bordered">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="15%">
                    <col width="15%">
                    <col width="20%">
                    <col width="15%">
                    <col width="15%">
                </colgroup>
                <thead>
                    <tr class="bg-gradient-dark text-light">
                        <th class="text-center">#</th>
                        <th class="text-center">Fecha de Pedido</th>
                        <th class="text-center">Código Ref.</th>
                        <th class="text-center">Cliente</th>
                        <th class="text-center">Monto Total</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $orders = $conn->query("SELECT o.*,concat(c.lastname,', ', c.firstname,' ',c.middlename) as fullname FROM `order_list` o inner join client_list c on o.client_id = c.id order by o.status asc, unix_timestamp(o.date_created) desc ");
                    while($row = $orders->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="text-center"><?= $i++ ?></td>
                            <td><?= date("Y-m-d H:i", strtotime($row['date_created'])) ?></td>
                            <td><?= $row['ref_code'] ?></td>
                            <td><?= $row['fullname'] ?></td>
                            <td class="text-right"><?= number_format($row['total_amount'],2) ?></td>
                            <td class="text-center" id="status_<?php echo $row['id'] ?>">
                                <?php 
                                $status_badge = [
                                    0 => '<span class="badge badge-secondary px-3 rounded-pill">Pendiente</span>',
                                    1 => '<span class="badge badge-primary px-3 rounded-pill">Empacado</span>',
                                    2 => '<span class="badge badge-success px-3 rounded-pill">Para Envío</span>',
                                    3 => '<span class="badge badge-warning px-3 rounded-pill">En Camino</span>',
                                    4 => '<span class="badge badge-default bg-gradient-teal px-3 rounded-pill">Entregado</span>',
                                    5 => '<span class="badge badge-danger px-3 rounded-pill">Cancelado</span>'
                                ];
                                echo $status_badge[$row['status']] ?? '<span class="badge badge-secondary px-3 rounded-pill">Desconocido</span>';
                                ?>
                            </td>
                            <td class="text-center">
                                <a class="btn btn-flat btn-sm btn-default border view_data" href="./?page=orders/view_order&id=<?= $row['id'] ?>" data-id="<?= $row['id'] ?>"><i class="fa fa-eye"></i> Ver</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
    $(function(){
        // Inicializar DataTable
        var orderTable = $('.table').DataTable({
            "order": [[5, 'asc'], [1, 'desc']], // Ordenar por estado y fecha
            "columnDefs": [
                { "orderable": false, "targets": [6] } // Deshabilitar ordenación en la columna de acciones
            ]
        });
        
        $('.table th, .table td').addClass("align-middle px-2 py-1");
        
        // Función para actualizar los estados de los pedidos
        function updateOrderStatus() {
            // Obtener todos los IDs de pedidos en la tabla
            let orderIds = [];
            $('td[id^="status_"]').each(function() {
                const id = $(this).attr('id').replace('status_', '');
                orderIds.push(id);
            });

            if (orderIds.length === 0) return;

            // Hacer una sola petición para actualizar todos los estados
            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=get_orders_status',
                method: 'POST',
                data: { ids: orderIds },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.data) {
                        // Actualizar solo los estados que hayan cambiado
                        response.data.forEach(function(order) {
                            const statusBadge = [
                                '<span class="badge badge-secondary px-3 rounded-pill">Pendiente</span>',
                                '<span class="badge badge-primary px-3 rounded-pill">Empacado</span>',
                                '<span class="badge badge-success px-3 rounded-pill">Para Envío</span>',
                                '<span class="badge badge-warning px-3 rounded-pill">En Camino</span>',
                                '<span class="badge badge-default bg-gradient-teal px-3 rounded-pill">Entregado</span>',
                                '<span class="badge badge-danger px-3 rounded-pill">Cancelado</span>'
                            ];
                            
                            const statusCell = $('#status_' + order.id);
                            const currentStatus = statusCell.html().trim();
                            const newStatus = statusBadge[order.status] || '<span class="badge badge-secondary px-3 rounded-pill">Desconocido</span>';
                            
                            // Solo actualizar si el estado cambió
                            if (currentStatus !== newStatus) {
                                // Obtener la fila actual
                                const row = statusCell.closest('tr');
                                const rowData = orderTable.row(row).data();
                                
                                // Actualizar el estado en el DataTable
                                rowData[5] = newStatus;
                                orderTable.row(row).data(rowData).draw(false);
                                
                                // Efecto visual de actualización
                                statusCell.fadeOut(100, function() {
                                    $(this).html(newStatus).fadeIn(100);
                                });
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al actualizar estados:', error);
                }
            });
        }

        // Actualizar estados cada 5 segundos
        setInterval(updateOrderStatus, 5000);
        
        // Actualizar estados al cargar la página
        updateOrderStatus();
    });
</script>