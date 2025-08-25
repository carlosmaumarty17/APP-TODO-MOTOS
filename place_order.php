<?php
require_once('config.php');
include_once('inc/header.php');
?>
<div class="content py-5 mt-3">
    <div class="container">
        <div class="card card-outline card-dark shadow rounded-0">
            <div class="card-header">
                <h4 class="card-title">Realizar Pedido</h4>
            </div>
            <div class="card-body">
                <form action="" id="place_order">
                    <input type="hidden" name="reference" id="reference" value="<?= 'ORD_'.time().'_'.$_settings->userdata('id') ?>">
                    <input type="hidden" name="payment_status" id="payment_status" value="pending">
                    
                    <div class="form-group">
                        <label for="delivery_address" class="control-label">Dirección de Entrega</label>
                        <textarea name="delivery_address" id="delivery_address" class="form-control form-control-sm rounded-0" rows="4" required><?= $_settings->userdata('address') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label">Método de Pago</label>
                        <div class="pl-3">
                            <div class="icheck-primary d-inline">
                                <input type="radio" id="payment_method_wompi" name="payment_method" value="wompi" checked>
                                <label for="payment_method_wompi">
                                    <img src="https://wompi.co/wp-content/uploads/2020/10/logo-wompi-1.svg" alt="Wompi" style="height: 30px; margin-left: 10px;">
                                    <span class="ml-2">Pagar con Wompi (Tarjeta, PSE, Bancolombia, etc.)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div id="order-summary" class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Resumen del Pedido</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">$0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Envío:</span>
                                <span id="shipping">$0</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between font-weight-bold">
                                <span>Total:</span>
                                <span id="total">$0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group text-right">
                        <a href="./?p=cart" class="btn btn-flat btn-default"><i class="fa fa-arrow-left"></i> Volver al Carrito</a>
                    </div>
                    
                    <!-- Botón de pago movido aquí -->
                    <div id="wompi-widget-container" class="mb-3 mt-3">
                        <button type="button" id="wompi-pay-button" class="btn btn-primary btn-lg btn-block py-3">
                            <i class="fas fa-credit-card mr-2"></i> Confirmar tu pago
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Cargar el script de Wompi con manejo de CORS -->
<script>
    // Función para cargar el script de Wompi dinámicamente
    function loadWompiScript() {
        return new Promise((resolve, reject) => {
            if (window.WompiCheckout) {
                console.log('Wompi ya está cargado');
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://checkout.wompi.co/widget.js';
            script.async = true;
            script.defer = true;
            
            script.onload = () => {
                console.log('Script de Wompi cargado correctamente');
                if (window.WompiCheckout) {
                    resolve();
                } else {
                    reject(new Error('WompiCheckout no está disponible después de cargar el script'));
                }
            };
            
            script.onerror = (error) => {
                console.error('Error al cargar el script de Wompi:', error);
                reject(new Error('No se pudo cargar el script de Wompi'));
            };
            
            document.head.appendChild(script);
        });
    }
    
    // Cargar Wompi cuando el documento esté listo
    document.addEventListener('DOMContentLoaded', () => {
        loadWompiScript().catch(error => {
            console.error('Error al cargar Wompi:', error);
        });
    });
</script>
<script>
    $(function(){
        // Función para formatear moneda
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-CO', { 
                style: 'currency', 
                currency: 'COP',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        }

        // Función para cargar el resumen del carrito
        function loadCartSummary() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            let subtotal = 0;
            
            // Calcular subtotal
            cart.forEach(item => {
                subtotal += parseFloat(item.price) * parseInt(item.quantity);
            });
            
            // Calcular envío (ejemplo: $5.000 para pedidos menores a $100.000)
            const shipping = subtotal > 0 && subtotal < 100000 ? 5000 : 0;
            const total = subtotal + shipping;
            
            // Actualizar la interfaz
            $('#subtotal').text(formatCurrency(subtotal));
            $('#shipping').text(formatCurrency(shipping));
            $('#total').text(formatCurrency(total));
            
            return total;
        }
        
        // Cargar resumen del carrito al iniciar
        const totalAmount = loadCartSummary();
        
        // Función para cargar el script de Wompi
        function loadWompiScript(callback) {
            if (typeof WompiCheckout !== 'undefined') {
                if (callback) callback();
                return;
            }
            
            if (!document.querySelector('script[src*="wompi.co/widget.js"]')) {
                const script = document.createElement('script');
                script.src = 'https://checkout.wompi.co/widget.js';
                script.async = true;
                script.onload = function() {
                    console.log('Script de Wompi cargado correctamente');
                    if (callback) callback();
                };
                script.onerror = function() {
                    console.error('Error al cargar el script de Wompi');
                    alert_toast('Error al cargar el sistema de pagos. Por favor recarga la página.', 'error');
                };
                document.head.appendChild(script);
            } else if (callback) {
                // Si el script ya está cargando, esperar a que termine
                const checkWompi = setInterval(() => {
                    if (typeof WompiCheckout !== 'undefined') {
                        clearInterval(checkWompi);
                        callback();
                    }
                }, 100);
            }
        }

        // Función para inicializar el widget de Wompi
        function initWompiWidget() {
            console.log('Inicializando botón de pago...');
            
            // Configurar el botón de pago
            $('#wompi-pay-button').off('click').on('click', async function(e) {
                e.preventDefault();
                console.log('Botón de pago clickeado');
                
                // Validar dirección
                if ($('#delivery_address').val().trim() === '') {
                    alert_toast('Por favor ingresa una dirección de entrega', 'error');
                    return false;
                }
                
                // Mostrar mensaje de carga
                start_loader();
                
                try {
                    // Verificar si Wompi está disponible
                    if (typeof WompiCheckout === 'undefined') {
                        console.log('WompiCheckout no está disponible, cargando script...');
                        // Cargar el script de Wompi
                        await loadWompiScript();
                        console.log('Script de Wompi cargado, procesando pedido...');
                    } else {
                        console.log('WompiCheckout ya está disponible, procesando pedido...');
                    }
                    
                    // Procesar el pedido
                    processOrder(true);
                    
                } catch (error) {
                    console.error('Error al cargar Wompi:', error);
                    alert_toast('Error al cargar el sistema de pagos. Por favor inténtalo de nuevo.', 'error');
                    end_loader();
                }
            });
            
            // Precargar el script de Wompi en segundo plano
            loadWompiScript()
                .then(() => {
                    console.log('Wompi está listo para usarse');
                    window.wompiReady = true;
                })
                .catch(error => {
                    console.error('Error al precargar Wompi:', error);
                });
        }
        
        // Inicializar el widget cuando el documento esté listo
        initWompiWidget();

        // Manejar el envío del formulario
        $('#place_order').on('submit', function(e) {
            e.preventDefault();
            // El envío ahora se maneja directamente desde el botón de pago
            return false;
        });
        
        // Función para abrir el widget de Wompi
        function openWompiCheckout(resp) {
            console.log('Abriendo Wompi con referencia:', resp.reference);
            console.log('Datos de la respuesta:', JSON.stringify(resp, null, 2));
            
            // Verificar si el script de Wompi está cargado
            if (typeof WompiCheckout === 'undefined') {
                console.error('WompiCheckout no está definido');
                alert_toast('Error al cargar el sistema de pagos. Por favor recarga la página.', 'error');
                end_loader();
                return;
            }
            
            try {
                // Verificar que los datos requeridos estén presentes
                if (!resp.amount || !resp.reference) {
                    throw new Error('Faltan datos requeridos para abrir Wompi');
                }
                
                // Configuración del widget
                const wompiConfig = {
                    public_key: resp.public_key || 'pub_test_7uCFxW8WrWZnhyaXf3sH9y99nXis5KxI',
                    currency: resp.currency || 'COP',
                    amount_in_cents: parseInt(resp.amount),
                    reference: resp.reference.toString(),
                    redirect_url: resp.redirect_url || (_base_url_ + '?p=my_orders'),
                    customer_information: {
                        email: '<?= $_settings->userdata('email') ?>',
                        full_name: '<?= $_settings->userdata('firstname').' '.$_settings->userdata('lastname') ?>',
                        phone_number: '<?= $_settings->userdata('contact') ?>'
                    },
                    onOpen: function() {
                        console.log('Widget de Wompi abierto exitosamente');
                    },
                    onClose: function() {
                        console.log('Widget de Wompi cerrado');
                    },
                    onError: function(error) {
                        console.error('Error en el widget de Wompi:', error);
                        alert_toast('Error al abrir el sistema de pagos: ' + (error.message || 'Error desconocido'), 'error');
                        end_loader();
                    }
                };
                
                console.log('Configuración de Wompi:', JSON.stringify(wompiConfig, null, 2));
                
                // Crear instancia del widget de Wompi
                const wompiCheckout = new WompiCheckout(wompiConfig);
                
                console.log('Widget de Wompi creado, abriendo...');
                
                // Abrir el checkout
                wompiCheckout.open({
                    amount_in_cents: parseInt(resp.amount),
                    reference: resp.reference.toString()
                });
                
                // Limpiar el carrito después de un tiempo
                setTimeout(() => {
                    localStorage.removeItem('cart');
                }, 300000); // 5 minutos
                
            } catch (error) {
                console.error('Error al abrir Wompi:', error);
                alert_toast('Error al abrir el sistema de pagos: ' + error.message, 'error');
            } finally {
                end_loader();
            }
        }
        
        // Función para procesar el pedido
        function processOrder(withWompi = false) {
            const formData = new FormData($('#place_order')[0]);
            
            // Agregar el carrito al formData
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            formData.append('cart', JSON.stringify(cart));
            
            console.log('Procesando pedido con Wompi:', withWompi);
            
            // Mostrar loader
            start_loader();
            
            // Enviar datos al servidor
            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=place_order',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                dataType: 'json',
                error: function(err) {
                    console.error('Error en la petición:', err);
                    alert_toast('Ocurrió un error al procesar el pedido', 'error');
                    end_loader();
                },
                success: function(resp) {
                    console.log('Respuesta del servidor:', resp);
                    
                    if (resp.status === 'success') {
                        if (withWompi) {
                            // Intentar abrir Wompi directamente
                            if (typeof WompiCheckout !== 'undefined') {
                                openWompiCheckout(resp);
                            } else {
                                // Si Wompi no está cargado, cargarlo y luego abrirlo
                                loadWompiScript(function() {
                                    openWompiCheckout(resp);
                                });
                            }
                        } else {
                            // Para otros métodos de pago
                            localStorage.removeItem('cart');
                            location.href = _base_url_ + '?p=my_orders';
                        }
                    } else {
                        let errorMsg = resp.msg || 'Ocurrió un error al procesar el pedido';
                        alert_toast(errorMsg, 'error');
                        console.error(resp);
                    }
                    end_loader();
                }
            });
        }
        
        // Manejar cambios en el método de pago
        $('input[name="payment_method"]').on('change', function() {
            const method = $(this).val();
            if (method === 'wompi') {
                $('#wompi-widget-container').show();
            } else {
                $('#wompi-widget-container').hide();
            }
        });
    });
</script>