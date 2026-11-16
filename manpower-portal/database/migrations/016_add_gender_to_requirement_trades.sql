-- Migration 016: Add gender to requirement_trades
ALTER TABLE `requirement_trades`
ADD COLUMN `gender` VARCHAR(50) DEFAULT 'Any' AFTER `max_age`;
