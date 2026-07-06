<?php $this->partial('head', ['title' => $title]); ?>

<?php
$vehicleTypes = [
        'moto' => ['icon' => 'bike', 'title' => 'Moto', 'color' => 'text-amber-400', 'bg' => 'bg-amber-400/10'],
        'carro' => ['icon' => 'car', 'title' => 'Carro', 'color' => 'text-blue-400', 'bg' => 'bg-blue-400/10'],
        'caminhao' => ['icon' => 'truck', 'title' => 'Caminhão', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-400/10'],
        'app' => ['icon' => 'smartphone', 'title' => 'App', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-400/10'],
];

$type = $_GET['type'] ?? 'carro';
$vehicle = $vehicleTypes[$type] ?? $vehicleTypes['carro'];
?>

<section class="min-h-screen w-full bg-[#0b0e14] flex items-center justify-center p-4 md:p-10 relative overflow-hidden">

    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-600/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 w-full max-w-6xl relative z-10">

        <div class="lg:col-span-1 flex flex-col gap-6">
            <div class="bg-white/[0.02] backdrop-blur-xl border border-white/5 rounded-[2.5rem] p-8 flex flex-col items-center justify-center shadow-2xl transition-all duration-500 hover:border-blue-500/30">
                <div class="p-6 rounded-3xl <?= $vehicle['bg'] ?> mb-4">
                    <i data-lucide="<?= $vehicle['icon'] ?? 'car' ?>" class="w-16 h-16 <?= $vehicle['color'] ?? 'text-white' ?>"></i>
                </div>
                <h3 class="text-white text-2xl font-black italic tracking-tighter"><?= $vehicle['title'] ?></h3>
                <span class="text-slate-500 text-[10px] uppercase font-black tracking-[0.2em] mt-2">Vaga Selecionada</span>
            </div>

            <div class="hidden lg:flex flex-col bg-blue-600/10 border border-blue-500/20 rounded-[2rem] p-6">
                <div class="flex items-center gap-3 text-blue-400 mb-2">
                    <i data-lucide="info" class="w-5 h-5"></i>
                    <span class="font-bold text-xs uppercase tracking-widest">Dica de UX</span>
                </div>
                <p class="text-slate-400 text-xs leading-relaxed">Verifique a placa e o horário de entrada antes de confirmar para evitar erros no relatório.</p>
            </div>
        </div>

        <div class="lg:col-span-3 bg-white/[0.02] backdrop-blur-3xl border border-white/5 rounded-[2.5rem] p-8 md:p-12 shadow-2xl relative overflow-hidden">

            <header class="mb-10 text-center md:text-left">
                <h2 class="text-3xl md:text-4xl font-black text-white tracking-tighter italic">
                    Check-in de <span class="text-blue-500 italic uppercase">Entrada</span>
                </h2>
                <div class="h-1 w-20 bg-blue-600 mt-2 mx-auto md:ml-0 rounded-full"></div>
            </header>

            <?php if (!empty($errorMessage)): ?>
                <div class="flex items-center gap-3 bg-rose-500/10 border border-rose-500/20 text-rose-400 px-5 py-4 rounded-2xl mb-8 text-sm font-bold animate-pulse">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form action="/vacancy/apply" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>" />
                <input type="hidden" name="id_vaga" value="<?= htmlspecialchars($id_vaga) ?>">

                <div class="md:col-span-2 space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Proprietário / Condutor</label>
                    <div class="relative group">
                        <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                        <input type="text" name="owner_name" required
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all"
                               placeholder="Digite o nome completo">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Contato</label>
                    <div class="relative group">
                        <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                        <input type="tel" name="phone" required
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all"
                               placeholder="(00) 00000-0000">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Identificação (Placa)</label>
                    <div class="relative group">
                        <i data-lucide="hash" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                        <input type="text" name="plate" required maxlength="8"
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm uppercase font-bold tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all"
                               placeholder="BRA-2E19">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Pagamento Antecipado (R$)</label>
                    <div class="relative group">
                        <i data-lucide="dollar-sign" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-emerald-500 transition-colors w-5 h-5"></i>
                        <input type="number" name="paid_amount" required step="0.01"
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white placeholder-slate-600 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 transition-all"
                               placeholder="0,00">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Hora de Início</label>
                    <div class="relative group">
                        <i data-lucide="clock" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-blue-500 transition-colors w-5 h-5"></i>
                        <input
                               type="time"
                               lang="pt-BR"
                               name="entry_time"
                               id="entry_time"
                               required
                               value="<?php echo date('H:i'); ?>"
                               class="w-full pl-12 pr-4 py-4 rounded-2xl bg-white/[0.03] border border-white/5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50 transition-all"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Forma de Pagamento</label>
                    <div class="relative group">
                        <i data-lucide="wallet-cards" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 w-5 h-5"></i>
                        <select name="payment_method" required
                                class="w-full pl-12 pr-4 py-4 rounded-2xl bg-[#111722] border border-white/5 text-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50">
                            <option value="">Selecione</option>
                            <option value="PIX">Pix</option>
                            <option value="CARD">Cartão</option>
                            <option value="CASH">Dinheiro</option>
                        </select>
                    </div>
                </div>

                <button type="button" onclick="openConfirmationModal()"
                        class="md:col-span-2 w-full py-5 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-black uppercase tracking-[0.2em] text-xs transition-all duration-300 shadow-xl shadow-blue-600/20 active:scale-[0.98] flex items-center justify-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    Confirmar Estacionamento
                </button>
            </form>
        </div>
    </div>


    <div id="confirmModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-[#0b0e14]/80 backdrop-blur-sm">
        <div class="bg-[#1f2b4a] border border-white/10 w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl animate-in fade-in zoom-in duration-300">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-yellow-500/10 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-yellow-500/20">
                    <i data-lucide="search" class="w-8 h-8 text-yellow-500"></i>
                </div>
                <h3 class="text-white text-xl font-black italic tracking-tighter uppercase">Conferência de Dados</h3>
                <p class="text-slate-400 text-xs mt-1 font-bold">Revise as informações antes de salvar</p>
            </div>

            <div class="space-y-4 bg-white/[0.03] p-6 rounded-3xl border border-white/5 mb-8">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500 font-bold uppercase text-[10px]">Placa:</span>
                    <span id="reviewPlate" class="text-blue-400 font-black tracking-widest uppercase"></span>
                </div>
                <div class="flex justify-between text-sm border-t border-white/5 pt-4">
                    <span class="text-slate-500 font-bold uppercase text-[10px]">Entrada:</span>
                    <span id="reviewTime" class="text-white font-mono italic"></span>
                </div>
                <div class="flex justify-between text-sm border-t border-white/5 pt-4">
                    <span class="text-slate-500 font-bold uppercase text-[10px]">Valor:</span>
                    <span id="reviewPrice" class="text-emerald-400 font-black"></span>
                </div>
                <div class="flex justify-between text-sm border-t border-white/5 pt-4">
                    <span class="text-slate-500 font-bold uppercase text-[10px]">Pagamento:</span>
                    <span id="reviewPaymentMethod" class="text-white font-black"></span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <button onclick="closeConfirmationModal()" class="py-4 rounded-2xl bg-white/5 text-slate-400 font-bold text-xs uppercase tracking-widest hover:bg-white/10 transition-all">
                    Corrigir
                </button>
                <button onclick="submitRealForm()" class="py-4 rounded-2xl bg-blue-600 text-white font-black text-xs uppercase tracking-widest shadow-lg shadow-blue-600/20 hover:bg-blue-500 transition-all">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        function openConfirmationModal() {
            // Pegar os valores dos inputs
            const plate = document.getElementsByName('plate')[0].value;
            const time = document.getElementsByName('entry_time')[0].value;
            const price = document.getElementsByName('paid_amount')[0].value;
            const payment = document.getElementsByName('payment_method')[0];

            // Validar se não estão vazios antes de abrir
            if(!plate || !time || !price || !payment.value) {
                alert("Por favor, preencha todos os campos obrigatórios.");
                return;
            }

            // Injetar no Modal
            document.getElementById('reviewPlate').innerText = plate;
            document.getElementById('reviewTime').innerText = time;
            document.getElementById('reviewPrice').innerText = 'R$ ' + parseFloat(price).toLocaleString('pt-br', {minimumFractionDigits: 2});
            document.getElementById('reviewPaymentMethod').innerText = payment.options[payment.selectedIndex].text;

            // Mostrar modal
            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeConfirmationModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        function submitRealForm() {
            // Pegar o formulário real pelo ID ou Nome e enviar
            document.querySelector('form').submit();
        }
    </script>
</section>
