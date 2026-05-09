document.addEventListener('DOMContentLoaded', function () {
    wireStickyDateFilterState();
    wireClientLocalDateTimeForMovementForms();
    applyBootstrapValidation();
    wireEditEquipmentModal();
    wireEditTecnicoModal();
    wireEditHandBalanceModal();
    wireUsoModal();
    wireUsoTesteModal();
    wireRecolhimentoModal();
    wireRecolhimentoDefeitoModal();
    wireDevolucaoModal();
    wireBatchForms();
    wireDashboardMovementQuickActions();
    wireDashboardMovementModalPrefill();
    wireMovementHistoryFilters();
    wireMovementPrefillFromQuery();
    wireBarcodeScannerInMovementModals();
    wireTestesFilters();
    wireRelatoriosCardsFilters();
    wirePurchaseSupportFilters();
    wirePurchaseWhatsAppMessage();
    wireInadimplenciaImport();
    wireInadimplenciaEditModal();
    wireUsoTesteImport();
    staggerRevealAnimation();
});

function wireStickyDateFilterState() {
    const filters = document.querySelectorAll('.sticky-date-filter');
    if (!filters.length) {
        return;
    }

    const desktopQuery = window.matchMedia('(min-width: 992px)');

    const syncState = function () {
        if (!desktopQuery.matches) {
            filters.forEach(function (el) {
                el.classList.remove('is-stuck');
            });
            return;
        }

        filters.forEach(function (el) {
            const rect = el.getBoundingClientRect();
            el.classList.toggle('is-stuck', rect.top <= 12);
        });
    };

    window.addEventListener('scroll', syncState, { passive: true });
    window.addEventListener('resize', syncState);
    syncState();
}

function wireClientLocalDateTimeForMovementForms() {
    const forms = document.querySelectorAll('form.js-movement-form');
    if (!forms.length) {
        return;
    }

    const pad = function (value) {
        return String(value).padStart(2, '0');
    };

    const buildLocalDateTime = function (baseDate) {
        const now = new Date();
        const hours = pad(now.getHours());
        const minutes = pad(now.getMinutes());
        const seconds = pad(now.getSeconds());
        return baseDate + ' ' + hours + ':' + minutes + ':' + seconds;
    };

    const getTodayLocalDate = function () {
        const now = new Date();
        const year = now.getFullYear();
        const month = pad(now.getMonth() + 1);
        const day = pad(now.getDate());
        return year + '-' + month + '-' + day;
    };

    forms.forEach(function (form) {
        let hiddenInput = form.querySelector('input[name="data_movimentacao_local"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'data_movimentacao_local';
            form.appendChild(hiddenInput);
        }

        form.addEventListener('submit', function () {
            const dateInput = form.querySelector('input[name="data_movimentacao"]');
            const selectedDate = dateInput && dateInput.value ? dateInput.value : getTodayLocalDate();
            hiddenInput.value = buildLocalDateTime(selectedDate);
        });
    });
}

function wirePurchaseSupportFilters() {
    const searchInput = document.querySelector('.js-purchase-item-search');
    const onlyAlertInput = document.querySelector('.js-purchase-only-alert');
    const clearBtn = document.querySelector('.js-purchase-clear-filters');
    const visibleCount = document.querySelector('.js-purchase-visible-count');
    const emptyState = document.querySelector('.js-purchase-empty');
    const rows = Array.from(document.querySelectorAll('.js-purchase-row'));

    if (!rows.length) {
        return;
    }

    const applyFilters = function () {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const onlyAlert = onlyAlertInput ? onlyAlertInput.checked : false;
        let visible = 0;

        rows.forEach(function (row) {
            const itemName = row.getAttribute('data-item-name') || '';
            const itemType = row.getAttribute('data-item-type') || '';
            const isAlert = (row.getAttribute('data-alert') || '0') === '1';

            const searchMatch = !query || itemName.indexOf(query) !== -1 || itemType.indexOf(query) !== -1;
            const alertMatch = !onlyAlert || isAlert;
            const isVisible = searchMatch && alertMatch;

            row.style.display = isVisible ? '' : 'none';
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

    if (onlyAlertInput) {
        onlyAlertInput.addEventListener('change', applyFilters);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }

            if (onlyAlertInput) {
                onlyAlertInput.checked = false;
            }

            applyFilters();
        });
    }

    applyFilters();
}

