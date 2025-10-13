<?php $this->partial('head', ['title' => $title]); ?>

<section class="flex items-center justify-center min-h-screen bg-gradient-to-br from-[#1e2a47] to-[#121826] px-6 py-10">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 w-full max-w-5xl">

        <!-- Formulário -->
        <div class="bg-[#1f2b4a] shadow-lg rounded-2xl px-8 py-10 relative overflow-hidden">
            <h2 class="text-white text-3xl mb-8 font-semibold text-center">
                <?= htmlspecialchars($formTitle ?? '📩 Fale com a gente') ?>
            </h2>

            <?php if (!empty($errorMessage)) : ?>
                <p class="text-red-400 mb-6 text-center"><?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>

            <?php if (!empty($successMessage)) : ?>
                <p class="text-green-400 mb-6 text-center"><?= htmlspecialchars($successMessage) ?></p>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($formAction ?? '/Contact/SendSMTP') ?>" method="POST" class="space-y-6">

                <!-- Nome -->
                <div class="relative">
                    <i data-lucide="user" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required
                           class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="<?= htmlspecialchars($placeholderName ?? 'Seu nome completo') ?>">
                </div>

                <!-- Email -->
                <div class="relative">
                    <i data-lucide="mail" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300"></i>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required
                           class="w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="<?= htmlspecialchars($placeholderEmail ?? 'seu@email.com') ?>">
                </div>

                <!-- Mensagem -->
                <div class="relative">
                    <i data-lucide="message-square" class="absolute left-3 top-4 text-gray-300"></i>
                    <textarea id="message" name="message" rows="5" required
                              class="resize-none w-full pl-10 pr-4 py-3 rounded-lg bg-[#2d3a5c] text-white placeholder-gray-300
                                     focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                              placeholder="<?= htmlspecialchars($placeholderMessage ?? 'Digite sua mensagem aqui...') ?>"><?= htmlspecialchars($message ?? '') ?></textarea>
                </div>

                <!-- Botão -->
                <button type="submit"
                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold text-white flex items-center justify-center gap-2 transition-colors duration-300">
                    <i data-lucide="send"></i> <?= htmlspecialchars($buttonText ?? 'Enviar Mensagem') ?>
                </button>
            </form>
        </div>

        <!-- Ticket ou Info -->
        <div class="flex flex-col items-center justify-center text-center px-6">
            <?php if (!empty($ticketNumber)) : ?>
                <!-- Ticket -->
                <div class="w-72 bg-[#0A2463] shadow-lg rounded-2xl px-6 py-8 text-[#DDDBF1] animate-fadeIn">
                    <i data-lucide="ticket" class="w-10 h-10 mb-3 text-yellow-400"></i>
                    <h3 class="text-lg font-semibold mb-2"><?= htmlspecialchars($ticketTitle ?? 'Ticket Gerado') ?></h3>
                    <p class="text-2xl font-mono mb-2"><?= htmlspecialchars($ticketNumber) ?></p>
                    <p class="text-sm opacity-80"><?= htmlspecialchars($ticketMessage ?? 'Seu e-mail foi enviado com sucesso ✅') ?></p>
                </div>
            <?php else: ?>
                <!-- Info de contato extra -->
                <div class="text-gray-200">
                    <i data-lucide="help-circle" class="w-12 h-12 mb-4 text-blue-400"></i>
                    <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($contactTitle ?? 'Precisa de ajuda rápida?') ?></h3>
                    <p class="mb-1">📞 <?= htmlspecialchars($contactPhoneLabel ?? 'Ligue:') ?> <span class="font-semibold"><?= htmlspecialchars($contactPhone ?? '0800-123-456') ?></span></p>
                    <p class="mb-1">📧 <?= htmlspecialchars($contactEmail ?? 'suporte@swiftlypark.com') ?></p>
                    <p class="text-sm opacity-70"><?= htmlspecialchars($contactHours ?? 'Atendimento seg a sex, 8h às 18h') ?></p>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>
