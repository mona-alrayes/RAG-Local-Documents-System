const DOCUMENT_POLL_INTERVAL_MS = 5000;
const DOCUMENT_POLL_MAX_FAILURES = 3;

function startDocumentPolling() {
    const container = document.querySelector('[data-document-poll-url]');

    if (!container) {
        return;
    }

    const pollUrl = container.dataset.documentPollUrl;
    const errorMessage = container.querySelector('[data-document-poll-error]');

    if (!pollUrl) {
        return;
    }

    const storageKey = `document-poll:${pollUrl}`;
    let consecutiveFailures = 0;

    const showPollingError = () => {
        if (errorMessage) {
            errorMessage.hidden = false;
        }
    };

    const scheduleNextPoll = () => {
        window.setTimeout(poll, DOCUMENT_POLL_INTERVAL_MS);
    };

    const handlePollingFailure = () => {
        consecutiveFailures += 1;

        if (consecutiveFailures >= DOCUMENT_POLL_MAX_FAILURES) {
            showPollingError();

            return;
        }

        scheduleNextPoll();
    };

    const poll = async () => {
        try {
            const response = await fetch(pollUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                handlePollingFailure();

                return;
            }

            consecutiveFailures = 0;

            const payload = await response.json();
            const pollRequired = payload?.data?.summary?.poll_required === true;
            const snapshot = JSON.stringify(payload?.data ?? null);
            const previousSnapshot = sessionStorage.getItem(storageKey);

            /*
             * The first successful request becomes the browser baseline.
             * Do not reload merely because sessionStorage was empty.
             *
             * If processing already became terminal before this first poll,
             * reload immediately so Laravel can render the terminal state.
             */
            if (previousSnapshot === null) {
                if (!pollRequired) {
                    sessionStorage.removeItem(storageKey);
                    window.location.reload();

                    return;
                }

                sessionStorage.setItem(storageKey, snapshot);
                scheduleNextPoll();

                return;
            }

            if (snapshot !== previousSnapshot) {
                sessionStorage.setItem(storageKey, snapshot);

                if (!pollRequired) {
                    sessionStorage.removeItem(storageKey);
                }

                window.location.reload();

                return;
            }

            if (!pollRequired) {
                sessionStorage.removeItem(storageKey);

                return;
            }

            scheduleNextPoll();
        } catch {
            handlePollingFailure();
        }
    };

    scheduleNextPoll();
}

startDocumentPolling();