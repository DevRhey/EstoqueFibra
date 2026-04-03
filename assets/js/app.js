document.addEventListener('DOMContentLoaded', function () {
    applyBootstrapValidation();
    wireEditEquipmentModal();
    wireUsoModal();
    wireUsoTesteModal();
    wireDevolucaoModal();
    wireBatchForms();
    wireMovementHistoryFilters();
    wireMovementPrefillFromQuery();
    wireBarcodeScannerInMovementModals();
    wireTestesFilters();
    wireRelatoriosCardsFilters();
    staggerRevealAnimation();
});

function wireRelatoriosCardsFilters() {
    const searchInput = document.querySelector('.js-report-card-search');
    const sortSelect = document.querySelector('.js-report-sort-cards');
    const shortageOnlyInput = document.querySelector('.js-report-only-shortage');
    const clearBtn = document.querySelector('.js-report-clear-filters');
    const exportBtn = document.querySelector('.js-report-export-cards');
    const visibleCount = document.querySelector('.js-report-visible-count');
    const emptyState = document.querySelector('.js-report-cards-empty');
    const cards = Array.from(document.querySelectorAll('.js-report-card'));
    const cardsRow = cards.length ? cards[0].parentElement : null;

    if (!cards.length || !cardsRow) {
        return;
    }

    const parseIntData = function (card, attr) {
        return parseInt(card.getAttribute(attr) || '0', 10) || 0;
    };

    const compareBySort = function (left, right, sortMode) {
        if (sortMode === 'nome') {
            const nameLeft = (left.getAttribute('data-tech-name') || '').toString();
            const nameRight = (right.getAttribute('data-tech-name') || '').toString();
            return nameLeft.localeCompare(nameRight);
        }

        if (sortMode === 'saldo_asc') {
            const saldoLeft = parseIntData(left, 'data-saldo-total');
            const saldoRight = parseIntData(right, 'data-saldo-total');
            if (saldoLeft !== saldoRight) {
                return saldoLeft - saldoRight;
            }
            return (left.getAttribute('data-tech-name') || '').localeCompare(right.getAttribute('data-tech-name') || '');
        }

        if (sortMode === 'saldo_desc') {
            const saldoLeft = parseIntData(left, 'data-saldo-total');
            const saldoRight = parseIntData(right, 'data-saldo-total');
            if (saldoLeft !== saldoRight) {
                return saldoRight - saldoLeft;
            }
            return (left.getAttribute('data-tech-name') || '').localeCompare(right.getAttribute('data-tech-name') || '');
        }

        const reporLeft = parseIntData(left, 'data-repor-total');
        const reporRight = parseIntData(right, 'data-repor-total');
        if (reporLeft !== reporRight) {
            return reporRight - reporLeft;
        }

        const saldoLeft = parseIntData(left, 'data-saldo-total');
        const saldoRight = parseIntData(right, 'data-saldo-total');
        if (saldoLeft !== saldoRight) {
            return saldoLeft - saldoRight;
        }

        return (left.getAttribute('data-tech-name') || '').localeCompare(right.getAttribute('data-tech-name') || '');
    };

    const sortCards = function () {
        const sortMode = sortSelect ? (sortSelect.value || 'prioridade') : 'prioridade';
        const ordered = cards.slice().sort(function (left, right) {
            return compareBySort(left, right, sortMode);
        });

        ordered.forEach(function (card) {
            cardsRow.appendChild(card);
        });
    };

    const applyFilters = function () {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const shortageOnly = shortageOnlyInput ? shortageOnlyInput.checked : false;
        let visible = 0;

        sortCards();

        cards.forEach(function (card) {
            const techName = card.getAttribute('data-tech-name') || '';
            const hasShortage = (card.getAttribute('data-has-shortage') || '0') === '1';

            const searchMatch = !query || techName.indexOf(query) !== -1;
            const shortageMatch = !shortageOnly || hasShortage;
            const isVisible = searchMatch && shortageMatch;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) {
                visible++;
            }
        });

        if (visibleCount) {
            visibleCount.textContent = String(visible);
        }

        if (emptyState) {
            emptyState.classList.toggle('d-none', visible > 0);
        }
    };

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', applyFilters);
    }

    if (shortageOnlyInput) {
        shortageOnlyInput.addEventListener('change', applyFilters);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }

            if (shortageOnlyInput) {
                shortageOnlyInput.checked = false;
            }

            if (sortSelect) {
                sortSelect.value = 'prioridade';
            }

            applyFilters();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const visibleCards = cards.filter(function (card) {
                return card.style.display !== 'none';
            });

            if (!visibleCards.length) {
                window.alert('Nao ha cards visiveis para exportar.');
                return;
            }

            const headers = [
                'Tecnico',
                'Saldo em mao',
                'Reposicao pendente',
                'Total entregue',
                'Total uso',
                'Total devolvido',
                'Total recolhido',
                'Status estoque seguro',
            ];

            const rows = visibleCards.map(function (card) {
                const reporTotal = parseInt(card.getAttribute('data-repor-total') || '0', 10) || 0;
                return [
                    card.getAttribute('data-tech-label') || '',
                    parseInt(card.getAttribute('data-saldo-total') || '0', 10) || 0,
                    reporTotal,
                    parseInt(card.getAttribute('data-total-entrega') || '0', 10) || 0,
                    parseInt(card.getAttribute('data-total-uso') || '0', 10) || 0,
                    parseInt(card.getAttribute('data-total-devolvido') || '0', 10) || 0,
                    parseInt(card.getAttribute('data-total-recolhido') || '0', 10) || 0,
                    reporTotal > 0 ? 'Pendente' : 'OK',
                ];
            });

            const date = new Date();
            const y = String(date.getFullYear());
            const m = String(date.getMonth() + 1).padStart(2, '0');
            const d = String(date.getDate()).padStart(2, '0');

            if (window.XLSX) {
                const sheetData = [headers].concat(rows);
                const worksheet = window.XLSX.utils.aoa_to_sheet(sheetData);
                const workbook = window.XLSX.utils.book_new();
                window.XLSX.utils.book_append_sheet(workbook, worksheet, 'Resumo');
                window.XLSX.writeFile(workbook, 'relatorios-cards-' + y + m + d + '.xlsx');
                return;
            }

            const toCsvValue = function (value) {
                const safeValue = (value || '').toString().replace(/"/g, '""');
                return '"' + safeValue + '"';
            };

            const csvLines = [headers].concat(rows);
            const csvContent = csvLines
                .map(function (line) {
                    return line.map(toCsvValue).join(';');
                })
                .join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = 'relatorios-cards-' + y + m + d + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });
    }

    applyFilters();
}

