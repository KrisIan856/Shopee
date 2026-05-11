-- Migration: Add rider support to the existing shopee_ph schema

ALTER TABLE users
  MODIFY role ENUM('buyer','seller','admin','rider') NOT NULL DEFAULT 'buyer';

ALTER TABLE orders
  ADD COLUMN rider_id INT UNSIGNED DEFAULT NULL AFTER buyer_id,
  ADD CONSTRAINT fk_orders_rider FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL;
