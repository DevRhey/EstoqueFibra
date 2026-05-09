// Gerenciador de Lista de Compras Personalizada
(function() {
    'use strict';

    const STORAGE_KEY = 'purchase_personal_list';

    function init() {
        const addBtn = document.querySelector('.js-purchase-add-item');
        const selectInput = document.querySelector('.js-purchase-new-item-select');
        const customInput = document.querySelector('.js-purchase-new-item-custom');
        const qtyInput = document.querySelector('.js-purchase-new-item-qty');
        const container = document.getElementById('purchase-list-items-container');
        const emptyMsg = document.getElementById('purchase-empty-message');
        const listDiv = document.getElementById('purchase-list-items');
        const whatsappBtn = document.querySelector('.js-purchase-list-whatsapp');
        const printBtn = document.querySelector('.js-purchase-list-print');
        const clearBtn = document.querySelector('.js-purchase-list-clear');

        if (!addBtn || !selectInput || !qtyInput || !container) {
            return;
        }

        function getStoredItems() {
            try {
                return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            } catch (error) {
                return [];
            }
        }

        function saveStoredItems(items) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        }

        function getQuantity(item) {
            return Math.max(1, parseInt(item.qty, 10) || 1);
        }

        function getTotalQuantity(items) {
            return items.reduce((sum, item) => sum + getQuantity(item), 0);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function updateBadges(count, total) {
            const countBadge = document.querySelector('.js-purchase-list-count');
            const totalBadge = document.querySelector('.js-purchase-list-total-items');

            if (countBadge) {
                countBadge.textContent = String(count);
            }

            if (totalBadge) {
                totalBadge.textContent = String(total);
            }
        }

        function renderList() {
            const items = getStoredItems();
            container.innerHTML = '';

            if (!items.length) {
                if (listDiv) listDiv.classList.add('d-none');
                if (emptyMsg) emptyMsg.classList.remove('d-none');
                updateBadges(0, 0);
                return;
            }

            if (listDiv) listDiv.classList.remove('d-none');
            if (emptyMsg) emptyMsg.classList.add('d-none');

            items.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'dark-panel-subtle p-3 rounded d-flex justify-content-between align-items-center gap-2';

                const info = document.createElement('div');
                info.className = 'flex-grow-1 d-flex align-items-center gap-2';
                info.innerHTML = `
                    <strong>${escapeHtml(item.name)}</strong>
                    ${item.type ? `<span class="badge text-bg-secondary">${escapeHtml(item.type)}</span>` : ''}
                    <span class="badge text-bg-info">Qtd: ${getQuantity(item)}</span>
                `;
                row.appendChild(info);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger';
                removeBtn.textContent = 'Remover';
                removeBtn.addEventListener('click', function() {
                    const nextItems = getStoredItems();
                    nextItems.splice(index, 1);
                    saveStoredItems(nextItems);
                    renderList();
                });
                row.appendChild(removeBtn);

                container.appendChild(row);
            });

            updateBadges(items.length, getTotalQuantity(items));
        }

        function addItem() {
            const selectedValue = (selectInput.value || '').trim();
            const customValue = (customInput ? customInput.value : '').trim();
            const qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);

            let itemName = '';
            let itemType = '';

            if (selectedValue === 'custom') {
                if (!customValue) {
                    alert('Por favor, digite uma descrição para o item customizado.');
                    customInput.focus();
                    return;
                }
                itemName = customValue;
            } else if (selectedValue) {
                const selectedOption = selectInput.options[selectInput.selectedIndex];
                itemName = selectedOption.getAttribute('data-equip-name') || selectedOption.textContent || '';
                itemType = selectedOption.getAttribute('data-equip-type') || '';
            } else {
                alert('Selecione um equipamento ou escolha a opção customizada.');
                selectInput.focus();
                return;
            }

            const items = getStoredItems();
            items.push({ name: itemName, type: itemType, qty: qty });
            saveStoredItems(items);

            selectInput.value = '';
            if (customInput) {
                customInput.value = '';
                customInput.classList.add('d-none');
            }
            qtyInput.value = '1';
            renderList();
            selectInput.focus();
        }

        function buildPdfDocument(items) {
            const pdfNamespace = window.jspdf;
            if (!pdfNamespace || !pdfNamespace.jsPDF) {
                alert('A biblioteca de PDF não foi carregada.');
                return null;
            }

            const { jsPDF } = pdfNamespace;
            const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true });
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const marginX = 10;
            const marginTop = 10;
            const marginBottom = 10;
            const contentWidth = pageWidth - (marginX * 2);
            const totalItems = items.length;
            const totalQty = getTotalQuantity(items);
            const generatedAt = new Date().toLocaleString('pt-BR');

            const tableX = marginX;
            const tableTop = 28;
            const headerHeight = 6;
            const rowCount = Math.max(items.length, 1);
            const availableHeight = pageHeight - tableTop - marginBottom - 12;
            const baseRowHeight = 6.2;
            const scale = Math.min(1, availableHeight / ((rowCount + 1) * baseRowHeight));
            const fontSize = Math.max(4.8, 8.5 * scale);
            const rowHeight = Math.max(4.4, baseRowHeight * scale);
            const headerFontSize = Math.max(6, 7.8 * scale);
            const titleFontSize = Math.max(11, 15 * scale);
            const metaFontSize = Math.max(6, 8 * scale);
            const colNo = 10;
            const colQty = 18;
            const colItem = contentWidth - colNo - colQty;

            doc.setFont('helvetica', 'bold');
            doc.setTextColor(18, 48, 77);
            doc.setFontSize(titleFontSize);
            doc.text('Lista de Compras', marginX, marginTop + 4);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(metaFontSize);
            doc.setTextColor(93, 112, 132);
            doc.text('Conferência rápida antes da compra.', marginX, marginTop + 9);

            doc.setDrawColor(216, 226, 236);
            doc.setFillColor(248, 251, 255);
            doc.roundedRect(pageWidth - marginX - 58, marginTop, 58, 18, 1.5, 1.5, 'FD');

            doc.setFont('helvetica', 'bold');
            doc.setTextColor(106, 124, 145);
            doc.setFontSize(Math.max(5.5, metaFontSize - 0.5));
            doc.text('Data', pageWidth - marginX - 55, marginTop + 5);
            doc.text('Itens', pageWidth - marginX - 55, marginTop + 9);
            doc.text('Total', pageWidth - marginX - 55, marginTop + 13);

            doc.setFont('helvetica', 'normal');
            doc.setTextColor(16, 37, 59);
            doc.setFontSize(Math.max(6, metaFontSize - 0.2));
            doc.text(generatedAt, pageWidth - marginX - 40, marginTop + 5, { maxWidth: 37 });
            doc.text(String(totalItems), pageWidth - marginX - 40, marginTop + 9);
            doc.text(String(totalQty), pageWidth - marginX - 40, marginTop + 13);

            let currentY = tableTop;
            doc.setFillColor(18, 48, 77);
            doc.setDrawColor(216, 226, 236);
            doc.rect(tableX, currentY, contentWidth, headerHeight, 'F');
            doc.setTextColor(247, 251, 255);
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(headerFontSize);
            doc.text('#', tableX + 2, currentY + 4.1);
            doc.text('Item', tableX + colNo + 2, currentY + 4.1);
            doc.text('Qtd', tableX + colNo + colItem + 7, currentY + 4.1);
            currentY += headerHeight;

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(fontSize);

            items.forEach((item, index) => {
                const quantity = getQuantity(item);
                const label = String(item.name || '-');
                const typeLabel = item.type ? ` (${String(item.type)})` : '';
                const itemText = `${label}${typeLabel}`;
                const maxChars = Math.max(24, Math.floor(colItem / 1.7));
                const displayText = itemText.length > maxChars ? itemText.slice(0, maxChars - 1).trimEnd() + '…' : itemText;

                if (index % 2 === 1) {
                    doc.setFillColor(248, 251, 254);
                    doc.rect(tableX, currentY, contentWidth, rowHeight, 'F');
                }

                doc.setDrawColor(228, 235, 242);
                doc.line(tableX, currentY, tableX + contentWidth, currentY);
                doc.setTextColor(19, 37, 58);
                doc.text(String(index + 1), tableX + 2, currentY + (rowHeight * 0.72));
                doc.text(displayText, tableX + colNo + 2, currentY + (rowHeight * 0.72), { maxWidth: colItem - 2 });
                doc.text(String(quantity), tableX + colNo + colItem + 9, currentY + (rowHeight * 0.72), { align: 'center' });
                currentY += rowHeight;
            });

            doc.line(tableX, currentY, tableX + contentWidth, currentY);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(Math.max(5.5, metaFontSize - 0.7));
            doc.setTextColor(98, 116, 136);
            doc.text('Documento de conferência.', marginX, pageHeight - marginBottom + 1, { baseline: 'bottom' });
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(16, 37, 59);
            doc.text(`Total: ${totalQty} itens`, pageWidth - marginX, pageHeight - marginBottom + 1, {
                align: 'right',
                baseline: 'bottom',
            });

            return doc;
        }

        addBtn.addEventListener('click', function(event) {
            event.preventDefault();
            addItem();
        });

        selectInput.addEventListener('change', function() {
            if (!customInput) {
                return;
            }

            if (this.value === 'custom') {
                customInput.classList.remove('d-none');
                customInput.focus();
            } else {
                customInput.classList.add('d-none');
                customInput.value = '';
            }
        });

        [selectInput, customInput, qtyInput].forEach(function(input) {
            if (!input) {
                return;
            }

            input.addEventListener('keypress', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addBtn.click();
                }
            });
        });

        if (whatsappBtn) {
            whatsappBtn.addEventListener('click', function() {
                const items = getStoredItems();
                if (!items.length) {
                    alert('Adicione itens à lista primeiro.');
                    return;
                }

                const message = 'Lista de Compras - Estoque Fibra\nData: ' + new Date().toLocaleString('pt-BR') + '\n\n' +
                    items.map((item, index) => `${index + 1}. ${item.name}${item.type ? ' (' + item.type + ')' : ''} - Qtd: ${getQuantity(item)}`).join('\n') +
                    '\n\nTotal: ' + getTotalQuantity(items) + ' itens';

                const phone = (whatsappBtn.getAttribute('data-whatsapp-number') || '').replace(/\D/g, '');
                if (phone) {
                    window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(message), '_blank');
                }
            });
        }

        if (printBtn) {
            printBtn.addEventListener('click', function() {
                const items = getStoredItems();
                if (!items.length) {
                    alert('Adicione itens à lista primeiro.');
                    return;
                }

                const pdfDoc = buildPdfDocument(items);
                if (!pdfDoc) {
                    return;
                }

                pdfDoc.save('lista-compras.pdf');
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                if (confirm('Tem certeza que deseja limpar toda a lista?')) {
                    localStorage.removeItem(STORAGE_KEY);
                    renderList();
                }
            });
        }

        renderList();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
