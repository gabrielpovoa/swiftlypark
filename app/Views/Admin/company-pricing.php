<?php

$escape = static fn (mixed $value): string => htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
);
$vehicleTypes = [
    'carro' => ['label' => 'Carro', 'icon' => 'car'],
    'moto' => ['label' => 'Moto', 'icon' => 'bike'],
    'caminhao' => ['label' => 'Caminhão', 'icon' => 'truck'],
    'app' => ['label' => 'Aplicativo', 'icon' => 'smartphone'],
];
$isMonthly = (int) ($company['is_mensalista'] ?? 0) === 1;
?>

<section class="min-h-screen bg-[#0b0e14] text-slate-300 p-5 md:p-10">
    <div class="max-w-6xl mx-auto">
        <header class="flex flex-col md:flex-row md:items-end justify-between gap-5 mb-8">
            <div>
                <a href="/admin/companies" class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-blue-400 hover:text-blue-300 mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Diretório de empresas
                </a>
                <span class="block text-amber-400 text-xs font-black uppercase tracking-[0.3em]">Pricing Engine</span>
                <h1 class="text-4xl font-black text-white mt-2"><?= $escape($company['name']) ?></h1>
                <p class="text-slate-500 mt-2">Informações cadastrais, modelo de cobrança e valores utilizados no checkout.</p>
            </div>
            <div class="flex flex-wrap items-stretch justify-end gap-3">
                <a href="/audit?company_id=<?= (int) $company['id'] ?>" class="inline-flex min-h-[76px] items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/[0.04] px-5 py-4 text-xs font-black text-slate-200 hover:bg-white/[0.08]">
                    <i data-lucide="search-check" class="h-4 w-4 text-blue-400"></i>Auditar
                </a>
                <div class="flex min-h-[76px] items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-4">
                <?php if (!empty($company['logo_path'])): ?>
                    <img src="/uploads/<?= $escape($company['logo_path']) ?>" alt="" class="h-11 w-11 object-contain">
                <?php else: ?>
                    <i data-lucide="building-2" class="w-7 h-7 text-amber-300"></i>
                <?php endif; ?>
                <div>
                    <span class="block text-[9px] font-black uppercase tracking-widest text-slate-500">Empresa #<?= (int) $company['id'] ?></span>
                    <strong class="text-white"><?= $escape($company['slug']) ?></strong>
                </div>
                </div>
            </div>
        </header>

        <?php if ($success): ?>
            <div role="status" class="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-4 font-bold text-emerald-400">
                <?= $escape($success) ?>
            </div>
        <?php elseif ($error): ?>
            <div role="alert" class="mb-6 rounded-2xl border border-rose-500/20 bg-rose-500/10 px-5 py-4 font-bold text-rose-400">
                <?= $escape($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="/admin/companies/company_id=<?= (int) $company['id'] ?>" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="company_id" value="<?= (int) $company['id'] ?>">

            <details class="group overflow-hidden rounded-3xl border border-white/10 bg-white/[0.03]">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6 marker:content-none">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400">Identidade empresarial</span>
                        <h2 class="mt-1 text-2xl font-black text-white">Dados cadastrais</h2>
                        <p class="mt-2 text-sm text-slate-500">Nome, razão social, slug e identidade visual.</p>
                    </div>
                    <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 text-slate-500 transition-transform group-open:rotate-180"></i>
                </summary>
                <div class="grid grid-cols-1 gap-4 border-t border-white/10 p-6 md:grid-cols-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        Razão social
                        <input name="legal_name" maxlength="255"
                               value="<?= $escape($company['legal_name'] ?? '') ?>"
                               placeholder="Razão social registrada"
                               class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white outline-none focus:border-indigo-500">
                    </label>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        Nome fantasia
                        <input name="trade_name" maxlength="255" required
                               value="<?= $escape($company['trade_name'] ?: $company['name']) ?>"
                               placeholder="Nome exibido ao usuário"
                               class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white outline-none focus:border-indigo-500">
                    </label>
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                        Slug da empresa
                        <input name="slug" maxlength="120" required pattern="[a-z0-9]+(-[a-z0-9]+)*"
                               value="<?= $escape($company['slug']) ?>"
                               placeholder="nome-da-empresa"
                               class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white outline-none focus:border-indigo-500">
                    </label>
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-white/15 bg-black/20 px-5 py-6 text-center transition hover:border-indigo-500/50 hover:bg-indigo-500/[0.04]">
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="sr-only" data-company-logo-input>
                        <i data-lucide="image-up" class="mb-2 h-6 w-6 text-indigo-400"></i>
                        <strong class="text-sm normal-case tracking-normal text-white" data-company-logo-label>
                            <?= !empty($company['logo_path']) ? 'Substituir logo atual' : 'Arraste a logo ou clique para selecionar' ?>
                        </strong>
                        <span class="mt-1 text-xs normal-case tracking-normal text-slate-500">PNG, JPG ou WEBP · máximo de 2 MB</span>
                    </label>
                    <label class="md:col-span-2 text-[10px] font-black uppercase tracking-widest text-slate-600">
                        CNPJ · disponível em uma próxima etapa
                        <input value="<?= $escape($company['cnpj'] ?? '') ?>" disabled
                               placeholder="00.000.000/0000-00"
                               class="mt-2 w-full cursor-not-allowed rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3 text-base normal-case tracking-normal text-slate-600 opacity-70">
                    </label>
                    <div class="md:col-span-2 flex justify-end">
                        <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-500 px-5 py-3 text-sm font-black normal-case tracking-normal text-white hover:bg-indigo-400">
                            <i data-lucide="save" class="h-4 w-4"></i> Salvar dados da empresa
                        </button>
                    </div>
                </div>
            </details>

            <section class="rounded-3xl border border-white/10 bg-white/[0.03] p-6">
                <div class="mb-5">
                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-400">Modelo operacional</span>
                    <h2 class="mt-1 text-2xl font-black text-white">Como esta empresa cobra?</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="cursor-pointer rounded-2xl border border-white/10 bg-black/20 p-5 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-500/10">
                        <input type="radio" name="billing_model" value="rotating" <?= !$isMonthly ? 'checked' : '' ?> class="text-blue-500">
                        <strong class="ml-2 text-white">Rotativo · pós-pago</strong>
                        <span class="mt-2 block text-sm text-slate-500">Calcula o valor pelo tempo e registra o pagamento no checkout.</span>
                    </label>
                    <label class="cursor-pointer rounded-2xl border border-white/10 bg-black/20 p-5 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-500/10">
                        <input type="radio" name="billing_model" value="monthly" <?= $isMonthly ? 'checked' : '' ?> class="text-amber-400">
                        <strong class="ml-2 text-white">Híbrido · mensalista + rotativo</strong>
                        <span class="mt-2 block text-sm text-slate-500">Contratos vigentes são pré-pagos; veículos avulsos usam o tarifário rotativo no checkout.</span>
                    </label>
                </div>
            </section>

            <section class="rounded-3xl border border-white/10 bg-white/[0.03] p-6">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 mb-6">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Tarifários rotativos</span>
                        <h2 class="mt-1 text-2xl font-black text-white">Preço por tipo de veículo</h2>
                        <p class="mt-2 text-sm text-slate-500">Os valores permanecem salvos mesmo quando o modo mensalista estiver ativo.</p>
                    </div>
                    <div class="rounded-xl border border-blue-500/20 bg-blue-500/10 px-4 py-3 text-xs text-blue-200">
                        Total = base + períodos adicionais
                    </div>
                </div>

                <div class="space-y-4">
                    <?php foreach ($vehicleTypes as $vehicleType => $metadata): ?>
                        <?php $tariff = $tariffs[$vehicleType] ?? []; ?>
                        <article class="rounded-2xl border border-white/10 bg-black/20 p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-blue-500/20 bg-blue-500/10 text-blue-400">
                                    <i data-lucide="<?= $escape($metadata['icon']) ?>" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="font-black text-white"><?= $escape($metadata['label']) ?></h3>
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-500"><?= $escape($vehicleType) ?></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Valor base
                                    <input type="number" name="tariffs[<?= $escape($vehicleType) ?>][valor_base]" min="0" step="0.01" required
                                           value="<?= $escape($tariff['valor_base'] ?? '') ?>"
                                           class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base text-white outline-none focus:border-blue-500">
                                </label>
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Valor adicional
                                    <input type="number" name="tariffs[<?= $escape($vehicleType) ?>][valor_adicional]" min="0" step="0.01" required
                                           value="<?= $escape($tariff['valor_adicional'] ?? '') ?>"
                                           class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base text-white outline-none focus:border-blue-500">
                                </label>
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Tolerância (min)
                                    <input type="number" name="tariffs[<?= $escape($vehicleType) ?>][tolerancia_minutos]" min="0" step="1" required
                                           value="<?= $escape($tariff['tolerancia_minutos'] ?? '0') ?>"
                                           class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base text-white outline-none focus:border-blue-500">
                                </label>
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    Frequência (min)
                                    <input type="number" name="tariffs[<?= $escape($vehicleType) ?>][frequencia_adicional]" min="1" step="1" required
                                           value="<?= $escape($tariff['frequencia_adicional'] ?? '60') ?>"
                                           class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base text-white outline-none focus:border-blue-500">
                                </label>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <a href="/admin/companies" class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/[0.05] px-6 py-4 font-black text-slate-300 hover:bg-white/[0.08]">
                    Cancelar
                </a>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-7 py-4 font-black text-white shadow-lg shadow-blue-600/20 hover:bg-blue-500">
                    <i data-lucide="save" class="w-5 h-5"></i>
                    Salvar configuração
                </button>
            </div>
        </form>

        <section class="mt-10 rounded-3xl border border-cyan-500/20 bg-cyan-500/[0.035] p-6">
            <div class="mb-6 flex items-start justify-between gap-4">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-cyan-400">Acessos do tenant</span>
                    <h2 class="mt-1 text-2xl font-black text-white">Pessoas vinculadas</h2>
                    <p class="mt-2 text-sm text-slate-500">Adicione pessoas existentes ou remova o acesso especificamente desta empresa.</p>
                </div>
                <span class="rounded-xl border border-cyan-500/20 bg-cyan-500/10 px-3 py-2 text-xs font-black text-cyan-300"><?= count($companyMembers) ?> vínculos</span>
            </div>

            <form method="POST" action="/admin/users/link-company" class="grid grid-cols-1 gap-3 rounded-2xl border border-white/10 bg-black/20 p-5 md:grid-cols-[1fr_240px_auto] md:items-end">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <input type="hidden" name="company_id" value="<?= (int) $company['id'] ?>">
                <input type="hidden" name="return_company_id" value="<?= (int) $company['id'] ?>">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Pessoa
                    <select name="user_id" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white">
                        <option value="">Selecione uma pessoa</option>
                        <?php foreach ($availableUsers as $availableUser): ?>
                            <option value="<?= (int) $availableUser['id_usuario'] ?>"><?= $escape($availableUser['nome']) ?> · <?= $escape($availableUser['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Perfil
                    <select name="role_id" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white">
                        <option value="">Selecione</option>
                        <?php foreach ($assignableRoles as $role): ?>
                            <option value="<?= (int) $role['id'] ?>"><?= $escape($role['label'] ?: $role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-cyan-500 px-5 py-3 font-black text-black hover:bg-cyan-400">
                    <i data-lucide="user-round-plus" class="h-4 w-4"></i> Vincular
                </button>
            </form>

            <div class="mt-4 divide-y divide-white/5 overflow-hidden rounded-2xl border border-white/10 bg-black/20">
                <?php if ($companyMembers === []): ?>
                    <p class="p-5 text-sm text-slate-500">Nenhuma pessoa vinculada a esta empresa.</p>
                <?php endif; ?>
                <?php foreach ($companyMembers as $member): ?>
                    <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <strong class="block truncate text-white"><?= $escape($member['nome']) ?></strong>
                            <span class="block truncate text-xs text-slate-500"><?= $escape($member['email']) ?> · <?= $escape($member['role_label'] ?? 'Sem perfil') ?></span>
                        </div>
                        <form method="POST" action="/admin/users/remove-company" onsubmit="return confirm('Remover o acesso desta pessoa à empresa?');">
                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                            <input type="hidden" name="company_id" value="<?= (int) $company['id'] ?>">
                            <input type="hidden" name="return_company_id" value="<?= (int) $company['id'] ?>">
                            <input type="hidden" name="user_id" value="<?= (int) $member['id_usuario'] ?>">
                            <button class="inline-flex items-center gap-2 rounded-xl border border-rose-500/20 bg-rose-500/10 px-4 py-2 text-xs font-black text-rose-400 hover:bg-rose-500 hover:text-white">
                                <i data-lucide="user-round-minus" class="h-4 w-4"></i> Remover
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="mt-10 rounded-3xl border border-amber-500/20 bg-amber-500/[0.04] p-6">
            <div class="mb-6">
                <span class="text-[10px] font-black uppercase tracking-widest text-amber-400">Contratos pré-pagos</span>
                <h2 class="mt-1 text-2xl font-black text-white">Mensalistas por placa</h2>
                <p class="mt-2 text-sm text-slate-500">Cada contratação ou renovação registra uma mensalidade. Somente contratos ativos e vigentes liberam o check-in.</p>
                <?php if (!$isMonthly): ?>
                    <p class="mt-3 rounded-xl border border-blue-500/20 bg-blue-500/10 px-4 py-3 text-sm text-blue-200">Os contratos podem ser preparados agora, mas só serão exigidos quando o modelo mensalista estiver ativo.</p>
                <?php endif; ?>
            </div>

            <form method="POST" action="/admin/companies/company_id=<?= (int) $company['id'] ?>/contracts/create" class="grid grid-cols-1 gap-3 rounded-2xl border border-white/10 bg-black/20 p-5 md:grid-cols-2 xl:grid-cols-3">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Cliente
                    <input name="customer_name" maxlength="120" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white" placeholder="Nome do cliente">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Placa
                    <input name="vehicle_plate" maxlength="8" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base uppercase tracking-normal text-white" placeholder="ABC-1D23">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Veículo
                    <select name="vehicle_type" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white">
                        <?php foreach ($vehicleTypes as $type => $metadata): ?><option value="<?= $escape($type) ?>"><?= $escape($metadata['label']) ?></option><?php endforeach; ?>
                    </select>
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Mensalidade
                    <input type="number" name="monthly_amount" min="0.01" step="0.01" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base text-white" placeholder="150,00">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Início
                    <input type="date" name="starts_at" value="<?= date('Y-m-d') ?>" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base text-white">
                </label>
                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Pagamento
                    <select name="payment_method" required class="mt-2 w-full rounded-xl border border-white/10 bg-[#131720] px-4 py-3 text-base normal-case tracking-normal text-white">
                        <option value="PIX">PIX</option><option value="CARD">Cartão</option><option value="CASH">Dinheiro</option>
                    </select>
                </label>
                <button class="md:col-span-2 xl:col-span-3 inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-6 py-3 font-black text-slate-950 hover:bg-amber-400">
                    <i data-lucide="file-plus-2" class="h-5 w-5"></i>Criar contrato e registrar pagamento
                </button>
            </form>

            <div class="mt-6 space-y-3">
                <?php if (empty($monthlyContracts)): ?>
                    <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center text-sm text-slate-500">Nenhum contrato mensalista cadastrado.</div>
                <?php endif; ?>
                <?php foreach (($monthlyContracts ?? []) as $contract): ?>
                    <?php
                    $isCancelled = $contract['status'] === 'CANCELLED';
                    $isExpired = !$isCancelled && $contract['expires_at'] < date('Y-m-d');
                    $statusLabel = $isCancelled ? 'Cancelado' : ($isExpired ? 'Vencido' : 'Ativo');
                    $statusClass = $isCancelled || $isExpired ? 'text-rose-400 bg-rose-500/10' : 'text-emerald-400 bg-emerald-500/10';
                    ?>
                    <article class="rounded-2xl border border-white/10 bg-black/20 p-5">
                        <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="text-lg text-white"><?= $escape($contract['customer_name']) ?></strong>
                                    <span class="rounded-lg px-2 py-1 text-[9px] font-black uppercase <?= $statusClass ?>"><?= $statusLabel ?></span>
                                </div>
                                <p class="mt-1 text-sm text-slate-400"><?= $escape($contract['vehicle_plate']) ?> · <?= $escape($vehicleTypes[$contract['vehicle_type']]['label'] ?? $contract['vehicle_type']) ?></p>
                                <p class="mt-1 text-xs text-slate-600">Vigência <?= date('d/m/Y', strtotime($contract['starts_at'])) ?> a <?= date('d/m/Y', strtotime($contract['expires_at'])) ?> · R$ <?= number_format((float) $contract['monthly_amount'], 2, ',', '.') ?></p>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <form method="POST" action="/admin/companies/company_id=<?= (int) $company['id'] ?>/contracts/renew" class="flex gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                                    <input type="number" name="monthly_amount" value="<?= $escape($contract['monthly_amount']) ?>" min="0.01" step="0.01" aria-label="Valor da renovação" class="w-28 rounded-xl border border-white/10 bg-[#131720] px-3 text-white">
                                    <select name="payment_method" aria-label="Pagamento" class="rounded-xl border border-white/10 bg-[#131720] px-3 text-white"><option>PIX</option><option value="CARD">Cartão</option><option value="CASH">Dinheiro</option></select>
                                    <button class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Renovar</button>
                                </form>
                                <?php if (!$isCancelled): ?>
                                    <form method="POST" action="/admin/companies/company_id=<?= (int) $company['id'] ?>/contracts/cancel">
                                        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>"><input type="hidden" name="contract_id" value="<?= (int) $contract['id'] ?>">
                                        <button class="w-full rounded-xl border border-rose-500/20 px-4 py-3 text-sm font-black text-rose-400">Cancelar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>

<script>
document.querySelectorAll('[data-company-logo-input]').forEach((input) => {
    const dropzone = input.closest('label');
    const label = dropzone?.querySelector('[data-company-logo-label]');
    input.addEventListener('change', () => {
        if (label && input.files?.[0]) label.textContent = input.files[0].name;
    });
    ['dragenter', 'dragover'].forEach((eventName) => dropzone?.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.add('border-indigo-500');
    }));
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('border-indigo-500'));
    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-indigo-500');
        if (event.dataTransfer?.files?.length) {
            input.files = event.dataTransfer.files;
            input.dispatchEvent(new Event('change'));
        }
    });
});
</script>