function wireTestesFilters() {
    const techInput = document.querySelector('.js-testes-tech-filter');
    const statusSelect = document.querySelector('.js-testes-status-filter');
    const clearBtn = document.querySelector('.js-testes-clear-filters');
    const orderBtn = document.querySelector('.js-testes-toggle-order');
    const emptyState = document.querySelector('.js-testes-empty-filter');
    const visibleCountBadge = document.querySelector('.js-testes-visible-count');
    const tableBody = document.querySelector('.js-testes-rows');
    const rows = Array.from(document.querySelectorAll('.js-teste-row'));

    if (!rows.length || !tableBody) {
        return;
    }

    let orderMode = orderBtn ? (orderBtn.getAttribute('data-order') || 'asc') : 'asc';

    const getDueTs = function (row) {
        const value = parseInt(row.getAttribute('data-due-ts') || '0', 10);
        return Number.isNaN(value) ? 0 : value;
    };

    const sortRows = function () {
        const orderedRows = rows.slice().sort(function (a, b) {
            const dueA = getDueTs(a);
            const dueB = getDueTs(b);

            if (dueA === dueB) {
                return 0;
            }

            // asc = mais urgente primeiro
            if (orderMode === 'asc') {
                if (dueA === 0) {
                    return 1;
                }
                if (dueB === 0) {
                    return -1;
                }
                return dueA - dueB;
            }

            if (dueA === 0) {
                return 1;
            }
            if (dueB === 0) {
                return -1;
            }
            return dueB - dueA;
        });

        orderedRows.forEach(function (row) {
            tableBody.appendChild(row);
        });
    };

    const syncOrderLabel = function () {
        if (!orderBtn) {
            return;
        }
        orderBtn.textContent = orderMode === 'asc' ? 'Urgencia ↑' : 'Urgencia ↓';
        orderBtn.setAttribute('data-order', orderMode);
    };

    const applyFilters = function () {
        sortRows();

        const techQuery = (techInput ? techInput.value : '').toLowerCase().trim();
        const selectedStatus = statusSelect ? statusSelect.value : '';
        let visibleCount = 0;

        rows.forEach(function (row) {
            const techName = row.getAttribute('data-tech-name') || '';
            const status = row.getAttribute('data-status') || '';

            const techMatch = !techQuery || techName.indexOf(techQuery) !== -1;
            const statusMatch = !selectedStatus || status === selectedStatus;
            const visible = techMatch && statusMatch;

            row.style.display = visible ? '' : 'none';
            if (visible) {
                visibleCount++;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
        }

        if (visibleCountBadge) {
            visibleCountBadge.textContent = visibleCount + ' registro(s)';
        }
    };

    if (techInput) {
        techInput.addEventListener('input', applyFilters);
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', applyFilters);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (techInput) {
                techInput.value = '';
            }
            if (statusSelect) {
                statusSelect.value = '';
            }
            applyFilters();
        });
    }

    if (orderBtn) {
        orderBtn.addEventListener('click', function () {
            orderMode = orderMode === 'asc' ? 'desc' : 'asc';
            syncOrderLabel();
            applyFilters();
        });
        syncOrderLabel();
    }

    applyFilters();
}

function wireMovementPrefillFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const route = (params.get('route') || '').trim();

    if (route !== 'movimentacoes') {
        return;
    }

    const tipo = (params.get('tipo') || '').trim();
    const tecnicoId = parseInt(params.get('tecnico_id') || '0', 10);

    if (!tipo || tecnicoId <= 0) {
        return;
    }

    const configByType = {
        entrega: { modalId: 'modal-entrega', tecnicoSelector: '.js-entrega-tecnico' },
        uso: { modalId: 'modal-uso', tecnicoSelector: '.js-uso-tecnico' },
        uso_teste: { modalId: 'modal-uso-teste', tecnicoSelector: '.js-uso-teste-tecnico' },
        devolucao: { modalId: 'modal-devolucao', tecnicoSelector: '.js-devolucao-tecnico' },
        recolhimento: { modalId: 'modal-recolhimento', tecnicoSelector: '.js-recolhimento-tecnico' },
    };

    const config = configByType[tipo];
    if (!config) {
        return;
    }

    const modalElement = document.getElementById(config.modalId);
    if (!modalElement) {
        return;
    }

    const tecnicoSelect = modalElement.querySelector(config.tecnicoSelector) || modalElement.querySelector('[name="tecnico_id"]');
    if (!tecnicoSelect) {
        return;
    }

    const exists = Array.from(tecnicoSelect.options).some(function (option) {
        return parseInt(option.value || '0', 10) === tecnicoId;
    });

    if (!exists) {
        return;
    }

    tecnicoSelect.value = String(tecnicoId);
    tecnicoSelect.dispatchEvent(new Event('change', { bubbles: true }));

    if (window.bootstrap && window.bootstrap.Modal) {
        new window.bootstrap.Modal(modalElement).show();
    }

    const cleanedUrl = new URL(window.location.href);
    cleanedUrl.searchParams.delete('tipo');
    cleanedUrl.searchParams.delete('tecnico_id');
    const query = cleanedUrl.searchParams.toString();
    window.history.replaceState({}, '', query ? (cleanedUrl.pathname + '?' + query) : cleanedUrl.pathname);
}

