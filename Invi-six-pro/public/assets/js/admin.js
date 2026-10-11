document.addEventListener('DOMContentLoaded', () => {

    /*
    |--------------------------------------------------------------------------
    | Abas
    |--------------------------------------------------------------------------
    */

    const adminTabs =
        document.querySelectorAll(
            '[data-admin-tab]'
        );

    const adminPanels =
        document.querySelectorAll(
            '[data-admin-panel]'
        );

    adminTabs.forEach((button) => {

        button.addEventListener(
            'click',
            () => {

                const target =
                    button.dataset.adminTab;

                adminTabs.forEach(
                    (tab) => {

                        tab.classList.remove(
                            'bg-neutral-950',
                            'text-white'
                        );

                        tab.classList.add(
                            'text-neutral-500'
                        );

                    }
                );

                button.classList.remove(
                    'text-neutral-500'
                );

                button.classList.add(
                    'bg-neutral-950',
                    'text-white'
                );

                adminPanels.forEach(
                    (panel) => {

                        panel.classList.toggle(
                            'hidden',
                            panel.dataset.adminPanel !==
                                target
                        );

                    }
                );

            }
        );

    });

    /*
    |--------------------------------------------------------------------------
    | Filtros de solicitações
    |--------------------------------------------------------------------------
    */

    const filterButtons =
        document.querySelectorAll(
            '[data-request-filter]'
        );

    const requestCards =
        document.querySelectorAll(
            '[data-request-card]'
        );

    filterButtons.forEach((button) => {

        button.addEventListener(
            'click',
            () => {

                const filter =
                    button.dataset.requestFilter;

                filterButtons.forEach(
                    (filterButton) => {

                        filterButton.classList.remove(
                            'bg-neutral-950',
                            'text-white'
                        );

                        filterButton.classList.add(
                            'text-neutral-500'
                        );

                    }
                );

                button.classList.remove(
                    'text-neutral-500'
                );

                button.classList.add(
                    'bg-neutral-950',
                    'text-white'
                );

                requestCards.forEach(
                    (card) => {

                        const visible =
                            filter === 'all' ||
                            card.dataset.status ===
                                filter;

                        card.classList.toggle(
                            'hidden',
                            !visible
                        );

                    }
                );

            }
        );

    });

    /*
    |--------------------------------------------------------------------------
    | Busca de usuários
    |--------------------------------------------------------------------------
    */

    const userSearch =
        document.getElementById(
            'user-search'
        );

    const userCards =
        document.querySelectorAll(
            '[data-user-card]'
        );

    if (userSearch) {

        userSearch.addEventListener(
            'input',
            () => {

                const value =
                    userSearch.value
                        .trim()
                        .toLowerCase();

                userCards.forEach(
                    (card) => {

                        const searchable =
                            (
                                card.dataset.userSearch
                                ?? ''
                            ).toLowerCase();

                        card.classList.toggle(
                            'hidden',
                            !searchable.includes(
                                value
                            )
                        );

                    }
                );

            }
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Gerador criptograficamente seguro
    |--------------------------------------------------------------------------
    */

    function secureRandomIndex(
        maximum
    ) {

        const limit =
            Math.floor(
                0x100000000 /
                maximum
            ) * maximum;

        const values =
            new Uint32Array(1);

        do {

            crypto.getRandomValues(
                values
            );

        } while (
            values[0] >= limit
        );

        return values[0] %
            maximum;
    }

    function randomCharacter(
        characters
    ) {

        return characters[
            secureRandomIndex(
                characters.length
            )
        ];
    }

    function shuffleSecure(
        characters
    ) {

        const result =
            [...characters];

        for (
            let i =
                result.length - 1;
            i > 0;
            i--
        ) {

            const j =
                secureRandomIndex(
                    i + 1
                );

            [
                result[i],
                result[j]
            ] = [
                result[j],
                result[i]
            ];

        }

        return result.join('');
    }

    function generatePassword() {

        const lowercase =
            'abcdefghijkmnopqrstuvwxyz';

        const uppercase =
            'ABCDEFGHJKLMNPQRSTUVWXYZ';

        const numbers =
            '23456789';

        const specials =
            '!@#$%&*?';

        const all =
            lowercase +
            uppercase +
            numbers +
            specials;

        const password = [
            randomCharacter(lowercase),
            randomCharacter(uppercase),
            randomCharacter(numbers),
            randomCharacter(specials)
        ];

        while (
            password.length < 12
        ) {

            password.push(
                randomCharacter(all)
            );

        }

        return shuffleSecure(
            password
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Aprovação
    |--------------------------------------------------------------------------
    */

    const approveModal =
        document.getElementById(
            'approve-modal'
        );

    const approveRequestId =
        document.getElementById(
            'approve-request-id'
        );

    const approvePlayerName =
        document.getElementById(
            'approve-player-name'
        );


    const approveButtons =
        document.querySelectorAll(
            '[data-approve-request]'
        );

    const closeApproveButtons =
        document.querySelectorAll(
            '[data-close-approve]'
        );

    function openApproveModal(
        requestId,
        player
    ) {

        approveRequestId.value =
            requestId;

        approvePlayerName.textContent =
            player;


        window.LobbyUI.openModal(approveModal);

        window.setTimeout(
            () => {
                approveModal.querySelector('button[type="submit"]').focus();
            },
            50
        );
    }

    function closeApproveModal() {

        window.LobbyUI.closeModal(approveModal);
    }

    approveButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    openApproveModal(
                        button.dataset
                            .approveRequest,
                        button.dataset.player
                    );

                }
            );

        }
    );

    closeApproveButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                closeApproveModal
            );

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Rejeição
    |--------------------------------------------------------------------------
    */

    const rejectModal =
        document.getElementById(
            'reject-modal'
        );

    const rejectRequestId =
        document.getElementById(
            'reject-request-id'
        );

    const rejectPlayerName =
        document.getElementById(
            'reject-player-name'
        );

    const rejectButtons =
        document.querySelectorAll(
            '[data-reject-request]'
        );

    const closeRejectButtons =
        document.querySelectorAll(
            '[data-close-reject]'
        );

    function openRejectModal(
        requestId,
        player
    ) {

        rejectRequestId.value =
            requestId;

        rejectPlayerName.textContent =
            player;

        window.LobbyUI.openModal(rejectModal);
    }

    function closeRejectModal() {

        window.LobbyUI.closeModal(rejectModal);
    }

    rejectButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    openRejectModal(
                        button.dataset
                            .rejectRequest,
                        button.dataset.player
                    );

                }
            );

        }
    );

    closeRejectButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                closeRejectModal
            );

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Redefinir senha
    |--------------------------------------------------------------------------
    */

    const resetPasswordModal =
        document.getElementById(
            'reset-password-modal'
        );

    const resetPasswordUserId =
        document.getElementById(
            'reset-password-user-id'
        );

    const resetPasswordPlayerName =
        document.getElementById(
            'reset-password-player-name'
        );

    const resetPasswordInput =
        document.getElementById(
            'reset-password-input'
        );

    const resetPasswordButtons =
        document.querySelectorAll(
            '[data-reset-password]'
        );

    const closeResetPasswordButtons =
        document.querySelectorAll(
            '[data-close-reset-password]'
        );

    const generateResetPassword =
        document.getElementById(
            'generate-reset-password'
        );

    const toggleResetPassword =
        document.getElementById(
            'toggle-reset-password'
        );

    function openResetPasswordModal(
        userId,
        player
    ) {

        resetPasswordUserId.value =
            userId;

        resetPasswordPlayerName.textContent =
            player;

        resetPasswordInput.value = '';

        resetPasswordInput.type =
            'password';

        toggleResetPassword.textContent =
            'Mostrar';

        window.LobbyUI.openModal(resetPasswordModal);

        window.setTimeout(
            () => {
                resetPasswordInput.focus();
            },
            50
        );
    }

    function closeResetPasswordModal() {

        window.LobbyUI.closeModal(resetPasswordModal);
    }

    resetPasswordButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                () => {

                    openResetPasswordModal(
                        button.dataset
                            .resetPassword,
                        button.dataset.player
                    );

                }
            );

        }
    );

    closeResetPasswordButtons.forEach(
        (button) => {

            button.addEventListener(
                'click',
                closeResetPasswordModal
            );

        }
    );

    generateResetPassword.addEventListener(
        'click',
        () => {

            resetPasswordInput.value =
                generatePassword();

            resetPasswordInput.type =
                'text';

            toggleResetPassword.textContent =
                'Ocultar';

            resetPasswordInput.focus();
            resetPasswordInput.select();

        }
    );

    toggleResetPassword.addEventListener(
        'click',
        () => {

            const hidden =
                resetPasswordInput.type ===
                'password';

            resetPasswordInput.type =
                hidden
                    ? 'text'
                    : 'password';

            toggleResetPassword.textContent =
                hidden
                    ? 'Ocultar'
                    : 'Mostrar';

        }
    );

    /*
    |--------------------------------------------------------------------------
    | Fechar clicando fora
    |--------------------------------------------------------------------------
    */

    approveModal.addEventListener(
        'click',
        (event) => {

            if (
                event.target ===
                approveModal
            ) {
                closeApproveModal();
            }

        }
    );

    rejectModal.addEventListener(
        'click',
        (event) => {

            if (
                event.target ===
                rejectModal
            ) {
                closeRejectModal();
            }

        }
    );

    resetPasswordModal.addEventListener(
        'click',
        (event) => {

            if (
                event.target ===
                resetPasswordModal
            ) {
                closeResetPasswordModal();
            }

        }
    );

    /*
    |--------------------------------------------------------------------------
    | ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        (event) => {

            if (
                event.key !== 'Escape'
            ) {
                return;
            }

            closeApproveModal();
            closeRejectModal();
            closeResetPasswordModal();

        }
    );


});