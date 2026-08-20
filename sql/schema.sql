-- MySQL 5.0 compatible schema for snk_comservice_new
-- Run via install.php

CREATE DATABASE IF NOT EXISTS `snk_comservice_new`
  DEFAULT CHARACTER SET utf8
  COLLATE utf8_unicode_ci;

USE `snk_comservice_new`;

DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `sla_items`;
DROP TABLE IF EXISTS `job_types`;
DROP TABLE IF EXISTS `job_categories`;
DROP TABLE IF EXISTS `departments`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(128) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL default 'staff',
  `is_active` tinyint(1) NOT NULL default '1',
  `created_at` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `job_categories` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `job_types` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `sort_order` int(11) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `sla_items` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(150) NOT NULL,
  `duration_text` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `jobs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `ticket_no` varchar(20) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `department_id` int(10) unsigned NOT NULL,
  `requester_name` varchar(100) NOT NULL,
  `requester_phone` varchar(30) NOT NULL default '',
  `category_id` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `priority` varchar(10) NOT NULL default 'normal',
  `status` varchar(20) NOT NULL default 'pending',
  `needed_by` date default NULL,
  `received_at` datetime default NULL,
  `completed_at` datetime default NULL,
  `assignee_id` int(10) unsigned default NULL,
  `estimate_days` varchar(20) default NULL,
  `work_note` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uk_ticket` (`ticket_no`),
  KEY `idx_status` (`status`),
  KEY `idx_dept` (`department_id`),
  KEY `idx_created` (`created_at`),
  KEY `idx_assignee` (`assignee_id`),
  KEY `idx_priority` (`priority`),
  KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
