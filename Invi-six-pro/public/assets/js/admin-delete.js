(() => {
    const modal = document.getElementById('delete-modal');
    let trigger;
    const close = () => {
        window.LobbyUI.closeModal(modal);
    };
    document.querySelectorAll('[data-delete-kind]').forEach(button => button.addEventListener('click', () => {
        trigger = button;
        const player = button.dataset.deleteKind === 'player';
        const title = player ? 'Remover jogador' : 'Excluir solicitação';
        document.getElementById('delete-label').textContent = title;
        document.getElementById('delete-title').textContent = title;
        document.getElementById('delete-player').textContent = button.dataset.player;
        document.getElementById('delete-description').textContent = player
            ? 'Esta ação removerá a conta e os dados pessoais vinculados deste jogador. O histórico compartilhado das partidas será preservado. Esta ação não poderá ser desfeita.'
            : 'Depois de excluir, essa pessoa poderá fazer uma nova solicitação. Esta ação não poderá ser desfeita.';
        document.getElementById('delete-form').action = player ? '/admin/user/delete' : '/admin/access/delete';
        const id = document.getElementById('delete-id');
        id.name = player ? 'user_id' : 'request_id'; id.value = button.dataset.deleteId;
        document.getElementById('delete-submit').textContent = title;
        window.LobbyUI.openModal(modal,{trigger,focus:modal.querySelector('[data-close-delete]')});
    }));
    modal.querySelector('[data-close-delete]').addEventListener('click', close);
    modal.addEventListener('click', event => { if (event.target === modal) close(); });
    document.addEventListener('keydown', event => {
        if (modal.classList.contains('hidden')) return;
        if (event.key === 'Escape') close();
        if (event.key === 'Tab') {
            const buttons = [...modal.querySelectorAll('button')];
            if (event.shiftKey && document.activeElement === buttons[0]) { event.preventDefault(); buttons.at(-1).focus(); }
            else if (!event.shiftKey && document.activeElement === buttons.at(-1)) { event.preventDefault(); buttons[0].focus(); }
        }
    });
})();
