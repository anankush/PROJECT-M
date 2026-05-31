-- ═══════════════════════════════════════════════════════
-- Money Management — Savings Module Database Schema Update
-- ═══════════════════════════════════════════════════════

-- Update savings_goals table
ALTER TABLE `savings_goals` 
  ADD COLUMN `category` varchar(50) NOT NULL DEFAULT 'others',
  ADD COLUMN `theme_color` varchar(50) NOT NULL DEFAULT 'purple',
  ADD COLUMN `priority` varchar(20) NOT NULL DEFAULT 'medium';

-- Update savings_transactions table
ALTER TABLE `savings_transactions` 
  ADD COLUMN `notes` varchar(255) DEFAULT NULL;