function wirePurchaseWhatsAppMessage() {
    const sendBtn = document.querySelector('.js-purchase-send-whatsapp');
    if (!sendBtn) {
        return;
    }

    sendBtn.addEventListener('click', function () {
        const whatsappNumber = (sendBtn.getAttribute('data-whatsapp-number') || '').replace(/\D/g, '');
        if (!whatsappNumber) {
            window.alert('Numero de WhatsApp nao configurado.');
            return;
        }

        const selectedRows = Array.from(document.querySelectorAll('.js-purchase-row')).filter(function (row) {
            const checkbox = row.querySelector('.js-purchase-whatsapp-item');
            return checkbox && checkbox.checked;
        });

        if (!selectedRows.length) {
            window.alert('Selecione pelo menos um equipamento para enviar na mensagem.');
            return;
        }

        const prazoInput = document.querySelector('[name="prazo_reposicao_dias"]');
        const prazoReposicao = prazoInput ? (parseInt(prazoInput.value || '0', 10) || 0) : 0;
        const coberturaCompraDias = 30;

        const lines = [];
        lines.push('Solicitacao de compra - Estoque Fibra');
        lines.push('Data: ' + new Date().toLocaleString('pt-BR'));
        if (prazoReposicao > 0) {
            lines.push('Prazo de reposicao: ' + prazoReposicao + ' dias');
        }
        lines.push('Cobertura da compra sugerida: ' + coberturaCompraDias + ' dias');
        lines.push('');
        lines.push('Itens selecionados para compra:');

        selectedRows.forEach(function (row) {
            const nome = row.getAttribute('data-item-label') || 'Item';
            const tipo = row.getAttribute('data-item-type-label') || '-';
            const qtd = parseInt(row.getAttribute('data-buy-qty') || '0', 10) || 0;
            const atual = parseInt(row.getAttribute('data-stock-current') || '0', 10) || 0;
            const minimo = parseInt(row.getAttribute('data-stock-min') || '0', 10) || 0;

            lines.push('- ' + nome + ' (' + tipo + '): comprar ' + qtd + ' un | atual ' + atual + ' | minimo ' + minimo);
        });

        const message = lines.join('\n');
        const url = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(message);
        window.open(url, '_blank', 'noopener');
    });
}

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

    if (route !== 'movimentacoes' && route !== 'dashboard') {
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
        recolhimento_defeito: {
            modalId: document.getElementById('modal-recolhimento-defeito') ? 'modal-recolhimento-defeito' : 'modal-recolhimento',
            tecnicoSelector: document.getElementById('modal-recolhimento-defeito') ? '.js-recolhimento-defeito-tecnico' : '.js-recolhimento-tecnico',
        },
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

function wireDashboardMovementQuickActions() {
    const buttons = document.querySelectorAll('.js-dashboard-open-movement-modal');
    if (!buttons.length || !window.bootstrap || !window.bootstrap.Modal) {
        return;
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const modalId = button.getAttribute('data-modal-id') || '';
            const tecnicoSelector = button.getAttribute('data-tecnico-selector') || '';
            const tecnicoId = parseInt(button.getAttribute('data-tecnico-id') || '0', 10);

            if (!modalId || !tecnicoSelector || tecnicoId <= 0) {
                return;
            }

            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                return;
            }

            const tecnicoSelect = modalElement.querySelector(tecnicoSelector) || modalElement.querySelector('[name="tecnico_id"]');
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
            new window.bootstrap.Modal(modalElement).show();
        });
    });
}

function wireDashboardMovementModalPrefill() {
    const modalConfigs = [
        { modalId: 'modal-entrega', tecnicoSelector: '.js-entrega-tecnico' },
        { modalId: 'modal-uso', tecnicoSelector: '.js-uso-tecnico' },
        { modalId: 'modal-uso-teste', tecnicoSelector: '.js-uso-teste-tecnico' },
        { modalId: 'modal-recolhimento', tecnicoSelector: '.js-recolhimento-tecnico' },
        { modalId: 'modal-recolhimento-defeito', tecnicoSelector: '.js-recolhimento-defeito-tecnico' },
        { modalId: 'modal-devolucao', tecnicoSelector: '.js-devolucao-tecnico' },
    ];

    modalConfigs.forEach(function (config) {
        const modalElement = document.getElementById(config.modalId);
        if (!modalElement) {
            return;
        }

        modalElement.addEventListener('show.bs.modal', function (event) {
            const relatedTarget = event.relatedTarget;
            if (!relatedTarget) {
                return;
            }

            const tecnicoId = parseInt(relatedTarget.getAttribute('data-tecnico-id') || '0', 10);
            if (tecnicoId <= 0) {
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
        });
    });
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

function wireEditTecnicoModal() {
    const buttons = document.querySelectorAll('.btn-edit-tecnico');

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const idInput = document.getElementById('edit-tecnico-id');
            const nomeInput = document.getElementById('edit-tecnico-nome');

            if (idInput) {
                idInput.value = button.getAttribute('data-id') || '';
            }

            if (nomeInput) {
                nomeInput.value = button.getAttribute('data-nome') || '';
            }
        });
    });
}

