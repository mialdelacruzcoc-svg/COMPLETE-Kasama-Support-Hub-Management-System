// JavaScript for coach-faq-manager.php

// ADD FAQ AJAX
        document.getElementById('addFaqForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = e.target.querySelector('button');
            const originalText = btn.textContent;
            btn.textContent = 'Publishing...';
            btn.disabled = true;

            const formData = new FormData(e.target);
            try {
                const res = await fetch('../../api/manage-faq.php', { method: 'POST', body: formData });
                const data = await res.json();
                if(data.success) { 
                    location.reload(); 
                } else { 
                    alert('Error: ' + data.message); 
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Connection error.');
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });

        // DELETE FAQ AJAX
        async function deleteFaq(id) {
            if(confirm('Sigurado ka i-delete kini? Kini mahanaw sab sa FAQ page sa mga estudyante.')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                try {
                    const res = await fetch('../../api/manage-faq.php', { method: 'POST', body: formData });
                    const data = await res.json();
                    if(data.success) { 
                        location.reload(); 
                    }
                } catch (err) {
                    alert('Failed to delete.');
                }
            }
        }

        // EDIT FAQ MODAL
        const editFaqModal = document.getElementById('editFaqModal');
        const editFaqForm = document.getElementById('editFaqForm');

        function openEditFaqModal(button) {
            const id = button.dataset.id || '';
            const question = button.dataset.question || '';
            const answer = button.dataset.answer || '';
            const category = button.dataset.category || 'Others';

            document.getElementById('editFaqId').value = id;
            document.getElementById('editQuestion').value = question;
            document.getElementById('editAnswer').value = answer;
            document.getElementById('editCategory').value = category;

            editFaqModal.classList.add('show');
            editFaqModal.setAttribute('aria-hidden', 'false');
        }

        function closeEditFaqModal() {
            editFaqModal.classList.remove('show');
            editFaqModal.setAttribute('aria-hidden', 'true');
        }

        if (editFaqForm) {
            editFaqForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('editFaqSubmitBtn');
                const originalText = btn.textContent;
                btn.textContent = 'Saving...';
                btn.disabled = true;

                const formData = new FormData(editFaqForm);

                try {
                    const res = await fetch('../../api/manage-faq.php', { method: 'POST', body: formData });
                    const data = await res.json();

                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Update failed'));
                        btn.textContent = originalText;
                        btn.disabled = false;
                    }
                } catch (err) {
                    alert('Connection error.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            });
        }

        // Close modal when clicking outside content
        if (editFaqModal) {
            editFaqModal.addEventListener('click', (e) => {
                if (e.target === editFaqModal) {
                    closeEditFaqModal();
                }
            });
        }
