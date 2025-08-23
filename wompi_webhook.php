<?php
require_once('config.php');
require_once('classes/DBConnection.php');

// Configurar el manejo de errores
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

// Función para registrar eventos en el log
function logEvent($message, $data = []) {
    $log = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    if (!empty($data)) {
        $log .= 'Data: ' . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
    }
    $log .= '----------------------------------------' . PHP_EOL;
    
    // Guardar en archivo de log
    file_put_contents(__DIR__ . '/wompi_webhook.log', $log, FILE_APPEND);
    
    // También registrar en error_log para depuración
    error_log('Wompi Webhook: ' . $message);
    if (!empty($data)) {
        error_log('Data: ' . json_encode($data));
    }
}

// Verificar que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    logEvent('Método no permitido', ['method' => $_SERVER['REQUEST_METHOD']]);
    die(json_encode(['error' => 'Método no permitido']));
}

// Obtener el payload de la notificación
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

// Verificar que el evento sea válido
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    logEvent('JSON inválido', [
        'error' => json_last_error_msg(),
        'payload' => $payload
    ]);
    die(json_encode(['error' => 'JSON inválido']));
}

// Verificar la firma del webhook (recomendado para producción)
$signature = $_SERVER['HTTP_EVENT_SIGNATURE'] ?? '';
$secret = 'prv_test_YOUR_PRIVATE_KEY'; // Reemplazar con tu private key de Wompi

// Calcular la firma esperada
$signature_calculated = hash_hmac('sha256', $payload, $secret);

// En modo desarrollo, puedes comentar esta verificación
if ($signature !== $signature_calculated) {
    http_response_code(401);
    logEvent('Firma no válida', [
        'signature_received' => $signature,
        'signature_calculated' => $signature_calculated
    ]);
    die(json_encode(['error' => 'Firma no válida']));
}

// Registrar el evento recibido
logEvent('Evento recibido', $event);

try {
    $conn = new DBConnection();
    
    // Procesar el evento según su tipo
    if (isset($event['event'])) {
        switch ($event['event']) {
            case 'transaction.updated':
                $transaction = $event['data']['transaction'] ?? null;
                if (!$transaction) {
                    throw new Exception('Datos de transacción no encontrados');
                }
                
                $reference = $transaction['reference'] ?? '';
                $status = strtolower($transaction['status'] ?? '');
                $transaction_id = $transaction['id'] ?? '';
                $payment_method = $transaction['payment_method_type'] ?? '';
                $payment_date = date('Y-m-d H:i:s', strtotime($transaction['created_at'] ?? 'now'));
                
                if (empty($reference)) {
                    throw new Exception('Referencia de transacción no encontrada');
                }
                
                // Mapear el estado de Wompi al estado de tu sistema
                $status_map = [
                    'approved' => 'paid',
                    'declined' => 'declined',
                    'voided' => 'voided',
                    'error' => 'error',
                    'pending' => 'pending'
                ];
                
                $payment_status = $status_map[$status] ?? 'pending';
                
                // Iniciar transacción
                $conn->begin_transaction();
                
                try {
                    // Actualizar el estado del pedido en la base de datos
                    $update_sql = "UPDATE order_list SET 
                        payment_status = ?,
                        payment_reference = ?,
                        wompi_transaction_id = ?,
                        payment_date = ?,
                        status = CASE WHEN ? = 'paid' THEN 1 ELSE status END
                        WHERE reference = ?";
                    
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param(
                        'ssssss',
                        $payment_status,
                        $transaction_id,
                        $transaction_id,
                        $payment_date,
                        $payment_status,
                        $reference
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception('Error al actualizar el pedido: ' . $stmt->error);
                    }
                    
                    // Si el pago fue exitoso, actualizar el inventario
                    if ($payment_status === 'paid') {
                        // Obtener los items del pedido
                        $items_sql = "SELECT oi.product_id, oi.quantity 
                                     FROM order_items oi 
                                     JOIN order_list ol ON oi.order_id = ol.id 
                                     WHERE ol.reference = ?";
                        
                        $stmt = $conn->prepare($items_sql);
                        $stmt->bind_param('s', $reference);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($item = $result->fetch_assoc()) {
                            // Actualizar el inventario (ajustar según tu esquema)
                            $update_stock = $conn->prepare("UPDATE product_list 
                                                          SET stock = stock - ? 
                                                          WHERE id = ?");
                            $update_stock->bind_param('ii', $item['quantity'], $item['product_id']);
                            $update_stock->execute();
                            $update_stock->close();
                        }
                        
                        // Aquí podrías agregar el envío de correo de confirmación
                        // sendOrderConfirmationEmail($reference);
                    }
                    
                    $conn->commit();
                    logEvent('Pedido actualizado exitosamente', [
                        'reference' => $reference,
                        'status' => $payment_status,
                        'transaction_id' => $transaction_id
                    ]);
                    
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Pedido actualizado correctamente',
                        'reference' => $reference
                    ]);
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    throw $e;
                }
                
                break;
                
            case 'payment.created':
            case 'payment.updated':
                // Manejar otros eventos de pago si es necesario
                logEvent('Evento de pago recibido', $event);
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Evento recibido']);
                break;
                
            default:
                logEvent('Tipo de evento no manejado', $event);
                http_response_code(200);
                echo json_encode(['status' => 'success', 'message' => 'Evento no manejado']);
        }
    } else {
        throw new Exception('Evento no reconocido');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $error_message = 'Error en el webhook: ' . $e->getMessage();
    logEvent($error_message, [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'event' => $event ?? null
    ]);
    
    echo json_encode([
        'status' => 'error',
        'message' => $error_message
    ]);
}

// Función para enviar correo de confirmación (ejemplo)
function sendOrderConfirmationEmail($reference) {
    // Implementar el envío de correo aquí
    // Usar PHPMailer, mail() u otra librería
}
?>