function applyBootstrapValidation() {
    const forms = document.querySelectorAll('.needs-validation');

    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });
}

function wireEditEquipmentModal() {
    const buttons = document.querySelectorAll('.btn-edit-equip');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('edit-equip-id').value = button.getAttribute('data-id') || '';
            document.getElementById('edit-equip-nome').value = button.getAttribute('data-nome') || '';
            document.getElementById('edit-equip-tipo').value = button.getAttribute('data-tipo') || '';
            const codigoInput = document.getElementById('edit-equip-codigo');
            if (codigoInput) {
                codigoInput.value = button.getAttribute('data-codigo-barras') || '';
            }
            document.getElementById('edit-equip-quantidade').value = button.getAttribute('data-quantidade') || '0';
        });
    });
}

function wireUsoModal() {
    wireHandEquipmentModal('#modal-uso', '.js-uso-tecnico', '.js-uso-equipamento');
}

function wireDevolucaoModal() {
    wireHandEquipmentModal('#modal-devolucao', '.js-devolucao-tecnico', '.js-devolucao-equipamento');
}

function wireUsoTesteModal() {
    wireHandEquipmentModal('#modal-uso-teste', '.js-uso-teste-tecnico', '.js-uso-teste-equipamento');
}

function readMovementMap() {
    const mapElement = document.getElementById('movement-equipment-map');
    if (!mapElement) {
        return null;
    }

    try {
        return JSON.parse(mapElement.textContent || '{}');
    } catch (error) {
        return null;
    }
}

function renderHandEquipmentOptions(selectElement, items) {
    selectElement.innerHTML = '';
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = items.length ? 'Selecione um equipamento' : 'Nenhum equipamento em mão';
    selectElement.appendChild(emptyOption);

    items.forEach(function (item) {
        const option = document.createElement('option');
        option.value = item.id || item.equipamento_id || '';
        const codigoBarras = (item.codigo_barras || '').toString().trim();
        option.textContent = item.nome + ' | em mão: ' + item.saldo_mao + (codigoBarras ? ' | cód: ' + codigoBarras : '');
        if (codigoBarras) {
            option.setAttribute('data-codigo-barras', codigoBarras);
        }
        selectElement.appendChild(option);
    });

    selectElement.disabled = items.length === 0;
}

function wireHandEquipmentModal(modalSelector, techSelector, equipmentSelector) {
    const movementMap = readMovementMap();
    if (!movementMap) {
        return;
    }

    const form = document.querySelector(modalSelector + ' form');
    if (!form) {
        return;
    }

    const tecnicoSelect = form.querySelector(techSelector);
    const equipamentoSelect = form.querySelector(equipmentSelector);

    if (!tecnicoSelect || !equipamentoSelect) {
        return;
    }

    const syncOptions = function () {
        const tecnicoId = tecnicoSelect.value;
        if (!tecnicoId) {
            renderHandEquipmentOptions(equipamentoSelect, []);
            return;
        }

        const handItems = (movementMap.handByTechnician && movementMap.handByTechnician[tecnicoId]) ? movementMap.handByTechnician[tecnicoId] : [];
        renderHandEquipmentOptions(equipamentoSelect, handItems);
    };

    tecnicoSelect.addEventListener('change', syncOptions);

    const modalElement = document.querySelector(modalSelector);
    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', syncOptions);
    }
}

function normalizeBarcode(value) {
    return (value || '').toString().trim().replace(/\s+/g, '');
}

