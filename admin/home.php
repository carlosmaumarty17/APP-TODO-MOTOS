<?php
// Obtener datos para los gráficos

// 1. Estadísticas de órdenes
$status_mapping = [
    0 => 'Pendientes',
    1 => 'Confirmados', 
    2 => 'Para Envío',
    3 => 'En Camino',
    4 => 'Entregados',
    5 => 'Cancelados'
];

// Inicializar el array de datos con ceros
$orders_data = array_fill(0, count($status_mapping), 0);
$status_labels = array_values($status_mapping);
$total_orders = 0;

// Obtener los conteos de la base de datos
$result = $conn->query("
    SELECT status, COUNT(*) as total 
    FROM `order_list` 
    WHERE status IN (".implode(',', array_keys($status_mapping)).")
    GROUP BY status
");

// Verificar si hay resultados
if($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $status = (int)$row['status'];
        if(isset($status_mapping[$status])) {
            $orders_data[$status] = (int)$row['total'];
            $total_orders += (int)$row['total'];
        }
    }
}

// 2. Total de clientes registrados
$clientes_result = $conn->query("SELECT COUNT(*) as total FROM `client_list` WHERE status = 1 AND delete_flag = 0")->fetch_assoc();
$total_clientes = $clientes_result['total'];

// 3. Total de productos en inventario
$productos_result = $conn->query("SELECT COUNT(*) as total FROM `product_list` WHERE status = 1 AND delete_flag = 0")->fetch_assoc();
$total_productos = $productos_result['total'];

// 4. Total de servicios disponibles
$servicios_result = $conn->query("SELECT COUNT(*) as total FROM `service_list` WHERE status = 1 AND delete_flag = 0")->fetch_assoc();
$total_servicios = $servicios_result['total'];

// 5. Total de mecánicos activos
$mecanicos_result = $conn->query("SELECT COUNT(*) as total FROM `mechanics_list` WHERE status = 1")->fetch_assoc();
$total_mecanicos = $mecanicos_result['total'];

// 6. Total de marcas
$marcas_result = $conn->query("SELECT COUNT(*) as total FROM `brand_list` WHERE delete_flag = 0")->fetch_assoc();
$total_marcas = $marcas_result['total'];

// 7. Total de categorías
$categorias_result = $conn->query("SELECT COUNT(*) as total FROM `categories` WHERE status = 1")->fetch_assoc();
$total_categorias = $categorias_result['total'];

// 8. Servicios más solicitados
$service_labels = [];
$service_values = [];
$service_percentages = [];
$total_requests = 0;

// Primero obtenemos el total de solicitudes
$total_result = $conn->query("SELECT COUNT(*) as total FROM service_requests");
if($total_result) {
    $total_requests = (int)$total_result->fetch_assoc()['total'];
}

// Consulta para obtener los servicios más solicitados
$query = "SELECT 
            service_type as service_name, 
            COUNT(id) as request_count
          FROM service_requests 
          WHERE service_type != ''
          GROUP BY service_type
          ORDER BY request_count DESC
          LIMIT 10";

$services_result = $conn->query($query);

if($services_result && $services_result->num_rows > 0) {
    while($row = $services_result->fetch_assoc()) {
        $count = (int)$row['request_count'];
        $percentage = $total_requests > 0 ? round(($count / $total_requests) * 100, 1) : 0;
        
        $service_labels[] = $row['service_name'];
        $service_values[] = $count;
        $service_percentages[] = $percentage;
    }
}

// Si no hay datos, mostramos un mensaje
if(empty($service_labels)) {
    $service_labels = ['No hay datos disponibles'];
    $service_values = [0];
    $service_percentages = [0];
}
?>

