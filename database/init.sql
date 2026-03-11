CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    is_admin BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS files (
    id SERIAL PRIMARY KEY,
    share_token VARCHAR(64) UNIQUE NOT NULL,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL,
    password_hash TEXT,
    expires_at TIMESTAMP
);

-- Default admin account (username: admin, password: admin)
INSERT INTO users (username, password_hash, is_admin)
VALUES ('admin', '21232f297a57a5a743894a0e4a801fc3', TRUE)
ON CONFLICT (username) DO NOTHING;