function wireBarcodeScannerInMovementModals() {
    const modalSelectors = ['#modal-entrega', '#modal-uso', '#modal-uso-teste', '#modal-recolhimento', '#modal-devolucao'];

    modalSelectors.forEach(function (modalSelector) {
        const modal = document.querySelector(modalSelector);
        if (!modal) {
            return;
        }

        if (modal.querySelector('.js-batch-form')) {
            return;
        }

        const scannerInput = modal.querySelector('.js-scan-equip');
        const equipmentSelect = modal.querySelector('[name="equipamento_id"]');
        if (!scannerInput || !equipmentSelect) {
            return;
        }

        const applyScan = function () {
            const scanned = normalizeBarcode(scannerInput.value);
            if (!scanned) {
                return;
            }

            const matchingOption = Array.from(equipmentSelect.options).find(function (option) {
                const code = normalizeBarcode(option.getAttribute('data-codigo-barras'));
                return code !== '' && code === scanned;
            });

            if (!matchingOption) {
                scannerInput.classList.add('is-invalid');
                scannerInput.classList.remove('is-valid');
                return;
            }

            equipmentSelect.value = matchingOption.value;
            equipmentSelect.dispatchEvent(new Event('change', { bubbles: true }));
            scannerInput.classList.remove('is-invalid');
            scannerInput.classList.add('is-valid');

            const quantityInput = modal.querySelector('[name="quantidade"]');
            if (quantityInput) {
                quantityInput.focus();
            }
        };

        scannerInput.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            applyScan();
        });

        scannerInput.addEventListener('input', function () {
            scannerInput.classList.remove('is-invalid');
            if (scannerInput.value.length >= 6) {
                applyScan();
            }
        });

        modal.addEventListener('shown.bs.modal', function () {
            scannerInput.value = '';
            scannerInput.classList.remove('is-valid', 'is-invalid');
            scannerInput.focus();
        });

        modal.addEventListener('hidden.bs.modal', function () {
            scannerInput.value = '';
            scannerInput.classList.remove('is-valid', 'is-invalid');
        });
    });
}