function wireEditHandBalanceModal() {
    const buttons = document.querySelectorAll('.js-ajuste-mao-btn');

    if (!buttons.length) {
        return;
    }

    const tecnicoIdInput = document.getElementById('ajuste-mao-tecnico-id');
    const equipamentoIdInput = document.getElementById('ajuste-mao-equipamento-id');
    const tecnicoNomeInput = document.getElementById('ajuste-mao-tecnico-nome');
    const equipamentoNomeInput = document.getElementById('ajuste-mao-equipamento-nome');
    const saldoAtualInput = document.getElementById('ajuste-mao-saldo-atual');
    const saldoNovoInput = document.getElementById('ajuste-mao-saldo-novo');

    if (!tecnicoIdInput || !equipamentoIdInput || !tecnicoNomeInput || !equipamentoNomeInput || !saldoAtualInput || !saldoNovoInput) {
        return;
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            const tecnicoId = button.getAttribute('data-tecnico-id') || '';
            const equipamentoId = button.getAttribute('data-equipamento-id') || '';
            const tecnicoNome = button.getAttribute('data-tecnico-nome') || '';
            const equipamentoNome = button.getAttribute('data-equipamento-nome') || '';
            const saldoAtual = button.getAttribute('data-saldo-atual') || '0';

            tecnicoIdInput.value = tecnicoId;
            equipamentoIdInput.value = equipamentoId;
            tecnicoNomeInput.value = tecnicoNome;
            equipamentoNomeInput.value = equipamentoNome;
            saldoAtualInput.value = saldoAtual;
            saldoNovoInput.value = saldoAtual;
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

function wireRecolhimentoModal() {
    wireSystemEquipmentModal('#modal-recolhimento', '.js-recolhimento-equipamento');
}

function wireRecolhimentoDefeitoModal() {
    wireSystemEquipmentModal('#modal-recolhimento-defeito', '.js-recolhimento-defeito-equipamento');
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

function renderSystemEquipmentOptions(selectElement, items) {
    selectElement.innerHTML = '';
    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = items.length ? 'Selecione um equipamento' : 'Nenhum equipamento disponivel';
    selectElement.appendChild(emptyOption);

    items.forEach(function (item) {
        const option = document.createElement('option');
        option.value = item.id || item.equipamento_id || '';
        const codigoBarras = (item.codigo_barras || '').toString().trim();
        const tipo = (item.tipo || '').toString().trim();
        const saldoCampo = parseInt(item.saldo_campo || '0', 10) || 0;
        option.textContent = item.nome
            + (tipo ? ' (' + tipo + ')' : '')
            + (saldoCampo > 0 ? ' | em campo: ' + saldoCampo : '')
            + (codigoBarras ? ' | cod: ' + codigoBarras : '');
        if (codigoBarras) {
            option.setAttribute('data-codigo-barras', codigoBarras);
        }
        selectElement.appendChild(option);
    });

    selectElement.disabled = items.length === 0;
}

function wireSystemEquipmentModal(modalSelector, equipmentSelector, techSelector, mapKey) {
    const movementMap = readMovementMap();
    if (!movementMap) {
        return;
    }

    const form = document.querySelector(modalSelector + ' form');
    if (!form) {
        return;
    }

    const equipamentoSelect = form.querySelector(equipmentSelector);
    if (!equipamentoSelect) {
        return;
    }

    const tecnicoSelect = techSelector ? form.querySelector(techSelector) : null;
    const allItems = Array.isArray(movementMap.all) ? movementMap.all : [];
    const scopedItemsMap = mapKey && movementMap[mapKey] ? movementMap[mapKey] : null;

    const syncOptions = function () {
        if (tecnicoSelect && scopedItemsMap) {
            const tecnicoId = tecnicoSelect.value;
            if (!tecnicoId) {
                renderSystemEquipmentOptions(equipamentoSelect, []);
                return;
            }

            const scopedItems = Array.isArray(scopedItemsMap[tecnicoId]) ? scopedItemsMap[tecnicoId] : [];
            renderSystemEquipmentOptions(equipamentoSelect, scopedItems);
            return;
        }

        renderSystemEquipmentOptions(equipamentoSelect, allItems);
    };

    const modalElement = document.querySelector(modalSelector);
    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', syncOptions);
    }

    if (tecnicoSelect) {
        tecnicoSelect.addEventListener('change', syncOptions);
    }

    syncOptions();
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
    const modalSelectors = ['#modal-entrega', '#modal-uso', '#modal-uso-teste', '#modal-recolhimento', '#modal-recolhimento-defeito', '#modal-devolucao'];

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

        const tipoExigeTecnico = function () {
            const tipo = getTipo();
            return tipo !== 'recolhimento' && tipo !== 'recolhimento_defeito';
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

            if (tipoExigeTecnico() && tecnicoId <= 0) {
                window.alert('Selecione o técnico antes de adicionar itens.');
                return;
            }

            if (tipoExigeTecnico() && batchTecnicoId > 0 && tecnicoId !== batchTecnicoId) {
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

            if (tipoExigeTecnico() && batchTecnicoId === 0) {
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

            if (tipoExigeTecnico() && tecnicoId <= 0) {
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

            if (tipoExigeTecnico() && batchTecnicoId > 0 && tecnicoId !== batchTecnicoId) {
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

            if (tipoExigeTecnico() && batchTecnicoId === 0) {
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
                if (tecnicoSelect && tipoExigeTecnico() && batchTecnicoId > 0) {
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

function wirePurchasePersonalList() {
    const STORAGE_KEY = 'purchase_personal_list';
    
    // Aguardar um pouco para garantir que o DOM está pronto
    setTimeout(function() {
        const addBtn = document.querySelector('.js-purchase-add-item');
        const itemSelectInput = document.querySelector('.js-purchase-new-item-select');
        const itemCustomInput = document.querySelector('.js-purchase-new-item-custom');
        const itemQtyInput = document.querySelector('.js-purchase-new-item-qty');
        
        if (!addBtn || !itemSelectInput || !itemQtyInput) {
            console.warn('wirePurchasePersonalList - Elementos necessários não encontrados');
            return;
        }

        const listContainer = document.getElementById('purchase-list-container');
        const listItems = document.getElementById('purchase-list-items');
        const listItemsContainer = document.getElementById('purchase-list-items-container');
        const emptyMessage = document.getElementById('purchase-empty-message');
        const countBadge = document.querySelector('.js-purchase-list-count');
        const whatsappBtn = document.querySelector('.js-purchase-list-whatsapp');
        const printBtn = document.querySelector('.js-purchase-list-print');
        const clearBtn = document.querySelector('.js-purchase-list-clear');

        // Handle select change to show/hide custom input
        if (itemSelectInput) {
            itemSelectInput.addEventListener('change', function () {
                const selectedValue = this.value;
                if (selectedValue === 'custom') {
                    if (itemCustomInput) {
                        itemCustomInput.classList.remove('d-none');
                        itemCustomInput.focus();
                    }
                } else {
                    if (itemCustomInput) {
                        itemCustomInput.classList.add('d-none');
                        itemCustomInput.value = '';
                    }
                }
            });
        }

        // Load items from localStorage
        const loadItems = function () {
            try {
                const saved = localStorage.getItem(STORAGE_KEY);
                return saved ? JSON.parse(saved) : [];
            } catch (e) {
                console.error('Erro ao carregar lista', e);
                return [];
            }
        };

        // Save items to localStorage
        const saveItems = function (items) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
            } catch (e) {
                console.error('Erro ao salvar lista', e);
            }
        };

        // Escape HTML entities
        const escapeHtml = function (text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        };

        // Render the list
        const renderList = function (items) {
            if (!listItemsContainer) {
                console.warn('listItemsContainer não encontrado');
                return;
            }
            
            listItemsContainer.innerHTML = '';

            items.forEach(function (item, index) {
                const div = document.createElement('div');
                div.className = 'dark-panel-subtle p-3 rounded d-flex justify-content-between align-items-center gap-2';
                
                const infoDiv = document.createElement('div');
                infoDiv.className = 'flex-grow-1';
                let typeTag = '';
                if (item.type) {
                    typeTag = ' <span class="badge text-bg-secondary">' + escapeHtml(item.type) + '</span>';
                }
                infoDiv.innerHTML = '<strong>' + escapeHtml(item.name) + '</strong>' + typeTag + ' <span class="badge text-bg-info">Qtd: ' + (parseInt(item.qty || '1', 10) || 1) + '</span>';
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-outline-danger';
                removeBtn.textContent = 'Remover';
                removeBtn.addEventListener('click', function () {
                    const updatedItems = items.filter(function (_, i) { return i !== index; });
                    saveItems(updatedItems);
                    renderList(updatedItems);
                    updateUI(updatedItems);
                });

                div.appendChild(infoDiv);
                div.appendChild(removeBtn);
                listItemsContainer.appendChild(div);
            });

            updateUI(items);
        };

        // Update UI visibility
        const updateUI = function (items) {
            const isEmpty = items.length === 0;
            
            if (isEmpty) {
                if (listItems) listItems.classList.add('d-none');
                if (emptyMessage) emptyMessage.classList.remove('d-none');
            } else {
                if (listItems) listItems.classList.remove('d-none');
                if (emptyMessage) emptyMessage.classList.add('d-none');
            }

            if (countBadge) {
                countBadge.textContent = String(items.length);
            }

            const totalQty = items.reduce(function (sum, item) {
                return sum + (parseInt(item.qty || '1', 10) || 1);
            }, 0);

            const totalBadge = document.querySelector('.js-purchase-list-total-items');
            if (totalBadge) {
                totalBadge.textContent = String(totalQty);
            }
        };

        // Add item to list
        const addItem = function () {
            const selectedValue = (itemSelectInput.value || '').trim();
            const customValue = (itemCustomInput.value || '').trim();
            const qty = Math.max(1, parseInt(itemQtyInput.value || '1', 10) || 1);

            let itemName = '';
            let itemType = '';

            if (selectedValue === 'custom') {
                if (!customValue) {
                    window.alert('Informe a descrição do item customizado.');
                    itemCustomInput.focus();
                    return;
                }
                itemName = customValue;
                itemType = '';
            } else if (selectedValue && selectedValue !== '') {
                const selectedOption = itemSelectInput.options[itemSelectInput.selectedIndex];
                itemName = selectedOption.getAttribute('data-equip-name') || selectedOption.textContent.trim();
                itemType = selectedOption.getAttribute('data-equip-type') || '';
            } else {
                window.alert('Selecione um equipamento ou escolha digitar customizado.');
                itemSelectInput.focus();
                return;
            }

            const items = loadItems();
            items.push({
                name: itemName,
                type: itemType,
                qty: qty,
                addedAt: new Date().toISOString()
            });

            saveItems(items);
            renderList(items);

            itemSelectInput.value = '';
            if (itemCustomInput) {
                itemCustomInput.value = '';
                itemCustomInput.classList.add('d-none');
            }
            itemQtyInput.value = '1';
            itemSelectInput.focus();
        };

        // Format list for WhatsApp
        const formatForWhatsApp = function (items) {
            const lines = [];
            lines.push('Lista de Compras - Estoque Fibra');
            lines.push('Data: ' + new Date().toLocaleString('pt-BR'));
            lines.push('');
            lines.push('Itens para comprar:');

            items.forEach(function (item, index) {
                let line = (index + 1) + '. ' + item.name;
                if (item.type) {
                    line += ' (' + item.type + ')';
                }
                line += ' - Qtd: ' + (parseInt(item.qty || '1', 10) || 1);
                lines.push(line);
            });

            lines.push('');
            const totalQty = items.reduce(function (sum, item) {
                return sum + (parseInt(item.qty || '1', 10) || 1);
            }, 0);
            lines.push('Total de itens: ' + totalQty);

            return lines.join('\n');
        };

        // Format list for PDF
        const formatForPDF = function (items) {
            const totalQty = items.reduce(function (sum, item) {
                return sum + (parseInt(item.qty || '1', 10) || 1);
            }, 0);

            let html = '<h2>Lista de Compras - Estoque Fibra</h2>';
            html += '<p><strong>Data:</strong> ' + new Date().toLocaleString('pt-BR') + '</p>';
            html += '<table class="table table-bordered" style="width: 100%; border-collapse: collapse;">';
            html += '<thead style="background-color: #f8f9fa;"><tr><th style="padding: 10px; text-align: left; border: 1px solid #dee2e6;">Item</th><th style="padding: 10px; text-align: center; border: 1px solid #dee2e6;">Quantidade</th></tr></thead>';
            html += '<tbody>';

            items.forEach(function (item, index) {
                html += '<tr>';
                let itemDisplay = (index + 1) + '. ' + escapeHtml(item.name);
                if (item.type) {
                    itemDisplay += ' (' + escapeHtml(item.type) + ')';
                }
                html += '<td style="padding: 10px; border: 1px solid #dee2e6;">' + itemDisplay + '</td>';
                html += '<td style="padding: 10px; text-align: center; border: 1px solid #dee2e6;">' + (parseInt(item.qty || '1', 10) || 1) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            html += '<p style="margin-top: 20px;"><strong>Total de itens:</strong> ' + totalQty + '</p>';

            return html;
        };

        // Event listeners
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            addItem();
        });

        itemSelectInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addItem();
            }
        });

        if (itemCustomInput) {
            itemCustomInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addItem();
                }
            });
        }

        itemQtyInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                addItem();
            }
        });

        if (whatsappBtn) {
            whatsappBtn.addEventListener('click', function () {
                const items = loadItems();
                if (!items.length) {
                    window.alert('Adicione itens à lista antes de enviar.');
                    return;
                }

                const whatsappNumber = (whatsappBtn.getAttribute('data-whatsapp-number') || '').replace(/\D/g, '');
                if (!whatsappNumber) {
                    window.alert('Número de WhatsApp não configurado.');
                    return;
                }

                const message = formatForWhatsApp(items);
                const url = 'https://wa.me/' + whatsappNumber + '?text=' + encodeURIComponent(message);
                window.open(url, '_blank', 'noopener');
            });
        }

        if (printBtn) {
            printBtn.addEventListener('click', function () {
                const items = loadItems();
                if (!items.length) {
                    window.alert('Adicione itens à lista antes de gerar PDF.');
                    return;
                }

                const modal = new window.bootstrap.Modal(document.getElementById('purchase-pdf-preview-modal'));
                const previewContent = document.getElementById('purchase-pdf-preview-content');
                previewContent.innerHTML = formatForPDF(items);
                modal.show();
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                if (!window.confirm('Tem certeza que deseja limpar toda a lista?')) {
                    return;
                }
                saveItems([]);
                renderList([]);
            });
        }

        // Print modal button
        const printNowBtn = document.querySelector('.js-purchase-pdf-print-now');
        if (printNowBtn) {
            printNowBtn.addEventListener('click', function () {
                window.print();
            });
        }

        // Initial render
        renderList(loadItems());
    }, 100); // Esperar 100ms para garantir que o DOM está pronto
}

