-- Migration 015: Add max_age to requirement_trades
ALTER TABLE `requirement_trades`
ADD COLUMN `max_age` INT(10) UNSIGNED NULL AFTER `max_experience`;
