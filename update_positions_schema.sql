-- Update positions table to work with creator_id
-- Since the column already exists, we'll just update values and add constraints

-- Step 1: Update existing positions to use a valid admin user ID 
-- Replace 13 with an actual admin user ID from your users table
UPDATE positions SET creator_id = 13 WHERE creator_id IS NULL OR creator_id = 0;

-- Step 2: Make the column NOT NULL if it isn't already
ALTER TABLE positions MODIFY COLUMN creator_id INT NOT NULL;

-- Step 3: Add the foreign key constraint
-- First check if the constraint already exists and drop it if it does
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_NAME = 'fk_position_creator' 
    AND TABLE_NAME = 'positions'
);

SET @sql = IF(@constraint_exists > 0, 
    'ALTER TABLE positions DROP FOREIGN KEY fk_position_creator', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now add the constraint
ALTER TABLE positions ADD CONSTRAINT fk_position_creator FOREIGN KEY (creator_id) REFERENCES users(id) ON DELETE CASCADE;

-- Create applications table to track users applying to positions
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT NULL,
    CONSTRAINT fk_application_position FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE,
    CONSTRAINT fk_application_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT unique_application UNIQUE (position_id, user_id)
);