    -- Office Expenses Table
    CREATE TABLE IF NOT EXISTS office_expenses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expense_date DATE NOT NULL,
        category VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        amount DECIMAL(10,2) NOT NULL,
        payment_mode VARCHAR(50) DEFAULT 'Cash',
        receipt_no VARCHAR(100),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );

    -- Expense Categories Table
    CREATE TABLE IF NOT EXISTS expense_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(50) DEFAULT 'fas fa-receipt',
        color VARCHAR(20) DEFAULT '#6366f1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    -- Insert default categories
    INSERT INTO expense_categories (name, icon, color) VALUES
    ('Electricity Bill', 'fas fa-bolt', '#f59e0b'),
    ('Water Bill', 'fas fa-tint', '#3b82f6'),
    ('Office Rent', 'fas fa-building', '#8b5cf6'),
    ('Sweeper/Cleaning', 'fas fa-broom', '#10b981'),
    ('Maintenance', 'fas fa-tools', '#ef4444'),
    ('Internet/Phone', 'fas fa-wifi', '#06b6d4'),
    ('Stationery', 'fas fa-pen', '#ec4899'),
    ('Tea/Snacks', 'fas fa-coffee', '#84cc16'),
    ('Transport', 'fas fa-car', '#f97316'),
    ('Other', 'fas fa-ellipsis-h', '#64748b');
