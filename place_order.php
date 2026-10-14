<?php
require_once('config.php');
include_once('inc/header.php');

// Calcular el total del carrito desde la base de datos
$total = 0;
$subtotal = 0;
$shipping = 0;

if ($_settings->userdata('id') > 0) {
    $cart = $conn->query("SELECT c.*, p.price FROM `cart_list` c inner join product_list p on c.product_id = p.id where c.client_id = '{$_settings->userdata('id')}'");
    while ($row = $cart->fetch_assoc()) {
        $subtotal += ($row['quantity'] * $row['price']);
    }

    // Sin costo de envío
    $shipping = 0;
    $total = $subtotal;
}
?>
<div class="content py-5 mt-3">
    <div class="container">
        <div class="card card-outline card-dark shadow rounded-0">
            <div class="card-header">
                <h4 class="card-title">Realizar Pedido</h4>
            </div>
            <div class="card-body">
                <form action="" id="place_order">
                    <input type="hidden" name="reference" id="reference"
                        value="<?= 'ORD_' . time() . '_' . $_settings->userdata('id') ?>">
                    <input type="hidden" name="payment_status" id="payment_status" value="pending">

                    <div class="form-group">
                        <label for="delivery_address" class="control-label">Dirección de Entrega</label>
                        <textarea name="delivery_address" id="delivery_address"
                            class="form-control form-control-sm rounded-0" rows="4"
                            required><?= $_settings->userdata('address') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="control-label">Método de Pago</label>
                        <div class="pl-3">
                            <div class="icheck-primary d-inline">
                                <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                <label for="cod">Pago Contra Entrega</label>
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
                                <span id="subtotal">$ <?= number_format($subtotal, 0, ',', '.') ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between font-weight-bold">
                                <span>Total:</span>
                                <span id="total">$ <?= number_format($total, 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group text-right">
                        <a href="./?p=cart" class="btn btn-flat btn-default"><i class="fa fa-arrow-left"></i> Volver al
                            Carrito</a>
                        <button type="submit" class="btn btn-flat btn-primary">Realizar Pedido</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $(function () {


        // Manejar el envío del formulario
        $('#place_order').submit(function (e) {
            e.preventDefault();
            var _this = $(this);
            start_loader();

            // Preparar datos
            var formData = new FormData($(this)[0]);

            $.ajax({
                url: _base_url_ + "classes/Master.php?f=place_order",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: err => {
                    console.log(err)
                    alert_toast("An error occurred", 'error');
                    end_loader();
                },
                success: function (resp) {
                    if (typeof resp == 'object' && resp.status == 'success') {
                        localStorage.removeItem('cart'); // Limpiar carrito
                        location.replace('./');
                    } else if (resp.status == 'failed' && !!resp.msg) {
                        var el = $('<div>')
                        el.addClass("alert alert-danger err-msg").text(resp.msg)
                        _this.prepend(el)
                        el.show('slow')
                        $("html, body").animate({ scrollTop: 0 }, "fast");
                        end_loader()
                    } else {
                        alert_toast("An error occurred", 'error');
                        end_loader();
                        console.log(resp)
                    }
                }
            })
        })
    })
</script>