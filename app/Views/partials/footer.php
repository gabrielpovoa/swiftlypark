<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$footerShellClass = isset($_SESSION['user_id'])
    ? 'ml-20 w-[calc(100%-5rem)]'
    : 'w-full';
?>

<footer class="<?= $footerShellClass ?> relative bg-[#0b0e14] text-slate-300 px-3 sm:px-6 lg:px-8 py-6 sm:py-8 lg:py-10 overflow-x-hidden">
    <div class="w-full max-w-7xl mx-auto">
        <div class="h-px w-full bg-gradient-to-r from-transparent via-blue-500/40 to-transparent mb-5 lg:mb-6"></div>

        <div class="relative overflow-hidden rounded-[1.75rem] lg:rounded-[2rem] bg-white/[0.03] border border-white/5 p-4 lg:p-6">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[280px] lg:w-[420px] h-[120px] lg:h-[160px] bg-blue-600/10 blur-[80px] lg:blur-[100px] rounded-full pointer-events-none"></div>

            <div class="relative grid grid-cols-1 xl:grid-cols-[1fr_auto_1fr] items-center gap-5 lg:gap-6">
                <div class="flex flex-col sm:flex-row items-center justify-center xl:justify-start gap-3 text-center xl:text-left">
                    <div class="flex h-11 w-11 lg:h-12 lg:w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-500">
                        <span class="text-2xl font-black italic">P</span>
                    </div>

                    <div>
                        <h2 class="text-xl lg:text-2xl font-black tracking-tighter text-white italic">
                            Swiftly<span class="text-blue-500">Park</span>
                        </h2>
                        <div class="mt-1 flex items-center justify-center lg:justify-start gap-2">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.22em] lg:tracking-[0.3em] text-emerald-500">
                                System Online
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 min-[420px]:grid-cols-2 lg:flex lg:flex-row items-stretch lg:items-center justify-center gap-3">
                    <a href="https://joao-povoa-filho.vercel.app/" target="_blank" rel="noopener noreferrer"
                       class="group inline-flex items-center justify-center gap-3 px-5 lg:px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white text-sm font-bold transition-all duration-300 hover:bg-blue-600 hover:border-blue-500 hover:shadow-[0_0_20px_rgba(37,99,235,0.3)]">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 lg:w-5 lg:h-5 text-blue-400 group-hover:text-white"></i>
                        <span>Portfolio</span>
                    </a>

                    <a href="https://www.linkedin.com/in/gabriel-limapovoa/" target="_blank" rel="noopener noreferrer"
                       class="group inline-flex items-center justify-center gap-3 px-5 lg:px-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-white text-sm font-bold transition-all duration-300 hover:bg-blue-600 hover:border-blue-500 hover:shadow-[0_0_20px_rgba(37,99,235,0.3)]">
                        <i data-lucide="external-link" class="w-4 h-4 lg:w-5 lg:h-5 text-blue-400 group-hover:text-white"></i>
                        <span>LinkedIn</span>
                    </a>
                </div>

                <div class="text-center xl:text-right">
                    <p class="text-xs sm:text-sm font-bold text-white leading-relaxed">
                        Desenvolvido por
                        <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">
                            João Gabriel Póvoa
                        </span>
                    </p>

                    <div class="mt-2 flex flex-wrap items-center justify-center xl:justify-end gap-x-2 lg:gap-x-3 gap-y-2 text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.14em] lg:tracking-[0.2em] text-slate-500">
                        <span>PHP 8.3</span>
                        <span class="h-1 w-1 rounded-full bg-slate-700"></span>
                        <span>Tailwind CSS</span>
                        <span class="h-1 w-1 rounded-full bg-slate-700"></span>
                        <span>v2.4.0</span>
                    </div>
                </div>
            </div>

            <div class="relative mt-5 lg:mt-6 pt-4 lg:pt-5 border-t border-white/5 text-center">
                <p class="text-[9px] lg:text-[10px] font-bold uppercase tracking-[0.14em] lg:tracking-[0.24em] text-slate-600 leading-relaxed">
                    &copy; <?= date('Y') ?> SwiftlyPark — Inteligência em Estacionamentos
                </p>
            </div>
        </div>
    </div>
</footer>

<script>
    (() => {
        const createFooterIcons = () => {
            if (window.lucide?.createIcons) {
                window.lucide.createIcons();
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', createFooterIcons);
        } else {
            createFooterIcons();
        }
    })();
</script>

<script src="/js/ReportLog.js"></script>
<script src="/js/GetFilterVacancy.js"></script>
<script src="/js/modalApplyVacancy.js"></script>
<script src="/js/FinishVacancy.js"></script>
<script src="/js/changePassword.js"></script>
</body>
</html>
