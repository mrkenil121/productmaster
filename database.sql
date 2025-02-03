-- Users Table

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    mobile_no VARCHAR(20) NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER REFERENCES users(id),
    updated_at TIMESTAMP,
    updated_by INTEGER REFERENCES users(id),
    deleted_at TIMESTAMP,
    deleted_by INTEGER REFERENCES users(id)
);

-- Triggers

-- Function for updating updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger for updated_at
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE
    ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Function for handling soft delete
CREATE OR REPLACE FUNCTION handle_soft_delete()
RETURNS TRIGGER AS $$
BEGIN
    NEW.deleted_at = CURRENT_TIMESTAMP;
    NEW.is_active = false;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger for soft delete
CREATE TRIGGER handle_users_soft_delete
    BEFORE UPDATE OF is_active
    ON users
    FOR EACH ROW
    WHEN (OLD.is_active = true AND NEW.is_active = false)
    EXECUTE FUNCTION handle_soft_delete();