<?php $this->partial('head', ['title' => $title]); ?>
<?php $this->partial('header'); ?>

<main class="relative flex-1 min-h-screen bg-[#0b0e14] transition-all duration-500 ease-in-out ml-20 group-hover:ml-72 overflow-x-hidden">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -right-[5%] w-[500px] h-[500px] bg-blue-600/5 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] -left-[5%] w-[400px] h-[400px] bg-indigo-600/5 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 min-h-screen animate-in fade-in slide-in-from-bottom-4 duration-700">
        <?php if (!empty($_SESSION['support_impersonation']['company_name'])): ?>
            <div class="sticky top-0 z-50 border-b border-amber-500/20 bg-amber-500/10 backdrop-blur-xl px-4 py-3 text-amber-100">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <i data-lucide="badge-alert" class="mt-0.5 h-5 w-5 shrink-0 text-amber-300"></i>
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em]">Modo de suporte ativo</p>
                            <p class="text-sm font-bold">
                                Você está atuando como Super-Admin na empresa
                                <span class="text-white"><?= htmlspecialchars((string) $_SESSION['support_impersonation']['company_name'], ENT_QUOTES, 'UTF-8') ?></span>,
                                visualizando como
                                <span class="text-white"><?= htmlspecialchars((string) ($_SESSION['support_impersonation']['simulated_role_label'] ?? $_SESSION['support_impersonation']['simulated_role'] ?? 'perfil simulado'), ENT_QUOTES, 'UTF-8') ?></span>.
                                Todas as ações serão auditadas.
                            </p>
                        </div>
                    </div>
                    <a href="/admin/dashboard" class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-400/30 bg-black/20 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-white hover:bg-black/30">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        Dashboard global
                    </a>
                </div>
            </div>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </div>

</main>


<div style="position: fixed; bottom: 30px; right: 30px; z-index: 99999; font-family: sans-serif;">

    <a href="https://wa.me/51990140347" target="_blank" class="whatsapp-btn" style="text-decoration: none; display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">

        <div class="ws-tooltip" style="background: #1e293b; color: #fff; padding: 8px 14px; border-radius: 12px; font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 1px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 10px 15px rgba(0,0,0,0.3); opacity: 0; transform: translateY(10px); transition: all 0.3s ease; white-space: nowrap;">
            Suporte Instantâneo
        </div>

        <div style="width: 60px; height: 60px; background: #10b981; border-radius: 18px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); position: relative; transition: all 0.3s ease;" class="ws-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                <path d="M12 7v6"></path>
                <path d="M9 10h6"></path>
            </svg>
        </div>
    </a>
</div>



<?php $this->partial('footer'); ?>

<style>

    main {
        transition: margin-left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .content-wrapper {
        max-width: 1600px;
        margin: 0 auto;
    }
    /* Interação manual para garantir que funcione sem Tailwind */
    .whatsapp-btn:hover .ws-tooltip {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    .whatsapp-btn:hover .ws-icon {
        transform: scale(1.1) rotate(5deg) !important;
        background: #059669 !important;
    }
    /* O efeito de pulso sutil */
    .ws-icon::after {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 18px;
        border: 2px solid #10b981;
        animation: pulseEffect 2s infinite;
    }
    @keyframes pulseEffect {
        0% { transform: scale(1); opacity: 0.5; }
        100% { transform: scale(1.6); opacity: 0; }
    }
</style>