<h1 class="mb-4">Panel de Control - <?php echo $_settings->info('name') ?></h1>
<hr>
<div class="row">
          <!-- Tarjeta de Clientes -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-users"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Clientes Registrados</span>
                <span class="info-box-number">
                  <?php echo number_format($total_clientes); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Productos -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-success elevation-1"><i class="fas fa-boxes"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Productos en Inventario</span>
                <span class="info-box-number">
                  <?php echo number_format($total_productos); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Servicios -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-info elevation-1"><i class="fas fa-tools"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Servicios Disponibles</span>
                <span class="info-box-number">
                  <?php echo number_format($total_servicios); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Órdenes Totales -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-warning elevation-1"><i class="fas fa-shopping-cart"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Órdenes Totales</span>
                <span class="info-box-number">
                  <?php echo number_format($total_orders); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Mecánicos -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-danger elevation-1"><i class="fas fa-user-cog"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Mecánicos Activos</span>
                <span class="info-box-number">
                  <?php echo number_format($total_mecanicos); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Marcas -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-secondary elevation-1"><i class="fas fa-tags"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Marcas Registradas</span>
                <span class="info-box-number">
                  <?php echo number_format($total_marcas); ?>
                </span>
              </div>
            </div>
          </div>

          <!-- Tarjeta de Categorías -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-gradient-dark elevation-1"><i class="fas fa-list"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total de Marcas</span>
                <span class="info-box-number">
                  <?php 
                    $inv = $conn->query("SELECT COUNT(*) as total FROM brand_list where delete_flag = 0 ")->fetch_assoc()['total'];
                    echo number_format($inv);
                  ?>
                  <?php ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
              <span class="info-box-icon bg-light elevation-1"><i class="fas fa-th-list"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Total de Categorías</span>
                <span class="info-box-number">
                  <?php 
                    $inv = $conn->query("SELECT COUNT(*) as total FROM categories where delete_flag = 0 ")->fetch_assoc()['total'];
                    echo number_format($inv);
                  ?>
                  <?php ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users-cog"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Mecánicos</span>
                <span class="info-box-number">
                  <?php 
                    $mechanics = $conn->query("SELECT COUNT(*) as total FROM `mechanics_list` where status = '1' ")->fetch_assoc()['total'];
                    echo number_format($mechanics);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <!-- /.col -->

          <!-- fix for small devices only -->
          <div class="clearfix hidden-md-up"></div>

          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-success elevation-1"><i class="fas fa-th-list"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Servicios</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `service_list` where status = 1 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-users"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Clientes Registrados</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `client_list` where status = 1 and delete_flag = 0 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-secondary elevation-1"><i class="fas fa-tasks"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Órdenes Pendientes</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `order_list` where status = 0 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-primary elevation-1"><i class="fas fa-tasks"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Órdenes Confirmadas</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `order_list` where status = 1 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-success elevation-1"><i class="fas fa-tasks"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Órdenes para Envío</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `order_list` where status = 2 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-warning elevation-1"><i class="fas fa-tasks"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Órdenes en Camino</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `order_list` where status = 3 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-success elevation-1"><i class="fas fa-tasks"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Órdenes Entregadas</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `order_list` where status = 4 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-gradient-danger elevation-1"><i class="fas fa-tasks"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Órdenes Canceladas</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `order_list` where status = 5 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
          <div class="col-12 col-sm-6 col-md-3">
            <div class="shadow info-box mb-3">
              <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-file-invoice"></i></span>

              <div class="info-box-content">
                <span class="info-box-text">Solicitudes Finalizadas</span>
                <span class="info-box-number">
                <?php 
                    $services = $conn->query("SELECT COUNT(*) as total FROM `service_requests` where status = 3 ")->fetch_assoc()['total'];
                    echo number_format($services);
                  ?>
                </span>
              </div>
              <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
          </div>
        </div>

        <!-- Sección de Gráficos -->
        <div class="row mt-4">
          <!-- Gráfico de estados de pedidos -->
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Estadísticas de Pedidos</h3>
              </div>
              <div class="card-body">
                <canvas id="ordersChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
              </div>
            </div>
          </div>

          <!-- Gráfico de servicios más solicitados -->
          <div class="col-md-6">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Servicios Más Solicitados</h3>
              </div>
              <div class="card-body">
                <canvas id="servicesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Incluir Chart.js -->
        <script src="<?php echo base_url ?>plugins/chart.js/Chart.min.js"></script>
        <script>
        // Depuración - Ver datos
        console.log('Etiquetas:', <?php echo json_encode($status_labels); ?>);
        console.log('Datos:', <?php echo json_encode($orders_data); ?>);
        
        // Gráfico de estados de pedidos
        var ordersCanvas = document.getElementById('ordersChart');
        if (!ordersCanvas) {
            console.error('No se encontró el elemento con ID ordersChart');
        } else {
            var ordersCtx = ordersCanvas.getContext('2d');
            var ordersChart = new Chart(ordersCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($status_labels); ?>,
                    datasets: [{
                        label: 'Número de Pedidos',
                        data: <?php echo json_encode($orders_data); ?>,
                        backgroundColor: [
                            'rgba(108, 117, 125, 0.7)',  // Pendientes - Gris
                            'rgba(0, 123, 255, 0.7)',    // Confirmados - Azul
                            'rgba(40, 167, 69, 0.7)',    // Para Envío - Verde
                            'rgba(255, 193, 7, 0.7)',    // En Camino - Amarillo
                            'rgba(23, 162, 184, 0.7)',   // Entregados - Cian
                            'rgba(220, 53, 69, 0.7)'     // Cancelados - Rojo
                        ],
                        borderColor: [
                            'rgba(108, 117, 125, 1)',
                            'rgba(0, 123, 255, 1)',
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(23, 162, 184, 1)',
                            'rgba(220, 53, 69, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1,
                                precision: 0
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var label = dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                var value = dataset.data[tooltipItem.index];
                                label += value;
                                
                                // Calcular porcentaje
                                var total = 0;
                                dataset.data.forEach(function(v) {
                                    total += v;
                                });
                                
                                if (total > 0) {
                                    var percentage = Math.round((value / total) * 100);
                                    label += ' (' + percentage + '% del total)';
                                }
                                
                                return label;
                            }
                        }
                    },
                    legend: {
                        display: false
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });

        } // Fin del if-else de ordersChart
        
        // Datos para el gráfico de servicios
        var serviceLabels = <?php echo json_encode($service_labels); ?>;
        var serviceValues = <?php echo json_encode($service_values); ?>;
        var servicePercentages = <?php echo json_encode($service_percentages); ?>;
        
        // Crear etiquetas que incluyan cantidad y porcentaje
        var labelsWithData = [];
        for(var i = 0; i < serviceLabels.length; i++) {
            labelsWithData.push(serviceLabels[i] + '\n' + serviceValues[i] + ' (' + servicePercentages[i] + '%)');
        }
        
        // Colores para las barras
        var backgroundColors = [
            'rgba(255, 99, 132, 0.7)',    // Rojo
            'rgba(54, 162, 235, 0.7)',    // Azul
            'rgba(255, 206, 86, 0.7)',    // Amarillo
            'rgba(75, 192, 192, 0.7)',    // Verde agua
            'rgba(153, 102, 255, 0.7)',   // Morado
            'rgba(255, 159, 64, 0.7)',    // Naranja
            'rgba(199, 199, 199, 0.7)',   // Gris
            'rgba(83, 102, 255, 0.7)',    // Azul índigo
            'rgba(40, 167, 69, 0.7)',     // Verde
            'rgba(220, 53, 69, 0.7)'      // Rojo oscuro
        ];
        
        // Si hay más servicios que colores, repetimos la paleta
        var serviceColors = [];
        for(var i = 0; i < serviceLabels.length; i++) {
            serviceColors.push(backgroundColors[i % backgroundColors.length]);
        }
        
        // Verificar si el elemento del gráfico existe
        var servicesCanvas = document.getElementById('servicesChart');
        if (!servicesCanvas) {
            console.error('No se encontró el elemento con ID servicesChart');
        } else {
            // Verificar si hay datos para mostrar
            if (serviceValues.length === 0 || serviceValues[0] === 0) {
                servicesCanvas.parentNode.innerHTML = '<div class="text-center p-4">No hay datos de servicios disponibles</div>';
            } else {
                var servicesCtx = servicesCanvas.getContext('2d');
                try {
                    var servicesChart = new Chart(servicesCtx, {
                        type: 'bar',
                        data: {
                            labels: serviceLabels,
                            datasets: [{
                                label: 'Número de Solicitudes',
                                data: serviceValues,
                                backgroundColor: serviceColors,
                                borderColor: serviceColors.map(function(color) { 
                                    return color.replace('0.7', '1'); 
                                }),
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                yAxes: [{
                                    ticks: {
                                        beginAtZero: true,
                                        stepSize: 1,
                                        precision: 0
                                    }
                                }],
                                xAxes: [{
                                    ticks: {
                                        autoSkip: false,
                                        maxRotation: 45,
                                        minRotation: 45
                                    }
                                }]
                            },
                            legend: {
                                display: false
                            },
                            tooltips: {
                                callbacks: {
                                    label: function(tooltipItem, data) {
                                        var dataset = data.datasets[tooltipItem.datasetIndex];
                                        var label = dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        var value = dataset.data[tooltipItem.index];
                                        var percentage = servicePercentages[tooltipItem.index] || 0;
                                        return label + value + ' solicitudes (' + percentage + '% del total)';
                                    }
                                }
                            },
                            animation: {
                                duration: 1000,
                                easing: 'easeOutQuart'
                            }
                        }
                    });
                    console.log('Gráfico de servicios inicializado correctamente');
                } catch (error) {
                    console.error('Error al inicializar el gráfico de servicios:', error);
                    servicesCanvas.parentNode.innerHTML = '<div class="text-center p-4">Error al cargar el gráfico de servicios</div>';
                }
        }
        } // Fin del else
        </script>
