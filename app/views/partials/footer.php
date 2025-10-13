<footer class="bg-gradient-to-br from-blue-700/80 to-blue-900/80 text-white flex items-center justify-center gap-12 px-8 py-6 mt-12 h-32 w-5/6 ml-auto rounded-t-xl shadow-lg">

    <!-- Logo P + nome pequeno -->
    <div class="flex items-center gap-3">
        <div class="text-3xl font-bold font-mono select-none">P</div>
        <span class="text-sm font-semibold select-none">SwiftlyPark</span>
    </div>

    <!-- Links -->
    <div class="flex gap-8">
        <a href="https://joao-povoa-filho.vercel.app/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:underline transition-colors duration-300">
            <i data-lucide="external-link" class="w-5 h-5"></i>
            <span>Portfólio</span>
        </a>
        <a href="https://www.linkedin.com/in/gabriel-limapovoa/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:underline transition-colors duration-300">
            <i data-lucide="linkedin" class="w-5 h-5"></i>
            <span>LinkedIn</span>
        </a>
    </div>

    <!-- Copyright -->
    <div class="text-sm select-none whitespace-nowrap text-gray-200">
        &copy; <?= date('Y') ?> João Gabriel Póvoa. Todos os direitos reservados.
    </div>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        lucide.createIcons();
    });
</script>
<script src="/js/ReportLog.js"></script>
<script src="/js/GetFilterVacancy.js"></script>
<script src="/js/FinishVacancy.js"></script>
</body>
</html>