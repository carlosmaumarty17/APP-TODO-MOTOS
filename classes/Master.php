<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_category(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id','description'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(isset($_POST['description'])){
			if(!empty($data)) $data .=",";
				$data .= " `description`='".addslashes(htmlentities($description))."' ";
		}
		$check = $this->conn->query("SELECT * FROM `categories` where `category` = '{$category}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "La categoría ya existe.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `categories` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `categories` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"Nueva categoría guardada exitosamente.");
			else
				$this->settings->set_flashdata('success',"Categoría actualizada exitosamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_category(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `categories` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Categoría eliminada exitosamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_brand(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `brand_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Brand already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `brand_list` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `brand_list` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			$id = empty($id) ? $this->conn->insert_id : $id;
			if(empty($id))
				$resp['msg'] = "Nueva marca guardada exitosamente.";
			else
				$resp['msg'] = "Marca actualizada exitosamente.";
			if(!empty($_FILES['img']['tmp_name'])){
				$ext = $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$dir = base_app."uploads/brands/";
				if(!is_dir($dir))
				mkdir($dir);
				$name = $id.".".$ext;
				if(is_file($dir.$name))
					unlink($dir.$name);
				$move = move_uploaded_file($_FILES['img']['tmp_name'],$dir.$name);
				if($move){
					$this->conn->query("UPDATE `brand_list` set image_path = CONCAT('uploads/brands/$name','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$id}'");
				}else{
					$resp['msg'] .= " But logo has failed to upload.";
				}
			}
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if(isset($resp['msg']) && $resp['status'] == 'success'){
			$this->settings->set_flashdata('success',$resp['msg']);
		}
		return json_encode($resp);
	}
	function delete_brand(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `brand_list` set `delete_flag` = 1  where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Marca eliminada exitosamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_product(){
		$_POST['description'] = htmlentities($_POST['description']);
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `product_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Product already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `product_list` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `product_list` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			$pid = empty($id) ? $this->conn->insert_id : $id;
			$resp['id'] = $pid ;
			if(empty($id))
				$resp['msg'] = "Nuevo producto guardado correctamente.";
			else
				$resp['msg'] = "Producto actualizado correctamente.";
			if(!empty($_FILES['img']['tmp_name'])){
				$ext = $ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
				$dir = base_app."uploads/products/";
				if(!is_dir($dir))
				mkdir($dir);
				$name = $pid.".".$ext;
				if(is_file($dir.$name))
					unlink($dir.$name);
				$move = move_uploaded_file($_FILES['img']['tmp_name'],$dir.$name);
				if($move){
					$this->conn->query("UPDATE `product_list` set image_path = CONCAT('uploads/products/$name','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$pid}'");
				}else{
					$resp['msg'] .= " But logo has failed to upload.";
				}
			}
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if(isset($resp['msg']) && $resp['status'] == 'success'){
			$this->settings->set_flashdata('success',$resp['msg']);
		}
		return json_encode($resp);
	}
	function delete_product(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `product_list` set `delete_flag` = 1  where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Product successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_service(){
		extract($_POST);
		$data = "";
		$_POST['description'] = addslashes(htmlentities($description));
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `service_list` where `service` = '{$service}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "El servicio ya existe.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `service_list` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `service_list` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"Nuevo servicio guardado exitosamente.");
			else
				$this->settings->set_flashdata('success',"Servicio actualizado exitosamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_service(){
	extract($_POST);
		// Primero verificamos si hay solicitudes de servicio relacionadas
		$check = $this->conn->query("SELECT COUNT(*) as total FROM `service_requests` WHERE service_type = (SELECT service FROM `service_list` WHERE id = '{$id}')");
		$has_requests = $check->fetch_assoc()['total'] > 0;
		
		if($has_requests) {
			// Si hay solicitudes relacionadas, marcamos como eliminado lógicamente
			$del = $this->conn->query("UPDATE `service_list` SET delete_flag = 1 WHERE id = '{$id}'");
		} else {
			// Si no hay solicitudes relacionadas, eliminamos permanentemente
			$del = $this->conn->query("DELETE FROM `service_list` WHERE id = '{$id}'");
		}
		
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Servicio eliminado exitosamente.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_stock(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `stock_list` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `stock_list` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New Stock successfully saved.");
			else
				$this->settings->set_flashdata('success',"Stock successfully updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_stock(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `stock_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Stock successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_mechanic(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		$check = $this->conn->query("SELECT * FROM `mechanics_list` where `name` = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
		if($this->capture_err())
			return $this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Mechanic already exist.";
			return json_encode($resp);
			exit;
		}
		if(empty($id)){
			$sql = "INSERT INTO `mechanics_list` set {$data} ";
			$save = $this->conn->query($sql);
		}else{
			$sql = "UPDATE `mechanics_list` set {$data} where id = '{$id}' ";
			$save = $this->conn->query($sql);
		}
		if($save){
			$resp['status'] = 'success';
			if(empty($id))
				$this->settings->set_flashdata('success',"New Mechanic successfully saved.");
			else
				$this->settings->set_flashdata('success',"Mechanic successfully updated.");
		}else{
			$resp['status'] = 'failed';
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		return json_encode($resp);
	}
	function delete_mechanic(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `mechanics_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Mechanic successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_request(){
		if(empty($_POST['id']))
		$_POST['client_id'] = $this->settings->userdata('id');
		extract($_POST);
		$data = "";
		foreach($_POST as $k=> $v){
			if(in_array($k,array('client_id','service_type','mechanic_id','status'))){
				if(!empty($data)){ $data .= ", "; }

				$data .= " `{$k}` = '{$v}'";

			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `service_requests` set {$data} ";
		}else{
			$sql = "UPDATE `service_requests` set {$data} where id ='{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$rid = empty($id) ? $this->conn->insert_id : $id ;
			$data = "";
			foreach($_POST as $k=> $v){
				if(!in_array($k,array('id','client_id','service_type','mechanic_id','status'))){
					if(!empty($data)){ $data .= ", "; }
					if(is_array($_POST[$k]))
					$v = implode(",",$_POST[$k]);
					$v = $this->conn->real_escape_string($v);
					$data .= "('{$rid}','{$k}','{$v}')";
				}
			}
			$sql = "INSERT INTO `request_meta` (`request_id`,`meta_field`,`meta_value`) VALUES {$data} ";
			$this->conn->query("DELETE FROM `request_meta` where `request_id` = '{$rid}' ");
			$save = $this->conn->query($sql);
			if($save){
				$resp['status'] = 'success';
				$resp['id'] = $rid;
				if(empty($id))
				$resp['msg'] = " La solicitud de servicio se ha enviado correctamente.";
				else
				$resp['msg'] = " Los detalles de la solicitud de servicio se han actualizado correctamente.";
			}else{
				$resp['status'] = 'failed';
				$resp['error'] = $this->conn->error;
				$resp['sql'] = $sql;
				if(empty($id))
				$resp['msg'] = " Service Request has failed to submit.";
				else
				$resp['msg'] = " Service Request details has failed to update.";
				$this->conn->query("DELETE FROM `service_requests` where id = '{$rid}'");
			}

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			$resp['sql'] = $sql;
			if(empty($id))
			$resp['msg'] = " Service Request has failed to submit.";
			else
			$resp['msg'] = " Service Request details has failed to update.";
		}
		if($resp['status'] == 'success')
		$this->settings->set_flashdata("success", $resp['msg']);
		return json_encode($resp);
	}
	function delete_request(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `service_requests` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Request successfully deleted.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
	function save_to_cart(){
		$_POST['client_id'] = $this->settings->userdata('id');
		extract($_POST);
		$check = $this->conn->query("SELECT * FROM `cart_list` where client_id = '{$client_id}' and product_id = '{$product_id}'")->num_rows;
		if($check > 0){
			$sql = "UPDATE `cart_list` set quantity = quantity + {$quantity}  where product_id = '{$product_id}' and client_id = '{$client_id}'";
		}else{
			$sql = "INSERT INTO `cart_list` set quantity = quantity + {$quantity}, product_id = '{$product_id}', client_id = '{$client_id}'";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$resp['cart_count'] = $this->conn->query("SELECT SUM(quantity) from cart_list where client_id = '{$client_id}'")->fetch_array()[0];
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = " Product has failed to add in the cart list.";
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_cart_quantity(){
		extract($_POST);
		$get = $this->conn->query("SELECT * FROM `cart_list` where id = '{$cart_id}'")->fetch_array();
		$pid = $get['product_id'];
		$stocks = $this->conn->query("SELECT SUM(quantity) FROM stock_list where product_id = '$pid'")->fetch_array()[0];
        $out = $this->conn->query("SELECT SUM(quantity) FROM order_items where product_id = '{$pid}' and order_id in (SELECT id FROM order_list where `status` != 5) ")->fetch_array()[0];
        $stocks = $stocks > 0 ? $stocks : 0;
        $out = $out > 0 ? $out : 0;
        $available = $stocks - $out;
		if($available < 1){
			$resp['status'] = 'failed';
			$resp['msg'] = " Product doesn't have stock available.";
			$save = $this->conn->query("UPDATE cart_list set quantity = '0' where id = '{$cart_id}'");

		}elseif(eval("return ".$get['quantity']." ".$quantity.";") < 1 && $available > 0){
			$resp['status'] = 'failed';
			$save = $this->conn->query("UPDATE cart_list set quantity = '1' where id = '{$cart_id}'");
			$resp['msg'] = " You are at the lowest quantity.";
		}elseif(eval("return ".$get['quantity']." ".$quantity.";") > $available){
			$resp['status'] = 'failed';
			$save = $this->conn->query("UPDATE cart_list set quantity = '{$available}' where id = '{$cart_id}'");
			$resp['msg'] = " Product has only [{$available}] available stock";
		}else{
			$resp['status'] = 'success';
			$save = $this->conn->query("UPDATE cart_list set quantity = quantity {$quantity} where id = '{$cart_id}'");
		}
		return json_encode($resp);
	}
	function remove_from_cart(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `cart_list` where id = '{$cart_id}'");
		if($del){
			$resp['status'] = 'success';
			$resp['msg'] = "El producto ha sido eliminado del carrito correctamente.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "No se pudo eliminar el producto del carrito.";
			$resp['error'] = $this->conn->error;
		}
		if($resp['status'] == 'success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function place_order(){
		$_POST['client_id'] = $this->settings->userdata('id');
		extract($_POST);
		$pref = date("Ym-");
		$code = sprintf("%'.05d",1);
		while(true){
			$check = $this->conn->query("SELECT * FROM `order_list` where ref_code = '{$pref}{$code}'")->num_rows;
			if($check > 0){
				$code = sprintf("%'.05d",ceil($code) + 1);
			}else{
				break;
			}
		} 
		$ref_code = $pref.$code;
		
		// Generar referencia única para Wompi
		$ref_code = 'WMPI_' . time() . '_' . uniqid();
		
		// Calcular el total del carrito
		$total_amount = 0;
		$cart = $this->conn->query("SELECT c.*,p.price FROM cart_list c inner join product_list p on c.product_id = p.id where c.client_id = '{$_POST['client_id']}'");
		while($row = $cart->fetch_assoc()){
			$total_amount += ($row['price'] * $row['quantity']);
		}
		
		// Insertar el pedido con los datos de pago
		$sql1 = "INSERT INTO `order_list` 
			(`ref_code`, `client_id`, `delivery_address`, `total_amount`) 
			VALUES 
			('{$ref_code}','{$_POST['client_id']}','{$delivery_address}','{$total_amount}')";
			
		$save = $this->conn->query($sql1);
		
		if($save){
			$oid = $this->conn->insert_id;
			$data = "";
			$cart = $this->conn->query("SELECT c.*,p.price FROM cart_list c inner join product_list p on c.product_id = p.id where c.client_id = '{$_POST['client_id']}'");
			
			while($row = $cart->fetch_assoc()){
				if(!empty($data)) $data .= ", ";
				$data .= "('{$oid}','{$row['product_id']}','{$row['quantity']}')";
			}
			
			if(!empty($data)){
				$sql2 = "INSERT INTO `order_items` (`order_id`,`product_id`,`quantity`) VALUES {$data}";
				$save2 = $this->conn->query($sql2);
				
				if($save2){
					// Si el pago es con Wompi, devolver la información necesaria para el widget
					$resp['status'] = 'success';
					$resp['message'] = 'Pedido creado exitosamente. Redirigiendo a Wompi...';
					$resp['order_id'] = $oid;
					$resp['reference'] = $ref_code; // Usar ref_code como referencia
					$resp['amount'] = (int)($total_amount * 100); // Convertir a centavos para Wompi
					$resp['currency'] = 'COP';
					$resp['public_key'] = 'pub_test_7uCFxW8WrWZnhyaXf3sH9y99nXis5KxI';
					$resp['redirect_url'] = base_url . '?p=my_orders';
					
					// Limpiar el carrito después de crear el pedido exitosamente
					$this->conn->query("DELETE FROM `cart_list` where client_id = '{$_POST['client_id']}'");
					session_write_close();
				} else {
					$resp['status'] = 'error';
					$resp['message'] = 'Error al guardar los items del pedido';
					$this->conn->query("DELETE FROM `order_list` where id = '{$oid}'");
				}
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "El carrito está vacío.";
			}
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "Error al crear el pedido.";
			$resp['error'] = $this->conn->error;
		}
		
		if($resp['status'] == 'success' && !isset($resp['order_id'])) {
			$this->settings->set_flashdata('success', $resp['msg']);
		}
		
		return json_encode($resp);
	}
	function cancel_order(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `order_list` set status = 5 where id = '{$id}'");
		if($update){
			$resp['status'] = 'success';
			$resp['msg'] = " Order has been cancelled.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = " Order has failed to cancel.";
			$resp['error'] = $this->conn->error;
		}
		if($resp['status'] == 'success')
		$this->settings->set_flashdata('success',$resp['status']);
		return json_encode($resp);
	}
	function cancel_service(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `service_requests` set status = 4 where id = '{$id}'");
		if($update){
			$resp['status'] = 'success';
			$resp['msg'] = " Service Request has been cancelled.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = " Service Request has failed to cancel.";
			$resp['error'] = $this->conn->error;
		}
		if($resp['status'] == 'success')
		$this->settings->set_flashdata('success',$resp['status']);
		return json_encode($resp);
	}
	function update_order_status(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `order_list` set `status` = '{$status}' where id = '{$id}'");
		if($update){
			$resp['status'] ='success';
			$resp['msg'] = "El estado del pedido se ha actualizado correctamente.";
		}else{
			$resp['error'] = $this->conn->error;
			$resp['status'] ='failed';
			$resp['msg'] = "An error occurred while updating the order status. Error: ".$this->conn->error;
		}
		if($resp['status'] == 'success'){
			$this->settings->set_flashdata('success',$resp['msg']);
		}
		return json_encode($resp);
	}
	function get_orders_status() {
	    $resp = array('status' => 'failed', 'data' => array());
	    
	    if (isset($_POST['ids']) && is_array($_POST['ids']) && !empty($_POST['ids'])) {
	        $ids = array_map('intval', $_POST['ids']); // Sanitizar IDs
	        $ids = array_filter($ids); // Eliminar valores vacíos
	        
	        if (!empty($ids)) {
	            $id_list = implode(",", $ids);
	            $query = $this->conn->query("SELECT id, status FROM order_list WHERE id IN ($id_list) ORDER BY FIELD(id, $id_list)");
	            
	            if ($query && $query->num_rows > 0) {
	                $resp['status'] = 'success';
	                while ($row = $query->fetch_assoc()) {
	                    $resp['data'][] = array(
	                        'id' => $row['id'],
	                        'status' => $row['status']
	                    );
	                }
	            }
	        }
	    }
	    
	    return json_encode($resp);
	}
	function delete_order(){
		extract($_POST);
		$delete = $this->conn->query("DELETE FROM `order_list` where id = '{$id}'");
		if($delete){
			$resp['status'] ='success';
			$resp['msg'] = " Order's status has been deleted successfully.";
		}else{
			$resp['error'] = $this->conn->error;
			$resp['status'] ='failed';
			$resp['msg'] = " Order's status has failed to delete.";
		}
		if($resp['status'] == 'success')
		$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_category':
		echo $Master->save_category();
	break;
	case 'delete_category':
		echo $Master->delete_category();
	break;
	case 'save_brand':
		echo $Master->save_brand();
	break;
	case 'delete_brand':
		echo $Master->delete_brand();
	break;
	case 'save_service':
		echo $Master->save_service();
	break;
	case 'delete_service':
		echo $Master->delete_service();
	break;
	case 'save_product':
		echo $Master->save_product();
	break;
	case 'delete_product':
		echo $Master->delete_product();
	break;
	case 'save_stock':
		echo $Master->save_stock();
	break;
	case 'delete_stock':
		echo $Master->delete_stock();
	break;
	case 'save_mechanic':
		echo $Master->save_mechanic();
	break;
	case 'delete_mechanic':
		echo $Master->delete_mechanic();
	break;
	case 'save_request':
		echo $Master->save_request();
	break;
	case 'delete_request':
		echo $Master->delete_request();
	break;
	case 'cancel_service':
		echo $Master->cancel_service();
	break;
	case 'save_to_cart':
		echo $Master->save_to_cart();
	break;
	case 'update_cart_quantity':
		echo $Master->update_cart_quantity();
	break;
	case 'remove_from_cart':
		echo $Master->remove_from_cart();
	break;
	case 'place_order':
		echo $Master->place_order();
	break;
	case 'cancel_order':
		echo $Master->cancel_order();
	break;
	case 'update_order_status':
		echo $Master->update_order_status();
	break;
	case 'get_orders_status':
		echo $Master->get_orders_status();
	break;
	case 'delete_order':
		echo $Master->delete_order();
	break;
	default:
		// echo $sysset->index();
		break;
}