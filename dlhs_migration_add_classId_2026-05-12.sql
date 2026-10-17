-- DLHS Migration: Add classId to tests table
-- Run this on your database to support class arm selection in test creation.
-- Date: 2026-05-12

ALTER TABLE `tests`
  ADD COLUMN `classId` INT NOT NULL DEFAULT '0'
    COMMENT 'The class arm this test is assigned to (0 = unspecified / ALL)'
  AFTER `yearGroup`;
