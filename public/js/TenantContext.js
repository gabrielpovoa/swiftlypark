(function () {
    const STORAGE_KEY = 'swiftlypark.current_company_id';
    const root = window.SwiftlyParkTenant || {};

    function normalizeCompanyId(value) {
        const parsed = Number.parseInt(String(value || ''), 10);

        return Number.isInteger(parsed) && parsed > 0 ? String(parsed) : null;
    }

    function getCurrentCompanyId() {
        return normalizeCompanyId(root.currentCompanyId)
            || normalizeCompanyId(localStorage.getItem(STORAGE_KEY));
    }

    function setCurrentCompanyId(companyId) {
        const normalized = normalizeCompanyId(companyId);

        if (!normalized) {
            localStorage.removeItem(STORAGE_KEY);
            root.currentCompanyId = null;
            return;
        }

        localStorage.setItem(STORAGE_KEY, normalized);
        root.currentCompanyId = Number.parseInt(normalized, 10);
    }

    if (Object.prototype.hasOwnProperty.call(root, 'currentCompanyId')) {
        setCurrentCompanyId(root.currentCompanyId);
    } else {
        setCurrentCompanyId(localStorage.getItem(STORAGE_KEY));
    }

    const originalFetch = window.fetch ? window.fetch.bind(window) : null;
    if (originalFetch) {
        window.fetch = function (input, init) {
            const companyId = getCurrentCompanyId();
            const requestInit = init ? Object.assign({}, init) : {};
            const headers = new Headers(requestInit.headers || (input instanceof Request ? input.headers : undefined));

            if (companyId) {
                headers.set('X-Company-ID', companyId);
            }

            requestInit.headers = headers;

            return originalFetch(input, requestInit);
        };
    }

    function installAxiosInterceptor() {
        if (!window.axios || window.axios.__swiftlyParkTenantInterceptor) {
            return;
        }

        window.axios.interceptors.request.use((config) => {
            const companyId = getCurrentCompanyId();

            if (companyId) {
                config.headers = config.headers || {};
                config.headers['X-Company-ID'] = companyId;
            }

            return config;
        });
        window.axios.__swiftlyParkTenantInterceptor = true;
    }

    installAxiosInterceptor();
    document.addEventListener('DOMContentLoaded', installAxiosInterceptor);

    async function switchTenant(companyId) {
        const response = await fetch('/api/v1/tenant/switch', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ company_id: Number.parseInt(companyId, 10) }),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.error) {
            throw new Error(payload.error || 'Não foi possível trocar a empresa.');
        }

        setCurrentCompanyId(payload.current_company?.id || companyId);

        return payload;
    }

    async function switchGlobal() {
        const response = await fetch('/api/v1/tenant/global', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok || payload.error) {
            throw new Error(payload.error || 'Não foi possível ativar o contexto global.');
        }
        setCurrentCompanyId(null);

        return payload;
    }

    async function updateSupportProfile(form) {
        const formData = new FormData(form);
        const permissions = formData.getAll('extra_permissions[]')
            .map((value) => Number.parseInt(String(value), 10))
            .filter((value) => Number.isInteger(value) && value > 0);
        const response = await fetch('/api/v1/support/profile', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                simulated_role: String(formData.get('simulated_role') || ''),
                extra_permissions: permissions,
            }),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.error) {
            throw new Error(payload.error || 'Não foi possível atualizar o perfil de suporte.');
        }

        return payload;
    }

    function setSwitcherLoading(container, isLoading) {
        const select = container.querySelector('.tenant-switcher__select');
        const loading = container.querySelector('.tenant-switcher__loading');

        if (select) {
            select.disabled = isLoading;
        }

        if (loading) {
            loading.classList.toggle('hidden', !isLoading);
            loading.classList.toggle('flex', isLoading);
        }
    }

    function bindTenantSwitcher() {
        document.querySelectorAll('.tenant-switcher').forEach((container) => {
            const select = container.querySelector('.tenant-switcher__select');

            if (!select || select.dataset.bound === 'true') {
                return;
            }

            const renderedCompanyId = normalizeCompanyId(container.dataset.currentCompanyId);
            if (renderedCompanyId && select.querySelector(`option[value="${renderedCompanyId}"]`)) {
                select.value = renderedCompanyId;
                setCurrentCompanyId(renderedCompanyId);
            }

            select.dataset.bound = 'true';
            select.addEventListener('change', async () => {
                if (select.value === '__global__') {
                    setSwitcherLoading(container, true);
                    try {
                        const payload = await switchGlobal();
                        window.location.href = payload.redirect_url || '/admin/dashboard';
                    } catch (error) {
                        setSwitcherLoading(container, false);
                        window.Swal?.fire({
                            icon: 'error',
                            title: 'Troca bloqueada',
                            text: error.message,
                            background: '#111827',
                            color: '#e2e8f0',
                        });
                    }
                    return;
                }

                if (!normalizeCompanyId(select.value)) {
                    return;
                }

                const previousCompanyId = getCurrentCompanyId();

                setSwitcherLoading(container, true);

                try {
                    const payload = await switchTenant(select.value);
                    if (payload.redirect_url) {
                        window.location.href = payload.redirect_url;
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    if (previousCompanyId) {
                        select.value = previousCompanyId;
                    }

                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Troca bloqueada',
                            text: error.message,
                            background: '#111827',
                            color: '#f8fafc',
                            confirmButtonColor: '#2563eb',
                        });
                    } else {
                        alert(error.message);
                    }

                    setSwitcherLoading(container, false);
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', bindTenantSwitcher);

    function bindSupportProfile() {
        document.querySelectorAll('.support-profile__form').forEach((form) => {
            if (form.dataset.bound === 'true') {
                return;
            }

            const loading = form.querySelector('.support-profile__loading');
            const submit = form.querySelector('.support-profile__submit');
            form.dataset.bound = 'true';
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                if (submit) {
                    submit.disabled = true;
                    submit.classList.add('opacity-60');
                }

                if (loading) {
                    loading.classList.remove('hidden');
                    loading.classList.add('flex');
                }

                try {
                    const payload = await updateSupportProfile(form);
                    if (payload.redirect_url) {
                        window.location.href = payload.redirect_url;
                        return;
                    }

                    window.location.reload();
                } catch (error) {
                    if (window.Swal) {
                        window.Swal.fire({
                            icon: 'error',
                            title: 'Perfil bloqueado',
                            text: error.message,
                            background: '#111827',
                            color: '#f8fafc',
                            confirmButtonColor: '#f59e0b',
                        });
                    } else {
                        alert(error.message);
                    }

                    if (submit) {
                        submit.disabled = false;
                        submit.classList.remove('opacity-60');
                    }

                    if (loading) {
                        loading.classList.add('hidden');
                        loading.classList.remove('flex');
                    }
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', bindSupportProfile);

    window.SwiftlyParkTenant = Object.assign(root, {
        getCurrentCompanyId,
        setCurrentCompanyId,
        switchTenant,
        updateSupportProfile,
    });
})();
