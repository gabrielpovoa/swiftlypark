INSERT INTO login (id_login, email, senha)
VALUES (900001, 'integration@example.test', '$2y$10$integrationfixture');

INSERT INTO usuario (
    id_usuario, id_login, nome, email, senha_hash, password_reset_required
) VALUES (
    900001, 900001, 'Usuário Integração', 'integration@example.test',
    '$2y$10$integrationfixture', 0
);

INSERT INTO companies (id, name, trade_name, slug)
VALUES
    (900001, 'Empresa Integração A', 'Empresa Integração A', 'integration-a'),
    (900002, 'Empresa Integração B', 'Empresa Integração B', 'integration-b');

INSERT INTO company_user (company_id, user_id, role_id)
SELECT 900001, 900001, id FROM roles WHERE slug = 'admin';

INSERT INTO company_vehicle_types (company_id, vehicle_type, is_enabled)
VALUES
    (900001, 'carro', 1),
    (900001, 'moto', 1),
    (900001, 'caminhao', 1),
    (900001, 'app', 1),
    (900002, 'carro', 1),
    (900002, 'moto', 1),
    (900002, 'caminhao', 1),
    (900002, 'app', 1);

INSERT INTO vagas_disponiveis (id_vaga, company_id, categoria, status)
VALUES
    (900001, 900001, 'carro', 'livre'),
    (900002, 900001, 'moto', 'reservada');

INSERT INTO vagas_preenchidas (
    id_vaga_preenchida, company_id, id_vaga, hora_entrada, hora_saida,
    tempo_total, nome_cliente, telefone, placa, valor_pago, tipo_veiculo,
    billing_model, created_by, updated_by
) VALUES (
    900001, 900001, 900002, UTC_TIMESTAMP(), UTC_TIMESTAMP(), '00:10:00',
    'Cliente Integração', '41999999999', 'INT-0001', 0, 'moto',
    'ROTATING', 900001, 900001
);
