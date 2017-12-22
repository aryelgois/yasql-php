-- Generated with yasql-php
-- https://github.com/aryelgois/yasql-php
--
-- Timestamp: 2017-12-22T11:30:05-02:00
-- PHP version: 7.0.22-0ubuntu0.16.04.1
--
-- Project: aryelgois/yasql
-- Description: A YASQL database example
-- Version: 1.0.0
-- License: MIT
-- Authors: Aryel

CREATE DATABASE IF NOT EXISTS `example`
  CHARACTER SET utf8
  COLLATE utf8_general_ci;

USE `example`;

--
-- Tables
--

CREATE TABLE `people` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `document` varchar(14) NOT NULL
) CHARACTER SET utf8;

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `email` varchar(30) NOT NULL,
  `password` binary(20) NOT NULL
) CHARACTER SET utf8;

CREATE TABLE `products` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `description` varchar(500) NULL,
  `cost` decimal(17,4) UNSIGNED NOT NULL,
  `rating` tinyint(1) NOT NULL
) CHARACTER SET utf8;

CREATE TABLE `carts` (
  `id` int UNSIGNED NOT NULL,
  `person` int UNSIGNED NOT NULL,
  `paid` tinyint(1) UNSIGNED NOT NULL,
  `stamp` timestamp NOT NULL
) CHARACTER SET utf8;

CREATE TABLE `cart_items` (
  `cart` int UNSIGNED NOT NULL,
  `product` int UNSIGNED NOT NULL,
  `amount` int UNSIGNED NOT NULL
) CHARACTER SET utf8;

--
-- Indexes
--

ALTER TABLE `people`
  ADD UNIQUE KEY (`document`),
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart`, `product`);

--
-- AUTO_INCREMENT
--

ALTER TABLE `people`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `products`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `carts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Foreigns
--

ALTER TABLE `users`
  ADD FOREIGN KEY (`id`) REFERENCES `people` (`id`);

ALTER TABLE `carts`
  ADD FOREIGN KEY (`person`) REFERENCES `people` (`id`);

ALTER TABLE `cart_items`
  ADD FOREIGN KEY (`cart`) REFERENCES `carts` (`id`),
  ADD FOREIGN KEY (`product`) REFERENCES `products` (`id`);
