<?php $this->partial('head', ['title' => $title]); ?>

<section class="min-h-screen w-full bg-[#0f1117] text-slate-200 p-6 md:p-10 selection:bg-blue-500/30">

    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-end mb-10 gap-6">
        <div>
            <span class="text-blue-500 font-bold uppercase tracking-widest text-xs">Painel de Controle</span>
            <h1 class="text-4xl font-extrabold text-white tracking-tight mt-1">Vagas de Estacionamento</h1>
        </div>
        <a href="/vacancy"
           class="group flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-slate-300 transition-all duration-300">
            <i data-lucide="arrow-left" class="w-4 h-4 group-hover:-translate-x-1 transition-transform"></i>
            <span class="text-sm font-semibold">Painel Geral</span>
        </a>
    </div>

    <div class="max-w-7xl mx-auto mb-10">
        <form method="GET" action="/vacancy/manage"
              class="bg-white/[0.03] backdrop-blur-md border border-white/10 p-4 rounded-2xl flex flex-wrap md:flex-nowrap gap-4 items-center shadow-2xl">

            <div class="relative flex-grow md:flex-grow-0 md:w-64">
                <i data-lucide="layers" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"></i>
                <select name="categoria"
                        class="w-full bg-[#161b22] border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none appearance-none transition-all">
                    <option value="all">Todas Categorias</option>
                    <option value="carro" <?= ($filtros['categoria'] ?? '') === 'carro' ? 'selected' : '' ?>>Carros</option>
                    <option value="moto" <?= ($filtros['categoria'] ?? '') === 'moto' ? 'selected' : '' ?>>Motos</option>
                    <option value="caminhao" <?= ($filtros['categoria'] ?? '') === 'caminhao' ? 'selected' : '' ?>>Caminhões</option>
                </select>
            </div>

            <div class="relative flex-grow">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"></i>
                <input type="text" name="placa" placeholder="Buscar por placa (ex: ABC-1234)..."
                       value="<?= htmlspecialchars($filtros['placa'] ?? '') ?>"
                       class="w-full bg-[#161b22] border border-white/10 rounded-xl pl-11 pr-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
            </div>

            <button type="submit"
                    class="w-full md:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition-all shadow-lg shadow-blue-600/20 active:scale-95 flex items-center justify-center gap-2">
                <i data-lucide="filter" class="w-4 h-4"></i>
                Filtrar
            </button>
        </form>
    </div>

    <div id="vacancy-list" class="max-w-7xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        <?php if (!empty($vagas)):
            $mapaImagens = ['carro' => 'car.png', 'moto' => 'moto.png', 'caminhao' => 'truck.png', 'app' => 'uber.png'];
            foreach ($vagas as $vaga):
                $isLivre = $vaga['status'] === 'livre';
                $imagem = $mapaImagens[$vaga['categoria']] ?? 'default.png';
                ?>
                <div class="group relative bg-white/[0.03] border border-white/10 rounded-3xl p-6 transition-all duration-300 hover:border-blue-500/50 hover:bg-white/[0.06] shadow-xl overflow-hidden">

                    <div class="absolute left-0 top-0 bottom-0 w-1.5 <?= $isLivre ? 'bg-emerald-500' : 'bg-rose-500' ?>"></div>

                    <div class="flex justify-between items-start mb-4">
                    <span class="px-2 py-1 rounded-md bg-white/5 text-[10px] font-bold uppercase tracking-wider text-slate-400 border border-white/5">
                        ID: <?= $vaga['id_vaga'] ?>
                    </span>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full <?= $isLivre ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500' ?>"></span>
                            <span class="text-[10px] font-bold uppercase <?= $isLivre ? 'text-emerald-500' : 'text-rose-500' ?>">
                            <?= $isLivre ? 'Livre' : 'Ocupado' ?>
                        </span>
                        </div>
                    </div>

                    <div class="relative py-4 flex justify-center">
                        <img src="/images/<?= htmlspecialchars($imagem) ?>"
                             class="w-20 h-20 object-contain drop-shadow-[0_10px_10px_rgba(0,0,0,0.5)] group-hover:scale-110 transition-transform duration-500 <?= $isLivre ? 'grayscale opacity-30' : '' ?>"
                             alt="Veículo">
                    </div>

                    <div class="text-center mt-2">
                        <h3 class="text-xl font-black tracking-tighter <?= $isLivre ? 'text-slate-600' : 'text-white' ?>">
                            <?= $isLivre ? '---' : strtoupper($vaga['placa']) ?>
                        </h3>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-widest mt-1">
                            <?= ucfirst($vaga['categoria']) ?>
                        </p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-white/5">
                        <?php if (!$isLivre): ?>
                            <div class="flex flex-col gap-3">
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="text-slate-500">Entrada:</span>
                                    <span class="text-blue-400 font-mono font-bold"><?= date('H:i', strtotime($vaga['hora_entrada'])) ?></span>
                                </div>
                                <button class="finalizar w-full py-2.5 bg-rose-500/10 hover:bg-rose-500 text-rose-500 hover:text-white rounded-xl text-xs font-bold transition-all duration-300 border border-rose-500/20"
                                        data-id="<?= $vaga['id_vaga'] ?>">
                                    Finalizar Estadia
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="py-2 text-center text-[11px] text-slate-600 italic">
                                Aguardando veículo...
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; else: ?>
            <div class="col-span-full py-20 bg-white/[0.02] border border-dashed border-white/10 rounded-3xl text-center">
                <i data-lucide="parking-circle-off" class="w-12 h-12 text-slate-700 mx-auto mb-4"></i>
                <p class="text-slate-500 font-medium">Nenhuma vaga encontrada para os filtros aplicados.</p>
            </div>
        <?php endif; ?>
    </div>

</section>