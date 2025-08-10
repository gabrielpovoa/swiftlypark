<?php $this->partial('head', ['title' => $title]); ?>

<section class="text-white font-sans rounded-lg">
    <h1 class="text-3xl font-bold mb-8"><?= htmlspecialchars($details['title']) ?></h1>

    <div class="flex items-center gap-8 mb-8">
        <img src="<?= htmlspecialchars($details['image']) ?>" alt="<?= htmlspecialchars($details['title']) ?>" class="w-28 h-28 object-contain rounded" />
        <div>
            <p class="text-lg leading-relaxed"><?= htmlspecialchars($details['description']) ?></p>
            <p class="mt-3 text-sm italic text-gray-300"><?= htmlspecialchars($details['discount']) ?></p>
        </div>
    </div>

    <form action="/vacancy/apply" method="POST" class="grid grid-cols-2 gap-8 w-full">
        <input type="hidden" name="type" value="<?= htmlspecialchars($_GET['type'] ?? 'carro') ?>" />
        <input type="hidden" name="id_vaga" value="<?= htmlspecialchars($id_vaga) ?>">

        <label class="flex flex-col">
            Nome completo:
            <input
                type="text"
                name="owner_name"
                required
                class="mt-2 p-4 rounded border border-blue-400 focus:border-blue-600 text-black transition"
                placeholder="Seu nome completo"
            />
        </label>

        <label class="flex flex-col">
            Telefone:
            <input
                type="tel"
                name="phone"
                required
                class="mt-2 p-4 rounded border border-blue-400 focus:border-blue-600 text-black transition"
                placeholder="(99) 99999-9999"
            />
        </label>

        <label class="flex flex-col">
            Placa do veículo:
            <input
                type="text"
                name="plate"
                required
                class="mt-2 p-4 rounded border border-blue-400 focus:border-blue-600 text-black uppercase transition"
                placeholder="ABC-1234"
                maxlength="8"
            />
        </label>

        <label class="flex flex-col">
            Valor pago pela vaga (R$):
            <input
                type="number"
                name="paid_amount"
                required
                step="0.01"
                min="0"
                class="mt-2 p-4 rounded border border-blue-400 focus:border-blue-600 text-black transition"
                placeholder="Ex: 25.00"
            />
        </label>

        <label class="flex flex-col">
            Horário de entrada:
            <input
                type="time"
                name="entry_time"
                required
                class="mt-2 p-4 rounded border border-blue-400 focus:border-blue-600 text-black transition"
            />
        </label>

        <label class="flex flex-col opacity-70 cursor-not-allowed">
            Horário de saída:
            <input
                type="time"
                name="exit_time"
                disabled
                class="mt-2 p-4 rounded border border-blue-400 bg-transparent text-black transition"
                placeholder="Somente ao sair"
            />
        </label>

        <!-- Botão ocupa as duas colunas -->
        <button
            type="submit"
            class="cursor-pointer col-span-2 bg-[#2B4570] hover:bg-[#27457a] text-white font-semibold py-5 rounded transition-colors"
        >
            Estacionar
        </button>
    </form>
</section>