function wireBatchForms() {
    const forms = document.querySelectorAll('.js-batch-form');

    forms.forEach(function (form) {
        const addBtn = form.querySelector('.js-add-batch-item');
        const emptyText = form.querySelector('.js-batch-empty');
        const tableWrap = form.querySelector('.js-batch-table-wrap');
        const itemsBody = form.querySelector('.js-batch-items');
        const jsonInput = form.querySelector('.js-batch-json');

        if (!addBtn || !emptyText || !tableWrap || !itemsBody || !jsonInput) {
            return;
        }

        const tipoInput = form.querySelector('input[name="tipo"]');
        const tecnicoSelect = form.querySelector('[name="tecnico_id"]');
        const equipamentoSelect = form.querySelector('[name="equipamento_id"]');
        const quantidadeInput = form.querySelector('[name="quantidade"]');
        const localUsoInput = form.querySelector('[name="local_uso"]');
        const observacoesInput = form.querySelector('[name="observacoes"]');
        const scannerInput = form.querySelector('.js-scan-equip');
        const batchTotal = form.querySelector('.js-batch-total');
        const modalElement = form.closest('.modal');

        let batchItems = [];
        let batchTecnicoId = 0;
        let lastScannedBarcode = '';

        const getTipo = function () {
            return (tipoInput && tipoInput.value) ? tipoInput.value : '';
        };

        const tipoExigeLocal = function () {
            const tipo = getTipo();
            return tipo === 'uso' || tipo === 'uso_teste';
        };

        const getBatchTotalQuantity = function () {
            return batchItems.reduce(function (total, item) {
                return total + (parseInt(item.quantidade || '0', 10) || 0);
            }, 0);
        };

        const syncBatchTotal = function () {
            if (batchTotal) {
                batchTotal.textContent = String(getBatchTotalQuantity());
            }
        };

        const clearScanner = function () {
            if (!scannerInput) {
                return;
            }

            scannerInput.value = '';
            scannerInput.classList.remove('is-valid', 'is-invalid');
            scannerInput.focus();
            lastScannedBarcode = '';
        };

        const getSelectedEquipmentData = function () {
            if (!equipamentoSelect) {
                return null;
            }

            const selectedIndex = equipamentoSelect.selectedIndex;
            if (selectedIndex < 0) {
                return null;
            }

            const option = equipamentoSelect.options[selectedIndex];
            if (!option || !option.value) {
                return null;
            }

            return {
                equipamento_id: parseInt(option.value || '0', 10) || 0,
                equipamento_nome: option.getAttribute('data-label') || option.textContent.trim(),
            };
        };

        const upsertBatchItem = function (item) {
            const equipamentoId = parseInt(item.equipamento_id || '0', 10) || 0;
            const quantidade = parseInt(item.quantidade || '0', 10) || 0;

            if (equipamentoId <= 0 || quantidade <= 0) {
                return false;
            }

            const existingIndex = batchItems.findIndex(function (batchItem) {
                return (parseInt(batchItem.equipamento_id || '0', 10) || 0) === equipamentoId;
            });

            if (existingIndex >= 0) {
                batchItems[existingIndex].quantidade = (parseInt(batchItems[existingIndex].quantidade || '0', 10) || 0) + quantidade;

                if (!batchItems[existingIndex].local_uso && item.local_uso) {
                    batchItems[existingIndex].local_uso = item.local_uso;
                }

                if (!batchItems[existingIndex].observacoes && item.observacoes) {
                    batchItems[existingIndex].observacoes = item.observacoes;
                }

                return true;
            }

            batchItems.push({
                equipamento_id: equipamentoId,
                equipamento_nome: item.equipamento_nome || '',
                quantidade: quantidade,
                local_uso: item.local_uso || '',
                observacoes: item.observacoes || '',
            });

            return true;
        };

        const getEquipmentByBarcode = function (barcode) {
            if (!equipamentoSelect) {
                return null;
            }

            return Array.from(equipamentoSelect.options).find(function (option) {
                const code = normalizeBarcode(option.getAttribute('data-codigo-barras'));
                return code !== '' && code === barcode;
            }) || null;
        };

        const syncFieldRequirements = function () {
            const hasBatch = batchItems.length > 0;

            if (equipamentoSelect) {
                equipamentoSelect.required = !hasBatch;
            }

            if (quantidadeInput) {
                quantidadeInput.required = !hasBatch;
            }

            if (localUsoInput) {
                localUsoInput.required = !hasBatch && tipoExigeLocal();
            }
        };

        const renderBatchTable = function () {
            itemsBody.innerHTML = '';

            if (batchItems.length === 0) {
                batchTecnicoId = 0;
                emptyText.classList.remove('d-none');
                tableWrap.classList.add('d-none');
                jsonInput.value = '';
                syncBatchTotal();
                syncFieldRequirements();
                return;
            }

            batchItems.forEach(function (item, index) {
                const tr = document.createElement('tr');

                const tdEquip = document.createElement('td');
                tdEquip.textContent = item.equipamento_nome;
                tr.appendChild(tdEquip);

                const tdQtd = document.createElement('td');
                tdQtd.textContent = String(item.quantidade);
                tr.appendChild(tdQtd);

                if (tipoExigeLocal()) {
                    const tdLocal = document.createElement('td');
                    tdLocal.textContent = item.local_uso || '-';
                    tr.appendChild(tdLocal);
                }

                const tdAcoes = document.createElement('td');
                tdAcoes.className = 'text-end';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger';
                removeBtn.textContent = 'Remover';
                removeBtn.setAttribute('data-remove-index', String(index));
                tdAcoes.appendChild(removeBtn);
                tr.appendChild(tdAcoes);

                itemsBody.appendChild(tr);
            });

            emptyText.classList.add('d-none');
            tableWrap.classList.remove('d-none');
            jsonInput.value = JSON.stringify(batchItems);
            syncBatchTotal();
            syncFieldRequirements();
        };

        const addCurrentItem = function (quantityOverride) {
            const tecnicoId = tecnicoSelect ? parseInt(tecnicoSelect.value || '0', 10) : 0;
            const equipamentoData = getSelectedEquipmentData();
            const equipamentoId = equipamentoData ? equipamentoData.equipamento_id : 0;
            const quantidade = Number.isInteger(quantityOverride)
                ? quantityOverride
                : (quantidadeInput ? parseInt(quantidadeInput.value || '0', 10) : 0);
            const localUso = localUsoInput ? localUsoInput.value.trim() : '';
            const observacoes = observacoesInput ? observacoesInput.value.trim() : '';
            const equipamentoNome = equipamentoData ? equipamentoData.equipamento_nome : '';

            if (tecnicoId <= 0) {
                window.alert('Selecione o técnico antes de adicionar itens.');
                return;
            }

            if (batchTecnicoId > 0 && tecnicoId !== batchTecnicoId) {
                window.alert('Todos os itens do lote devem ser para o mesmo técnico.');
                return;
            }

            if (equipamentoId <= 0 || quantidade <= 0) {
                window.alert('Selecione o equipamento e informe uma quantidade válida.');
                return;
            }

            if (tipoExigeLocal() && localUso === '') {
                window.alert('Informe o local para este tipo de movimentação.');
                return;
            }

            if (batchTecnicoId === 0) {
                batchTecnicoId = tecnicoId;
            }

            if (!upsertBatchItem({
                equipamento_id: equipamentoId,
                equipamento_nome: equipamentoNome,
                quantidade: quantidade,
                local_uso: localUso,
                observacoes: observacoes,
            })) {
                window.alert('Selecione o equipamento e informe uma quantidade válida.');
                return;
            }

            if (equipamentoSelect) {
                equipamentoSelect.value = '';
            }
            if (quantidadeInput) {
                quantidadeInput.value = '1';
            }
            if (localUsoInput) {
                localUsoInput.value = '';
            }
            if (observacoesInput) {
                observacoesInput.value = '';
            }

            renderBatchTable();
        };

        const addScannedItem = function () {
            const tecnicoId = tecnicoSelect ? parseInt(tecnicoSelect.value || '0', 10) : 0;
            const scannedBarcode = scannerInput ? normalizeBarcode(scannerInput.value) : '';
            const localUso = localUsoInput ? localUsoInput.value.trim() : '';
            const observacoes = observacoesInput ? observacoesInput.value.trim() : '';

            if (tecnicoId <= 0) {
                window.alert('Selecione o técnico antes de ler o código de barras.');
                return;
            }

            if (!scannedBarcode || scannedBarcode === lastScannedBarcode) {
                return;
            }

            if (tipoExigeLocal() && localUso === '') {
                window.alert('Informe o local antes de iniciar a leitura deste tipo de movimentação.');
                return;
            }

            if (batchTecnicoId > 0 && tecnicoId !== batchTecnicoId) {
                window.alert('Todos os itens do lote devem ser para o mesmo técnico.');
                return;
            }

            const matchingOption = getEquipmentByBarcode(scannedBarcode);
            if (!matchingOption) {
                if (scannerInput) {
                    scannerInput.classList.add('is-invalid');
                    scannerInput.classList.remove('is-valid');
                }
                return;
            }

            if (batchTecnicoId === 0) {
                batchTecnicoId = tecnicoId;
            }

            const equipamentoId = parseInt(matchingOption.value || '0', 10) || 0;
            const equipamentoNome = matchingOption.getAttribute('data-label') || matchingOption.textContent.trim();

            if (!upsertBatchItem({
                equipamento_id: equipamentoId,
                equipamento_nome: equipamentoNome,
                quantidade: 1,
                local_uso: localUso,
                observacoes: observacoes,
            })) {
                return;
            }

            lastScannedBarcode = scannedBarcode;

            if (equipamentoSelect) {
                equipamentoSelect.value = matchingOption.value;
                equipamentoSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (quantidadeInput) {
                quantidadeInput.value = '1';
            }

            renderBatchTable();
            clearScanner();
        };

        addBtn.addEventListener('click', function () {
            addCurrentItem();
        });

        if (scannerInput) {
            scannerInput.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                addScannedItem();
            });

            scannerInput.addEventListener('input', function () {
                scannerInput.classList.remove('is-invalid');

                if ((scannerInput.value || '').trim().length >= 6) {
                    addScannedItem();
                }
            });
        }

        itemsBody.addEventListener('click', function (event) {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }

            const indexAttr = target.getAttribute('data-remove-index');
            if (indexAttr === null) {
                return;
            }

            const index = parseInt(indexAttr, 10);
            if (Number.isNaN(index) || !batchItems[index]) {
                return;
            }

            batchItems.splice(index, 1);
            renderBatchTable();
        });

        form.addEventListener('submit', function () {
            if (batchItems.length > 0) {
                if (tecnicoSelect && batchTecnicoId > 0) {
                    tecnicoSelect.value = String(batchTecnicoId);
                }
                jsonInput.value = JSON.stringify(batchItems);
                syncFieldRequirements();
            }
        });

        if (modalElement) {
            modalElement.addEventListener('shown.bs.modal', clearScanner);
            modalElement.addEventListener('hidden.bs.modal', function () {
                clearScanner();
                lastScannedBarcode = '';
            });
        }

        renderBatchTable();
    });
}