function staggerRevealAnimation() {
    const items = document.querySelectorAll('.reveal');

    items.forEach(function (item, index) {
        item.style.animationDelay = (index * 80) + 'ms';
    });
}

function wireInadimplenciaEditModal() {
    const buttons = document.querySelectorAll('.js-open-inadimplencia-edit');

    if (!buttons.length) {
        return;
    }

    const idInput = document.getElementById('inad-edit-id');
    const titularInput = document.getElementById('inad-edit-titular');
    const equipamentoInput = document.getElementById('inad-edit-equipamento');
    const contatoInput = document.getElementById('inad-edit-contato');
    const enderecoInput = document.getElementById('inad-edit-endereco');
    const prazoInput = document.getElementById('inad-edit-prazo');
    const statusInput = document.getElementById('inad-edit-status');
    const tentativaInput = document.getElementById('inad-edit-tentativa');
    const novaTentativaInput = document.getElementById('inad-edit-nova-tentativa');
    const addTentativaButton = document.querySelector('.js-add-inadimplencia-attempt');
    const observacoesInput = document.getElementById('inad-edit-observacoes');

    if (!idInput || !titularInput || !equipamentoInput || !contatoInput || !enderecoInput || !prazoInput || !statusInput || !tentativaInput || !novaTentativaInput || !addTentativaButton || !observacoesInput) {
        return;
    }

    const appendAttempt = function () {
        const attemptText = (novaTentativaInput.value || '').trim();
        if (!attemptText) {
            return;
        }

        const now = new Date();
        const stamp = now.toLocaleString('pt-BR');
        const entry = '[' + stamp + '] ' + attemptText;
        const currentHistory = (tentativaInput.value || '').trim();

        tentativaInput.value = currentHistory ? (currentHistory + '\n\n' + entry) : entry;
        novaTentativaInput.value = '';
        novaTentativaInput.focus();
    };

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            idInput.value = button.getAttribute('data-id') || '';
            titularInput.value = button.getAttribute('data-titular') || '';
            equipamentoInput.value = button.getAttribute('data-equipamento') || '';
            contatoInput.value = button.getAttribute('data-contato') || '';
            enderecoInput.value = button.getAttribute('data-endereco') || '';
            prazoInput.value = button.getAttribute('data-prazo') || '';
            statusInput.value = button.getAttribute('data-status') || 'AGUARDANDO';
            tentativaInput.value = button.getAttribute('data-tentativa') || '';
            novaTentativaInput.value = '';
            observacoesInput.value = button.getAttribute('data-observacoes') || '';
        });
    });

    addTentativaButton.addEventListener('click', function () {
        appendAttempt();
    });
}

