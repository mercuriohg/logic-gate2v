CREATE TABLE IF NOT EXISTS password_reset (
    user_id INT NOT NULL PRIMARY KEY,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_sent_at DATETIME NOT NULL,

    CONSTRAINT fk_password_reset_usuario
        FOREIGN KEY (user_id)
        REFERENCES usuario(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;
