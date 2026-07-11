document.addEventListener("DOMContentLoaded", () => {
    const month = document.getElementById("finance-month");
    if (!month) return;

    let dailyChart;
    let methodChart;
    const currency = new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    });

    const load = async () => {
        const response = await fetch(`/finance/data?month=${encodeURIComponent(month.value)}`);
        if (!response.ok) {
            let message = "Falha ao carregar dados financeiros.";
            try {
                const payload = await response.json();
                message = payload.error || message;
            } catch (_) {
            }

            throw new Error(message);
        }

        const data = await response.json();
        const exportLink = document.getElementById("finance-export");
        const printLink = document.getElementById("finance-print");

        if (exportLink) {
            exportLink.href = `/finance/export?month=${encodeURIComponent(month.value)}`;
        }

        if (printLink) {
            printLink.href = `/finance/print?month=${encodeURIComponent(month.value)}`;
        }

        document.getElementById("gross-revenue").textContent =
            currency.format(data.metrics.gross_revenue);
        document.getElementById("net-revenue").textContent =
            currency.format(data.metrics.net_revenue);
        document.getElementById("average-ticket").textContent =
            currency.format(data.metrics.average_ticket);
        document.getElementById("occupancy-rate").textContent =
            `${data.metrics.occupancy_rate.toFixed(2)}%`;
        document.getElementById("adjustments-total").textContent =
            currency.format(data.metrics.adjustments_total);
        document.getElementById("yoy-percentage").textContent =
            data.metrics.yoy_percentage === null
                ? "Sem histórico"
                : `${data.metrics.yoy_percentage.toFixed(2)}%`;

        dailyChart?.destroy();
        methodChart?.destroy();
        if (window.Chart) {
            dailyChart = chart("daily-chart", "line", data.charts.daily_revenue);
            methodChart = chart("method-chart", "doughnut", data.charts.payment_methods);
        }
        renderOperatorRanking(data.operators.top_parking);
        document.querySelectorAll(".animate-pulse").forEach((element) =>
            element.classList.remove("animate-pulse")
        );
    };

    const renderError = (error) => {
        const container = document.getElementById("operator-ranking");
        if (container) {
            container.innerHTML = `
                <div class="rounded-2xl bg-rose-500/10 border border-rose-500/20 p-5 text-rose-300 font-bold">
                    ${escapeHtml(error.message || "Não foi possível carregar o BI financeiro.")}
                </div>
            `;
        }

        document.querySelectorAll(".animate-pulse").forEach((element) =>
            element.classList.remove("animate-pulse")
        );
    };

    const renderOperatorRanking = (operators) => {
        const container = document.getElementById("operator-ranking");
        if (!container) return;

        if (!operators.length) {
            container.innerHTML = `
                <div class="rounded-2xl bg-white/[0.02] border border-white/5 p-5 text-slate-500">
                    Nenhum check-in encontrado para o mês selecionado.
                </div>
            `;
            return;
        }

        container.innerHTML = operators.map((operator, index) => `
            <div class="rounded-2xl bg-white/[0.02] border border-white/5 p-5">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center font-black">
                            ${index + 1}
                        </div>
                        <div>
                            <strong class="block text-white font-black">${escapeHtml(operator.name)}</strong>
                            <span class="text-xs text-slate-500">${escapeHtml(operator.email || "E-mail não informado")}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-right">
                        <div>
                            <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Veículos</span>
                            <strong class="text-white text-xl">${operator.parked_count}</strong>
                        </div>
                        <div>
                            <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-black">Receita</span>
                            <strong class="text-emerald-400 text-xl">${currency.format(operator.generated_revenue)}</strong>
                        </div>
                    </div>
                </div>
                <div class="h-2 rounded-full bg-white/[0.05] overflow-hidden">
                    <div class="h-full rounded-full bg-blue-500" style="width: ${operator.share_percentage}%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-2">${operator.share_percentage.toFixed(2)}% dos check-ins do período</p>
            </div>
        `).join("");
    };

    const escapeHtml = (value) => String(value)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");

    const chart = (id, type, source) => new Chart(
        document.getElementById(id),
        {
            type,
            data: {
                labels: source.labels,
                datasets: [{
                    label: id === "daily-chart"
                        ? "Faturamento por dia"
                        : "Receita por forma de pagamento",
                    data: source.values,
                    borderColor: "#10b981",
                    backgroundColor: type === "line"
                        ? "rgba(16,185,129,.18)"
                        : ["#10b981", "#3b82f6", "#f59e0b", "#64748b"],
                    fill: type === "line",
                    tension: .35,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: "#94a3b8" } } },
                scales: type === "line"
                    ? {
                        x: { ticks: { color: "#64748b" }, grid: { color: "rgba(255,255,255,.04)" } },
                        y: { ticks: { color: "#64748b" }, grid: { color: "rgba(255,255,255,.04)" } },
                    }
                    : {},
            },
        }
    );

    month.addEventListener("change", () => load().catch(renderError));
    load().catch(renderError);
});
