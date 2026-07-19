# Novidades Futuras e Melhorias SaaS

Este documento registra pontos de melhoria identificados após a implementação das Fases SaaS 1 a 4. A ideia é manter um backlog claro para próximas iterações sem misturar problemas conhecidos com as specs já entregues.

## 1. Isolamento Real de Dados por Empresa

Hoje a base multi-tenant existe, mas ainda há pontos que precisam ser endurecidos para garantir SaaS real.

Melhorias necessárias:

- empresa nova deve nascer completamente vazia;
- empresa nova não pode herdar vagas, check-ins, transações, faturamento ou logs da `SwiftlyPark`;
- dashboards financeiros devem retornar `0` para empresas sem movimentação;
- cada empresa deve visualizar apenas:
  - suas vagas;
  - seus veículos estacionados;
  - suas transações;
  - seus ajustes financeiros;
  - seus logs operacionais;
  - seu faturamento;
  - seus operadores vinculados.

Critério esperado:

```text
Usuário da Empresa B não vê nenhum dado operacional da Empresa A.
```

## 2. Seleção Explícita de Empresa Ativa

O sistema ainda usa fallback para escolher a empresa ativa, com preferência para `swiftlypark`.

Isso ajudou na migração dos dados legados, mas não é ideal para operação SaaS.

Melhorias necessárias:

- criar seletor de empresa ativa no header/sidebar;
- listar apenas empresas vinculadas ao usuário;
- salvar empresa ativa em sessão;
- trocar `TenantContext` quando o usuário troca a empresa;
- bloquear troca para empresas sem vínculo;
- remover dependência de fallback automático em fluxos administrativos.

Critério esperado:

```text
Se o usuário tem acesso a duas empresas, ele escolhe explicitamente qual tenant está operando.
```

## 3. Autorização por Papel no Tenant

Hoje ainda existe dependência relevante de `user_roles`, que é global.

Para SaaS maduro, o papel efetivo deve vir do vínculo:

```text
company_user.role_id
```

Melhorias necessárias:

- atualizar `RolePermissionResolver` para considerar a empresa ativa;
- resolver permissões a partir de `company_user.role_id`;
- manter `user_roles` apenas para papéis globais como `master` ou `super-admin`, se necessário;
- permitir que um mesmo usuário seja `admin` em uma empresa e `operator` em outra;
- garantir que ADMIN de uma empresa não consiga administrar outra sem vínculo explícito.

Critério esperado:

```text
Permissões operacionais = papel do usuário dentro da empresa ativa.
```

## 4. Cadastro Empresarial Mais Completo

Hoje `companies` ainda é simples, com `name` e `slug`.

Para SaaS real, cada empresa precisa ter dados próprios.

Campos futuros sugeridos:

- razão social;
- nome fantasia;
- CNPJ/documento;
- e-mail administrativo;
- telefone;
- endereço;
- status da empresa;
- plano/assinatura;
- timezone;
- moeda;
- regras comerciais.

Constraints necessárias:

- `slug` único;
- CNPJ/documento único;
- talvez razão social única, dependendo da regra de negócio.

Critério esperado:

```text
Cada empresa possui cadastro fiscal/comercial próprio e identificadores únicos.
```

## 5. Regra de Cobrança Pós-Paga por Tempo

Hoje o fluxo cobra no check-in, antes de saber quanto tempo o cliente ficou estacionado.

O comportamento desejado é pós-pago:

```text
Cliente entra → sistema registra entrada.
Cliente sai → sistema calcula tempo estacionado.
Sistema cobra com base na tabela de preço da empresa.
```

Melhorias necessárias:

- remover pagamento obrigatório no check-in;
- registrar somente a entrada no check-in;
- calcular valor no checkout;
- gerar transação no momento da saída;
- permitir revisão/confirmação do valor antes de finalizar;
- manter forma de pagamento no checkout;
- auditar cálculo e pagamento.

Critério esperado:

```text
O valor pago deve ser calculado apenas no checkout, com base no tempo real estacionado.
```

## 6. Tabela de Preços por Empresa

Cada empresa deve ter sua própria política de cobrança.

Regras mencionadas:

- valor por hora por empresa;
- cobrança por fração de 30 minutos;
- preço independente entre empresas.

Tabela futura sugerida:

```text
company_pricing_rules
```

Campos sugeridos:

- `id`;
- `company_id`;
- `vehicle_type`;
- `hourly_rate`;
- `billing_increment_minutes`;
- `minimum_charge_minutes`;
- `daily_max_amount`;
- `is_active`;
- `created_at`;
- `updated_at`.

Exemplo:

```text
Empresa A:
  hora: R$ 12,00
  incremento: 30 min

Empresa B:
  hora: R$ 8,00
  incremento: 30 min
```

Critério esperado:

```text
Mesmo tempo estacionado pode gerar valores diferentes em empresas diferentes.
```

## 7. Cálculo de Fração de 30 Minutos

