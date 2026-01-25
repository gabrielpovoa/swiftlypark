<?php $this->partial('head', ['title' => $title]); ?>
<?php $this->partial('header'); ?>

<main class="overflow-hidden flex items-start justify-start w-5/6 shadow-md bg-[#1F2937] px-8 py-8 ml-auto min-h-screen">
    <?= $content ?? '' ?>
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