<style>
    table td,table th{
        padding: 3px !important;
    }
</style>
<?php 
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] :  date("Y-m-d",strtotime(date("Y-m-d")." -7 days")) ;
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] :  date("Y-m-d") ;
?>
<div class="card card-primary card-outline">
    <div class="card-header">
        <h5 class="card-title">Reporte de Órdenes de Servicio</h5>
    </div>
    <div class="card-body">
        <form id="filter-form">
            <div class="row align-items-end">
                <div class="form-group col-md-3">
                    <label for="date_start">Fecha de Inicio</label>
                    <input type="date" class="form-control form-control-sm" name="date_start" value="<?php echo date("Y-m-d",strtotime($date_start)) ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="date_start">Fecha de Fin</label>
                    <input type="date" class="form-control form-control-sm" name="date_end" value="<?php echo date("Y-m-d",strtotime($date_end)) ?>">
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-flat btn-block btn-primary btn-sm"><i class="fa fa-filter"></i> Filtrar</button>
                </div>
                <div class="form-group col-md-1">
                    <button class="btn btn-flat btn-block btn-success btn-sm" type="button" id="printBTN"><i class="fa fa-print"></i> Imprimir</button>
                </div>
            </div>
        </form>
        <hr>
        <div id="printable">
            <div>
                <h4 class="text-center m-0"><?php echo $_settings->info('name') ?></h4>
                <h3 class="text-center m-0"><b>Reporte de Órdenes</b></h3>
                <p class="text-center m-0">Fecha entre <?php echo $date_start ?> y <?php echo $date_end ?></p>
                <hr>
            </div>
            <table class="table table-bordered">
                <colgroup>
                    <col width="5%">
                    <col width="20%">
                    <col width="20%">
                    <col width="20%">
                    <col width="15%">
                    <col width="20%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha y Hora</th>
                        <th>Código Ref.</th>
                        <th>Cliente</th>
                        <th>Monto Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                        $mechanic = $conn->query("SELECT * FROM mechanics_list");
                        $result = $mechanic->fetch_all(MYSQLI_ASSOC);
                        $mech_arr = array_column($result,'name','id');
                        $where = "where date(o.date_created) between '{$date_start}' and '{$date_end}'";
                        $qry = $conn->query("SELECT o.*,CONCAT(c.lastname,', ',c.firstname,' ',c.middlename) as fullname from order_list o inner join client_list c on o.client_id = c.id {$where} order by unix_timestamp(o.date_created) desc");
                        while($row = $qry->fetch_assoc()):
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $i++ ?></td>
                        <td><?php echo $row['date_created'] ?></td>
                        <td><?php echo $row['ref_code'] ?></td>
                        <td><?php echo $row['fullname'] ?></td>
                        <td class="text-right"><?= number_format($row['total_amount'],2) ?></td>
                        <td class='text-center' id='status_<?php echo $row['id'] ?>'>
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
                    </tr>
                    <?php endwhile; ?>
                    <?php if($qry->num_rows <= 0): ?>
                    <tr>
                        <td class="text-center" colspan="6">No hay datos...</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<noscript>
    <style>
        .m-0{
            margin:0;
        }
        .text-center{
            text-align:center;
        }
        .text-right{
            text-align:right;
        }
        .table{
            border-collapse:collapse;
            width: 100%;
        }
        .table tr,.table td,.table th{
            border:1px solid gray;
        }
    </style>
</noscript>
<script>
    $(function(){
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
                                // Efecto de actualización suave
                                statusCell.fadeOut(200, function() {
                                    $(this).html(newStatus).fadeIn(200);
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

        // Actualizar estados cada 10 segundos
        setInterval(updateOrderStatus, 10000);

        // También actualizar cuando se hace clic en el botón de imprimir/actualizar
        $('#printBTN').on('click', function() {
            updateOrderStatus();
        });

        // Actualizar estados al cargar la página
        updateOrderStatus();

        // Manejar el filtro de fechas
        $('#filter-form').submit(function(e) {
            e.preventDefault()
            location.href = "./?page=report/orders&date_start="+$('[name="date_start"]').val()+"&date_end="+$('[name="date_end"]').val()
        })

        // Manejar la impresión
        $('#printBTN').click(function(){
            var rep = $('#printable').clone();
            var ns = $('noscript').clone().html();
            start_loader()
            rep.prepend(ns)
            var nw = window.document.open('','_blank','width=900,height=600')
                nw.document.write(rep.html())
                nw.document.close()
                nw.print()
                setTimeout(function(){
                    nw.close()
                    end_loader()
                },500)
        })
    })
</script>