Regra sugerida:

```text
Cobrar por blocos de 30 minutos.
```

Exemplo com valor por hora de R$ 10,00:

- até 30 min → R$ 5,00;
- 31 a 60 min → R$ 10,00;
- 61 a 90 min → R$ 15,00;
- 91 a 120 min → R$ 20,00.

Pontos a definir:

- existe tolerância gratuita?
- o primeiro bloco mínimo é sempre 30 minutos?
- deve haver diária máxima?
- motos/carros/caminhões têm preços diferentes?
- mensalistas entram na mesma regra?

Critério esperado:

```text
O cálculo deve ser determinístico, auditável e baseado na regra ativa da empresa.
```

## 8. Refatoração do Fluxo de Vagas

Fluxo atual esperado futuramente:

```text
1. Operador seleciona vaga.
2. Registra placa, cliente e tipo de veículo.
3. Sistema salva check-in sem pagamento.
4. No checkout, sistema calcula tempo.
5. Sistema exibe valor calculado.
6. Operador escolhe forma de pagamento.
7. Sistema cria transação.
8. Sistema libera vaga.
```

Arquivos provavelmente afetados:

- `app/Models/VacancyModel.php`;
- `app/Controllers/VacancyController.php`;
- `app/Views/Vacancy/apply.php`;
- `public/js/FinishVacancy.js`;
- `app/Finance/Repositories/FinancialReportRepository.php`;
- `app/Finance/Services/FinancialReportService.php`.

## 9. Auditoria do Cálculo

Quando o sistema passar a calcular preço no checkout, a auditoria deve registrar:

- horário de entrada;
- horário de saída;
- tempo total;
- regra de preço aplicada;
- incremento usado;
- valor calculado;
- desconto/ajuste, se houver;
- forma de pagamento;
- operador responsável.

Critério esperado:

```text
Qualquer valor cobrado deve ser explicável posteriormente pela auditoria.
```

## 10. Valor Estimado em Tempo Real por Veículo

Um ponto importante para a operação é exibir, em cada veículo que está ocupando uma vaga, o valor estimado a pagar até o momento.

A ideia:

```text
Veículo estacionado
  ↓
tempo atual - hora_entrada
  ↓
company_pricing_rules
  ↓
valor estimado atualizado por bloco de cobrança
```

Melhorias necessárias:

- na tela de vagas ocupadas, exibir o valor estimado atual;
- atualizar o valor conforme o tempo passa;
- usar a regra ativa da empresa;
- considerar tipo de veículo;
- respeitar incremento de 30 minutos;
- destacar quando o valor muda de faixa;
- no checkout, usar o mesmo motor de cálculo para evitar divergência;
- deixar claro que o valor exibido antes do checkout é uma estimativa.

Exemplo com valor/hora de R$ 10,00 e incremento de 30 minutos:

```text
00:01 até 00:30 → R$ 5,00
00:31 até 01:00 → R$ 10,00
01:01 até 01:30 → R$ 15,00
01:31 até 02:00 → R$ 20,00
```

Possíveis locais na UI:

- card da vaga ocupada;
- listagem de veículos estacionados;
- modal de finalizar vaga;
- dashboard operacional.

Dados necessários por veículo:

- `hora_entrada`;
- `tipo_veiculo`;
- `company_id`;
- regra de preço ativa;
- timezone da empresa, quando houver.

Critério esperado:

```text
O operador consegue ver quanto cada veículo estacionado já deve pagar antes de finalizar a vaga.
```

## 11. Testes Necessários

Testes de isolamento:

- Empresa A com faturamento;
- Empresa B sem faturamento;
- usuário da Empresa B vê dashboard zerado;
- usuário da Empresa B não vê transações da Empresa A;
- usuário da Empresa A vê apenas seus dados.

Testes de cobrança:

- 15 minutos com incremento de 30 minutos;
- 30 minutos exatos;
- 31 minutos;
- 59 minutos;
- 60 minutos;
- 61 minutos;
- valor estimado antes do checkout;
- valor estimado após mudança de faixa de 30 minutos;
- tipos de veículo diferentes;
- preço diferente por empresa;
- checkout sem regra de preço ativa.

Testes de governança:

- MASTER cria empresa;
- MASTER cria admin para empresa nova;
- admin da empresa nova não acessa empresa SwiftlyPark;
- operador da empresa nova cria check-in sem afetar SwiftlyPark.

## Prioridade Sugerida

1. Criar seleção explícita de empresa ativa.
2. Fazer autorização usar `company_user.role_id`.
3. Garantir empresa nova vazia e dashboards zerados.
4. Criar cadastro completo de empresa.
5. Criar tabela de preços por empresa.
6. Refatorar check-in para não cobrar antecipado.
7. Refatorar checkout para calcular e cobrar.
8. Exibir valor estimado em tempo real por veículo estacionado.
9. Auditar cálculo financeiro.
10. Adicionar testes end-to-end de isolamento e cobrança.
