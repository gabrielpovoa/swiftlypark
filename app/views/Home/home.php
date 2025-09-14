<?php $this->partial('head', ['title' => $title]); ?>
<?php $this->partial('header'); ?>

<main class="overflow-hidden flex items-start justify-start w-5/6 shadow-md bg-[#1F2937] px-8 py-8 ml-auto min-h-screen">
    <?= $content ?? '' ?>
</main>

<a href="https://wa.me/51990140347" target="_blank" class="fixed bottom-8 right-8 group">
    <div class="relative">
        <div class="absolute right-0 bottom-full mb-3 hidden group-hover:block px-4 py-2 text-sm bg-white text-gray-800 rounded-lg shadow-lg whitespace-nowrap">
            Contate nosso time de suporte
            <div class="absolute bottom-[-6px] right-3 w-3 h-3 bg-white transform rotate-45"></div>
        </div>

        <div class="mb-16 flex items-center justify-center bg-green-500 rounded-full shadow-lg cursor-pointer w-14 h-14 relative transition-all duration-300 animate-pulse">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-white lucide lucide-whatsapp"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
    </div>
</a>

<?php $this->partial('footer'); ?>

<style>
    .animate-pulse {
        animation: pulseNeon 2s infinite;
    }

    @keyframes pulseNeon {
        0%, 100% {
            box-shadow: 0 0 6px #10B98133, 0 0 12px #10B98122, 0 0 24px #10B98111;
        }
        50% {
            box-shadow: 0 0 12px #10B98155, 0 0 18px #10B98144, 0 0 28px #10B98133;
        }
    }
</style>