function wireUsoTesteImport() {
    const form = document.querySelector('.js-uso-teste-import-form');
    if (!form) {
        return;
    }

    const fileInput = form.querySelector('.js-uso-teste-file');
    const parseBtn = form.querySelector('.js-uso-teste-parse');
    const jsonInput = form.querySelector('.js-uso-teste-import-json');
    const previewBox = form.querySelector('.js-uso-teste-preview');
    const previewCount = form.querySelector('.js-uso-teste-preview-count');
    const previewColumns = form.querySelector('.js-uso-teste-preview-columns');
    const previewBody = form.querySelector('.js-uso-teste-preview-body');

    if (!fileInput || !parseBtn || !jsonInput || !previewBox || !previewCount || !previewColumns || !previewBody) {
        return;
    }

    const normalizeHeader = function (value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();
    };

    const normalizeCell = function (value) {
        if (value === null || value === undefined) {
            return '';
        }
        return value.toString().trim();
    };

    const getColumnIndexMap = function (headerRow) {
        const map = {
            tecnico_id: -1,
            codigo_barras: -1,
            quantidade: -1,
            local: -1,
            observacoes: -1,
        };

        headerRow.forEach(function (cell, index) {
            const header = normalizeHeader(cell);

            if (header.indexOf('TECNICO') !== -1 || header.indexOf('ID_TECNICO') !== -1 || header.indexOf('IDTECNICO') !== -1) {
                map.tecnico_id = index;
            } else if (header.indexOf('CODIGO') !== -1 || header.indexOf('BARRAS') !== -1 || header.indexOf('CODIGOBARRAS') !== -1) {
                map.codigo_barras = index;
            } else if (header.indexOf('QUANTIDADE') !== -1 || header.indexOf('QTD') !== -1) {
                map.quantidade = index;
            } else if (header.indexOf('LOCAL') !== -1 || header.indexOf('LOCAL_TESTE') !== -1 || header.indexOf('LOCALIZACAO') !== -1) {
                map.local = index;
            } else if (header.indexOf('OBSERVACAO') !== -1 || header.indexOf('OBS') !== -1 || header.indexOf('COMENTARIO') !== -1) {
                map.observacoes = index;
            }
        });

        return map;
    };

    const findHeaderRowIndex = function (rows) {
        for (let i = 0; i < Math.min(rows.length, 10); i++) {
            const row = rows[i] || [];
            const joined = row.map(normalizeHeader).join(' | ');
            if ((joined.indexOf('TECNICO') !== -1 || joined.indexOf('IDTECNICO') !== -1) && (joined.indexOf('CODIGO') !== -1 || joined.indexOf('BARRAS') !== -1)) {
                return i;
            }
        }
        return -1;
    };

    const renderPreview = function (rows) {
        previewBody.innerHTML = '';

        rows.slice(0, 8).forEach(function (row) {
            const tr = document.createElement('tr');
            ['tecnico_id', 'codigo_barras', 'quantidade', 'local', 'observacoes'].forEach(function (key) {
                const td = document.createElement('td');
                td.textContent = row[key] || '-';
                tr.appendChild(td);
            });
            previewBody.appendChild(tr);
        });

        previewCount.textContent = rows.length + ' linha(s)';
        previewColumns.textContent = 'Colunas lidas: TECNICOID, CODIGOBARRAS, QUANTIDADE, LOCAL, OBSERVACOES';
        previewBox.classList.remove('d-none');
    };

    const parseWorkbookFile = function (file) {
        return file.arrayBuffer().then(function (arrayBuffer) {
            const workbook = window.XLSX.read(arrayBuffer, { type: 'array', cellDates: false });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const rows = window.XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

            if (!rows.length) {
                return [];
            }

            const headerIndex = findHeaderRowIndex(rows);
            if (headerIndex < 0) {
                throw new Error('Não foi possível identificar os cabeçalhos da planilha. Certifique-se de que a primeira linha contém "TECNICOID" e "CODIGOBARRAS".');
            }

            const headerMap = getColumnIndexMap(rows[headerIndex] || []);
            if (headerMap.tecnico_id < 0 || headerMap.codigo_barras < 0) {
                throw new Error('A planilha precisa conter pelo menos as colunas TECNICOID e CODIGOBARRAS.');
            }

            const mappedRows = [];

            for (let i = headerIndex + 1; i < rows.length; i++) {
                const row = rows[i] || [];

                const item = {
                    tecnico_id: headerMap.tecnico_id >= 0 ? normalizeCell(row[headerMap.tecnico_id]) : '',
                    codigo_barras: headerMap.codigo_barras >= 0 ? normalizeCell(row[headerMap.codigo_barras]) : '',
                    quantidade: headerMap.quantidade >= 0 ? String(parseInt(row[headerMap.quantidade] || '1', 10) || 1) : '1',
                    local: headerMap.local >= 0 ? normalizeCell(row[headerMap.local]) : '',
                    observacoes: headerMap.observacoes >= 0 ? normalizeCell(row[headerMap.observacoes]) : '',
                };

                if (!item.tecnico_id || !item.codigo_barras) {
                    continue;
                }

                if (!item.local) {
                    item.local = 'Importado';
                }

                mappedRows.push(item);
            }

            return mappedRows;
        });
    };

    parseBtn.addEventListener('click', function () {
        if (!window.XLSX) {
            window.alert('Biblioteca XLSX indisponível no navegador.');
            return;
        }

        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) {
            window.alert('Selecione um arquivo para ler a planilha.');
            return;
        }

        parseBtn.disabled = true;
        parseBtn.textContent = 'Lendo...';

        parseWorkbookFile(file)
            .then(function (rows) {
                if (!rows.length) {
                    throw new Error('Nenhuma linha válida encontrada na planilha.');
                }

                jsonInput.value = JSON.stringify(rows);
                renderPreview(rows);
            })
            .catch(function (error) {
                previewBox.classList.add('d-none');
                jsonInput.value = '';
                window.alert(error && error.message ? error.message : 'Falha ao ler a planilha.');
            })
            .finally(function () {
                parseBtn.disabled = false;
                parseBtn.textContent = 'Ler planilha';
            });
    });

    form.addEventListener('submit', function (event) {
        if (!jsonInput.value) {
            event.preventDefault();
            window.alert('Leia a planilha antes de confirmar a importação.');
        }
    });
}

