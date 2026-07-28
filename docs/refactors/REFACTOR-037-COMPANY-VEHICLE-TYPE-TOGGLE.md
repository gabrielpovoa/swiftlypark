# REFACTOR-037 — Tipos de vaga por empresa

## Controle

- Branch: `feature/company-vehicle-type-toggle`
- Base: `develop`
- Data: 28/07/2026
- Estado: concluído e integrado à `develop`

## Objetivo

Permitir que cada empresa determine quais tipos de veículo atende. Um tipo
desabilitado não deve aparecer como possibilidade de check-in, criação de vaga,
tarifário ou novo contrato mensalista.

## Implementação

- Criada a tabela `company_vehicle_types`, isolada por `company_id`.
- A migration habilita carro, moto, caminhão e aplicativo para todas as empresas
  existentes, preservando o comportamento atual.
- A tela da empresa recebeu toggles para cada tipo de vaga.
- Ao desabilitar um tipo, seu tarifário fica oculto e não é enviado nem alterado.
- Tarifários existentes permanecem armazenados para uma futura reativação.
- O check-in rápido, o diretório de vagas e a criação de vagas exibem somente
  tipos habilitados.
- Novos contratos mensalistas também ficam limitados aos tipos oferecidos.
- Requisições manuais de check-in, criação de vaga ou contrato são verificadas
  novamente no backend.
- Estadias já abertas continuam disponíveis para checkout.
- É obrigatório manter ao menos um tipo habilitado por empresa.
- O lock do PhpSpreadsheet foi atualizado de `5.8.0` para `5.9.0` porque a
  auditoria do CI passou a apontar três vulnerabilidades de alta severidade na
  versão anterior; não houve mudança na API utilizada pela aplicação.

## Segurança e consistência

- A configuração é salva na mesma transação dos dados e tarifários da empresa.
- O `company_id` vem do contexto autorizado e possui chave estrangeira.
- O backend não confia na ocultação realizada pela interface.
- O evento de auditoria registra a lista de tipos habilitados.

## Testes

```bash
php tests/CompanyVehicleTypeToggleTest.php
php tests/RepositoryContractsTest.php
bash tests/ci/run-unit.sh
php tests/SecurityTestSuite.php
composer audit --locked --no-interaction
```

## Validação manual

1. Aplicar a migration `20260802_create_company_vehicle_types.sql`.
2. Abrir `/admin/companies/company_id=5`.
3. Desabilitar **Caminhão** e salvar.
4. Confirmar que o tarifário de caminhão desaparece da tela.
5. Abrir `/vacancy` e o dashboard; caminhão não deve aparecer.
6. Acessar diretamente `/vacancy/apply?type=caminhao`; a operação deve ser
   recusada.
7. Abrir a criação de vagas e confirmar que caminhão não está no seletor.
8. Confirmar que estadias de caminhão já abertas ainda podem ser finalizadas.
9. Reabilitar caminhão e confirmar que o tarifário anterior reaparece.

## Retomada

Após validar, finalizar a feature, criar a tag
`refactor-037-company-vehicle-type-toggle` e permanecer em `develop`.
