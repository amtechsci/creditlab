-- Migration: Add block_next_loan flag to user table
-- Run once in your database (credit)
-- Date: 2026-06-05

ALTER TABLE `user`
    ADD COLUMN `block_next_loan` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Admin flag: auto-hold user on next loan application (1=blocked, 0=normal)';