function wireInadimplenciaImport() {
    const form = document.querySelector('.js-inadimplencia-import-form');
    if (!form) {
        return;
    }

    const fileInput = form.querySelector('.js-inadimplencia-file');
    const parseBtn = form.querySelector('.js-inadimplencia-parse');
    const jsonInput = form.querySelector('.js-inadimplencia-import-json');
    const previewBox = form.querySelector('.js-inadimplencia-preview');
    const previewCount = form.querySelector('.js-inadimplencia-preview-count');
    const previewColumns = form.querySelector('.js-inadimplencia-preview-columns');
    const previewBody = form.querySelector('.js-inadimplencia-preview-body');

    if (!fileInput || !parseBtn || !jsonInput || !previewBox || !previewCount || !previewColumns || !previewBody) {
        return;
    }

    const normalizeHeader = function (value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toUpperCase();
    };

    const normalizeCell = function (value) {
        if (value === null || value === undefined) {
            return '';
        }
        return value.toString().trim();
    };

    const toIsoDate = function (value) {
        const raw = normalizeCell(value);
        if (!raw) {
            return '';
        }

        if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
            return raw;
        }

        const monthMap = {
            jan: '01',
            fev: '02',
            mar: '03',
            abr: '04',
            mai: '05',
            jun: '06',
            jul: '07',
            ago: '08',
            set: '09',
            out: '10',
            nov: '11',
            dez: '12',
        };

        const low = raw.toLowerCase();
        const withNumericMonth = low.replace(/jan|fev|mar|abr|mai|jun|jul|ago|set|out|nov|dez/g, function (m) {
            return monthMap[m] || m;
        });

        const fullMatch = withNumericMonth.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
        if (fullMatch) {
            const day = String(parseInt(fullMatch[1], 10)).padStart(2, '0');
            const month = String(parseInt(fullMatch[2], 10)).padStart(2, '0');
            let year = parseInt(fullMatch[3], 10);
            if (year < 100) {
                year += 2000;
            }
            return String(year).padStart(4, '0') + '-' + month + '-' + day;
        }

        const shortMatch = withNumericMonth.match(/^(\d{1,2})\/(\d{1,2})$/);
        if (shortMatch) {
            const day = String(parseInt(shortMatch[1], 10)).padStart(2, '0');
            const month = String(parseInt(shortMatch[2], 10)).padStart(2, '0');
            const year = String(new Date().getFullYear());
            return year + '-' + month + '-' + day;
        }

        return '';
    };

    const getColumnIndexMap = function (headerRow) {
        const map = {
            titular: -1,
            equipamento: -1,
            contato: -1,
            endereco: -1,
            prazo: -1,
            status: -1,
            tentativa_1: -1,
        };

        headerRow.forEach(function (cell, index) {
            const header = normalizeHeader(cell);

            if (header.indexOf('TITULAR') !== -1) {
                map.titular = index;
            } else if (header.indexOf('EQUIPAMENTO') !== -1) {
                map.equipamento = index;
            } else if (header.indexOf('CONTATO') !== -1 || header.indexOf('TELEFONE') !== -1) {
                map.contato = index;
            } else if (header.indexOf('ENDERECO') !== -1) {
                map.endereco = index;
            } else if (header.indexOf('PRAZO') !== -1 || header.indexOf('VENCIMENTO') !== -1) {
                map.prazo = index;
            } else if (header.indexOf('STATUS') !== -1) {
                map.status = index;
            } else if (header.indexOf('TENTATIVA') !== -1) {
                map.tentativa_1 = index;
            }
        });

        return map;
    };

    const findHeaderRowIndex = function (rows) {
        for (let i = 0; i < Math.min(rows.length, 10); i++) {
            const row = rows[i] || [];
            const joined = row.map(normalizeHeader).join(' | ');
            if (joined.indexOf('TITULAR') !== -1 && joined.indexOf('EQUIPAMENTO') !== -1) {
                return i;
            }
        }
        return -1;
    };

    const renderPreview = function (rows) {
        previewBody.innerHTML = '';

        rows.slice(0, 8).forEach(function (row) {
            const tr = document.createElement('tr');
            ['titular', 'equipamento', 'contato', 'endereco', 'prazo', 'status', 'tentativa_1'].forEach(function (key) {
                const td = document.createElement('td');
                td.textContent = row[key] || '-';
                tr.appendChild(td);
            });
            previewBody.appendChild(tr);
        });

        previewCount.textContent = rows.length + ' linha(s)';
        previewColumns.textContent = 'Colunas lidas: TITULAR, EQUIPAMENTO, CONTATO, ENDERECO, PRAZO, STATUS, TENTATIVA 1';
        previewBox.classList.remove('d-none');
    };

    const parseWorkbookFile = function (file) {
        return file.arrayBuffer().then(function (arrayBuffer) {
            const workbook = window.XLSX.read(arrayBuffer, { type: 'array', cellDates: false });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            const rows = window.XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });

            if (!rows.length) {
                return [];
            }

            const headerIndex = findHeaderRowIndex(rows);
            if (headerIndex < 0) {
                throw new Error('Nao foi possivel identificar os cabecalhos da planilha.');
            }

            const headerMap = getColumnIndexMap(rows[headerIndex] || []);
            if (headerMap.titular < 0 || headerMap.equipamento < 0) {
                throw new Error('A planilha precisa conter pelo menos as colunas TITULAR e EQUIPAMENTO.');
            }

            const mappedRows = [];

            for (let i = headerIndex + 1; i < rows.length; i++) {
                const row = rows[i] || [];

                const item = {
                    titular: headerMap.titular >= 0 ? normalizeCell(row[headerMap.titular]) : '',
                    equipamento: headerMap.equipamento >= 0 ? normalizeCell(row[headerMap.equipamento]) : '',
                    contato: headerMap.contato >= 0 ? normalizeCell(row[headerMap.contato]) : '',
                    endereco: headerMap.endereco >= 0 ? normalizeCell(row[headerMap.endereco]) : '',
                    prazo: headerMap.prazo >= 0 ? toIsoDate(row[headerMap.prazo]) : '',
                    status: headerMap.status >= 0 ? normalizeCell(row[headerMap.status]).toUpperCase() : 'AGUARDANDO',
                    tentativa_1: headerMap.tentativa_1 >= 0 ? normalizeCell(row[headerMap.tentativa_1]) : '',
                };

                if (!item.titular && !item.equipamento) {
                    continue;
                }

                if (!item.status) {
                    item.status = 'AGUARDANDO';
                }

                mappedRows.push(item);
            }

            return mappedRows;
        });
    };

    parseBtn.addEventListener('click', function () {
        if (!window.XLSX) {
            window.alert('Biblioteca XLSX indisponivel no navegador.');
            return;
        }

        const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
        if (!file) {
            window.alert('Selecione um arquivo para ler a planilha.');
            return;
        }

        parseBtn.disabled = true;
        parseBtn.textContent = 'Lendo...';

        parseWorkbookFile(file)
            .then(function (rows) {
                if (!rows.length) {
                    throw new Error('Nenhuma linha valida encontrada na planilha.');
                }

                jsonInput.value = JSON.stringify(rows);
                renderPreview(rows);
            })
            .catch(function (error) {
                previewBox.classList.add('d-none');
                jsonInput.value = '';
                window.alert(error && error.message ? error.message : 'Falha ao ler a planilha.');
            })
            .finally(function () {
                parseBtn.disabled = false;
                parseBtn.textContent = 'Ler planilha';
            });
    });

    form.addEventListener('submit', function (event) {
        if (!jsonInput.value) {
            event.preventDefault();
            window.alert('Leia a planilha antes de confirmar a importacao.');
        }
    });
}
