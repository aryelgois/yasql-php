-- Generated with yasql-php
-- https://github.com/aryelgois/yasql-php
--
-- Timestamp: 2018-01-05T20:36:22-02:00
-- PHP version: 7.0.22-0ubuntu0.16.04.1
--
-- License: MIT

CREATE DATABASE IF NOT EXISTS `Uncommon-db`
  CHARACTER SET utf8
  COLLATE utf8_general_ci;

USE `Uncommon-db`;

--
-- Tables
--

CREATE TABLE `multi word table` (
  `column``with``quotes` int UNSIGNED NOT NULL
) CHARACTER SET utf8;

CREATE TABLE `:D` (
  `kill me` int UNSIGNED NOT NULL,
  `please` int UNSIGNED NOT NULL
) CHARACTER SET utf8;

--
-- Indexes
--

ALTER TABLE `multi word table`
  ADD PRIMARY KEY (`column``with``quotes`);

ALTER TABLE `:D`
  ADD PRIMARY KEY (`kill me`, `please`);

--
-- Foreigns
--

ALTER TABLE `:D`
  ADD FOREIGN KEY (`kill me`) REFERENCES `multi word table` (`column``with``quotes`);
