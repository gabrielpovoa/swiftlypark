CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    actor_email VARCHAR(255) NOT NULL,
    action VARCHAR(10) NOT NULL,
    entity VARCHAR(100) NOT NULL,
    entity_id VARCHAR(64) NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    request_id CHAR(36) NOT NULL,
    created_at DATETIME(6) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT chk_audit_logs_action
        CHECK (action IN ('CREATE', 'UPDATE', 'DELETE')),
    CONSTRAINT fk_audit_logs_user
        FOREIGN KEY (user_id) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    KEY idx_audit_logs_user_created (user_id, created_at),
    KEY idx_audit_logs_entity (entity, entity_id, created_at),
    KEY idx_audit_logs_request (request_id)
) ENGINE=InnoDB
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

ALTER TABLE vagas_disponiveis
    ADD COLUMN created_by INT NULL AFTER status,
    ADD COLUMN updated_by INT NULL AFTER created_by,
    ADD CONSTRAINT fk_vagas_disponiveis_created_by
        FOREIGN KEY (created_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_vagas_disponiveis_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE vagas_preenchidas
    ADD COLUMN created_by INT NULL AFTER tipo_veiculo,
    ADD COLUMN updated_by INT NULL AFTER created_by,
    ADD CONSTRAINT fk_vagas_preenchidas_created_by
        FOREIGN KEY (created_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_vagas_preenchidas_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

ALTER TABLE transacoes
    ADD COLUMN created_by INT NULL AFTER data_transacao,
    ADD COLUMN updated_by INT NULL AFTER created_by,
    ADD CONSTRAINT fk_transacoes_created_by
        FOREIGN KEY (created_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT,
    ADD CONSTRAINT fk_transacoes_updated_by
        FOREIGN KEY (updated_by) REFERENCES usuario (id_usuario)
        ON UPDATE RESTRICT ON DELETE RESTRICT;
