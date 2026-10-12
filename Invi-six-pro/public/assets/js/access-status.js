if (document.querySelector('[data-access-pending]')) {
    const poll = async () => {
        try {
            const response = await fetch('/solicitacao/status', {
                credentials: 'same-origin', cache: 'no-store',
                signal: AbortSignal.timeout(10000)
            });
            if (response.ok) {
                const data = await response.json();
                if (['approved', 'rejected', 'unavailable'].includes(data.status)) {
                    window.location.reload();
                    return;
                }
            }
        } catch { /* Retry on a temporary connection failure. */ }
        window.setTimeout(poll, 5000);
    };
    window.setTimeout(poll, 5000);
}
