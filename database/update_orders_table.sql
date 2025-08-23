-- Añadir campos necesarios para el procesamiento de pagos con Wompi
ALTER TABLE `order_list` 
ADD COLUMN `reference` VARCHAR(50) NULL AFTER `id`,
ADD COLUMN `payment_method` VARCHAR(20) NULL AFTER `reference`,
ADD COLUMN `payment_status` VARCHAR(20) NULL DEFAULT 'pending' AFTER `payment_method`,
ADD COLUMN `payment_reference` VARCHAR(100) NULL AFTER `payment_status`,
ADD COLUMN `payment_date` DATETIME NULL AFTER `payment_reference`,
ADD COLUMN `wompi_transaction_id` VARCHAR(100) NULL AFTER `payment_date`,
ADD INDEX `idx_reference` (`reference`) USING BTREE,
ADD INDEX `idx_payment_status` (`payment_status`) USING BTREE;

-- Actualizar referencias existentes (opcional)
-- UPDATE `order_list` SET `reference` = CONCAT('ORD_', LPAD(id, 8, '0')) WHERE `reference` IS NULL;
