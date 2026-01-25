<footer class="relative w-full max-w-7xl mx-auto mt-24 mb-10 px-6">
    <div class="w-full h-[1px] bg-gradient-to-r from-transparent via-blue-500/50 to-transparent mb-12"></div>

    <div class="relative bg-gradient-to-b from-white/[0.05] to-transparent backdrop-blur-2xl border border-white/10 rounded-[2.5rem] p-10 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">

        <div class="absolute -top-10 -left-10 w-32 h-32 bg-indigo-600/20 blur-[60px] rounded-full"></div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-blue-600/20 blur-[60px] rounded-full"></div>

        <div class="relative flex flex-col lg:flex-row items-center justify-between gap-10">

            <div class="flex flex-col items-center lg:items-start gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 bg-white text-blue-600 rounded-2xl flex items-center justify-center shadow-[0_0_20px_rgba(59,130,246,0.4)] transform -rotate-6 group-hover:rotate-0 transition-transform duration-500">
                        <span class="text-3xl font-black italic">P</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black tracking-tighter text-white italic">Swiftly<span class="text-blue-500">Park</span></h2>
                        <div class="flex items-center gap-2">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-500/80">System Online</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="https://joao-povoa-filho.vercel.app/" target="_blank"
                   class="group flex items-center gap-3 px-6 py-3 bg-white/5 border border-white/10 rounded-2xl hover:bg-blue-600 transition-all duration-500 hover:shadow-[0_0_20px_rgba(37,99,235,0.4)] hover:-translate-y-1">
                    <i data-lucide="layout" class="w-5 h-5 text-blue-400 group-hover:text-white transition-colors"></i>
                    <span class="text-sm font-bold text-slate-300 group-hover:text-white">Portfolio</span>
                </a>

                <a href="https://www.linkedin.com/in/gabriel-limapovoa/" target="_blank"
                   class="group flex items-center gap-3 px-6 py-3 bg-white/5 border border-white/10 rounded-2xl hover:bg-[#0077b5] transition-all duration-500 hover:shadow-[0_0_20px_rgba(0,119,181,0.4)] hover:-translate-y-1">
                    <i data-lucide="linkedin" class="w-5 h-5 text-[#0077b5] group-hover:text-white transition-colors"></i>
                    <span class="text-sm font-bold text-slate-300 group-hover:text-white">LinkedIn</span>
                </a>
            </div>

            <div class="flex flex-col items-center lg:items-end text-center lg:text-right">
                <p class="text-sm font-bold text-white tracking-tight">
                    Desenvolvido por <span class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">João Gabriel Póvoa</span>
                </p>
                <div class="mt-2 flex items-center gap-3 text-[10px] font-black uppercase tracking-widest text-slate-500">
                    <span>PHP 8.3</span>
                    <span class="w-1 h-1 rounded-full bg-slate-700"></span>
                    <span>Tailwind CSS</span>
                    <span class="w-1 h-1 rounded-full bg-slate-700"></span>
                    <span>v2.4.0</span>
                </div>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-white/5 text-center">
            <p class="text-[10px] text-slate-600 font-medium uppercase tracking-[0.3em]">
                &copy; <?= date('Y') ?> SwiftlyPark — Inteligência em Estacionamentos
            </p>
        </div>
    </div>
</footer>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        lucide.createIcons();
    });
</script>

<script src="/js/ReportLog.js"></script>
<script src="/js/GetFilterVacancy.js"></script>
<sript src="/js/modalApplyVacancy.js"></sript>
<script src="/js/FinishVacancy.js"></script>
<script src="/js/changePassword.js"></script>
</body>
</html>