function wireMovementHistoryFilters() {
    const techInput = document.querySelector('.js-history-tech-filter');
    const typeSelect = document.querySelector('.js-history-type-filter');
    const expandBtn = document.querySelector('.js-history-expand-all');
    const collapseBtn = document.querySelector('.js-history-collapse-all');
    const emptyFilterState = document.querySelector('.js-history-empty-filter');
    const techItems = document.querySelectorAll('.history-tech-item');

    if (!techItems.length) {
        return;
    }

    const applyFilters = function () {
        const techQuery = (techInput ? techInput.value : '').toLowerCase().trim();
        const selectedType = typeSelect ? typeSelect.value : '';
        let visibleTechCount = 0;

        techItems.forEach(function (item) {
            const techName = item.getAttribute('data-tech-name') || '';
            const movementItems = item.querySelectorAll('.history-mov-item');
            const typeGroups = item.querySelectorAll('.history-type-group');
            let visibleMovements = 0;

            movementItems.forEach(function (movItem) {
                const movType = movItem.getAttribute('data-mov-type') || '';
                const typeMatch = !selectedType || movType === selectedType;
                movItem.style.display = typeMatch ? '' : 'none';
                if (typeMatch) {
                    visibleMovements++;
                }
            });

            const techMatch = !techQuery || techName.indexOf(techQuery) !== -1;
            const shouldShowTech = techMatch && visibleMovements > 0;
            item.style.display = shouldShowTech ? '' : 'none';
            if (shouldShowTech) {
                visibleTechCount++;
            }

            typeGroups.forEach(function (group) {
                const visibleInGroup = group.querySelectorAll('.history-mov-item:not([style*="display: none"])').length;
                group.style.display = visibleInGroup > 0 ? '' : 'none';
            });
        });

        if (emptyFilterState) {
            emptyFilterState.classList.toggle('d-none', visibleTechCount > 0);
        }
    };

    if (techInput) {
        techInput.addEventListener('input', applyFilters);
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', applyFilters);
    }

    if (expandBtn) {
        expandBtn.addEventListener('click', function () {
            techItems.forEach(function (item) {
                if (item.style.display === 'none') {
                    return;
                }
                const collapses = item.querySelectorAll('.accordion-collapse');
                if (!window.bootstrap || !window.bootstrap.Collapse) {
                    return;
                }

                collapses.forEach(function (collapse) {
                    new window.bootstrap.Collapse(collapse, { toggle: false }).show();
                });
            });
        });
    }

    if (collapseBtn) {
        collapseBtn.addEventListener('click', function () {
            techItems.forEach(function (item) {
                const collapses = item.querySelectorAll('.accordion-collapse');
                if (!window.bootstrap || !window.bootstrap.Collapse) {
                    return;
                }

                collapses.forEach(function (collapse) {
                    new window.bootstrap.Collapse(collapse, { toggle: false }).hide();
                });
            });
        });
    }

    applyFilters();
}

function staggerRevealAnimation() {
    const items = document.querySelectorAll('.reveal');

    items.forEach(function (item, index) {
        item.style.animationDelay = (index * 80) + 'ms';
    });
}
