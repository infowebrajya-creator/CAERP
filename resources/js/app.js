import * as bootstrap from 'bootstrap';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Chart = Chart;

// Kanban drag-and-drop status updates
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.kanban-card[data-task-id]');
    const columns = document.querySelectorAll('.kanban-dropzone[data-status]');

    let draggedTaskId = null;

    cards.forEach((card) => {
        card.addEventListener('dragstart', () => {
            draggedTaskId = card.getAttribute('data-task-id');
            card.classList.add('opacity-50');
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('opacity-50');
        });
    });

    columns.forEach((col) => {
        col.addEventListener('dragover', (e) => {
            e.preventDefault();
            col.classList.add('border-primary');
        });
        col.addEventListener('dragleave', () => {
            col.classList.remove('border-primary');
        });
        col.addEventListener('drop', async (e) => {
            e.preventDefault();
            col.classList.remove('border-primary');
            const newStatus = col.getAttribute('data-status');
            if (!draggedTaskId || !newStatus) return;

            const draggedEl = document.querySelector(`.kanban-card[data-task-id="${draggedTaskId}"]`);
            if (draggedEl) {
                col.appendChild(draggedEl);
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            try {
                await fetch(`/tasks/${draggedTaskId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                    },
                    body: JSON.stringify({ status: newStatus }),
                });
                window.location.reload();
            } catch (err) {
                console.error('Status update failed', err);
            }
        });
    });
});
