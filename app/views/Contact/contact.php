<?php $this->partial('head', ['title' => $title]); ?>

<section class="flex items-center justify-center h-screen  px-4 py-6">
    <div class="flex gap-8">

        <!-- Formulário -->
        <div class="w-xl bg-[#364574] shadow-md rounded px-8 py-10">
            <h2 class="text-white text-3xl mb-8 font-semibold text-center">Precisa de Suporte?</h2>

            <?php if (!empty($errorMessage)) : ?>
                <p class="text-red-400 mb-6 text-center"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>

            <form action="Contact/SendSMTP" method="POST" class="space-y-6">
                <div>
                    <label for="name" class="block text-white font-medium mb-2">Nome</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-3 rounded bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Seu nome completo">
                </div>

                <div>
                    <label for="email" class="block text-white font-medium mb-2">Email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 rounded bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="seu@email.com">
                </div>

                <div>
                    <label for="message" class="block text-white font-medium mb-2">Mensagem</label>
                    <textarea id="message" name="message" rows="5" required
                              class="resize-none w-full px-4 py-3 rounded bg-[#485696] text-white placeholder-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Digite sua mensagem aqui..."></textarea>
                </div>

                <button type="submit"
                        class="w-full py-3 bg-blue-700 hover:bg-blue-800 rounded font-semibold text-white transition-colors duration-300">
                    Enviar Mensagem
                </button>
            </form>
        </div>

        <!-- Ticket -->
        <!-- Mensagem de erro -->
        <?php if (!empty($errorMessage)) : ?>
            <p class="text-red-400 mb-6 text-center"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <!-- Ticket -->
        <?php if (!empty($ticketNumber)) : ?>
            <div class="w-60 h-40 bg-[#0A2463] shadow-md rounded px-5 py-6 flex flex-col items-center justify-center text-[#DDDBF1]">
                <i data-lucide="ticket" class="w-8 h-8 mb-3"></i>
                <h3 class="text-lg font-semibold mb-2">Ticket Gerado</h3>
                <p class="text-xl font-mono mb-2"><?= htmlspecialchars($ticketNumber) ?></p>
                <p class="text-sm opacity-90">Seu e-mail foi enviado com sucesso.</p>
            </div>
        <?php endif; ?>



    </div>
</section>

