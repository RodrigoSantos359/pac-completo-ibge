/**
 * PAC Completo IBGE - Gerador Web
 * Interatividade e validação no lado do cliente
 */

(function () {
  'use strict';

  // DOM Elements
  const cnpjTextarea = document.getElementById('cnpjs');
  const searchBtn = document.getElementById('searchBtn');
  const loadingOverlay = document.getElementById('loadingOverlay');
  const errorSection = document.getElementById('errorSection');
  const errorMessage = document.getElementById('errorMessage');
  const loadingText = document.getElementById('loadingText');

  // Auto-format CNPJs in textarea
  if (cnpjTextarea) {
    cnpjTextarea.addEventListener('input', function (e) {
      let value = e.target.value;
      let lines = value.split('\n');
      let cleaned = lines.map(line => line.replace(/\D/g, '')).join('\n');
      e.target.value = cleaned;
    });
  }

  // Search button click
  if (searchBtn) {
    searchBtn.addEventListener('click', function () {
      const raw = cnpjTextarea ? cnpjTextarea.value : '';
      const cnpjs = raw.split('\n')
        .map(c => c.replace(/\D/g, '').trim())
        .filter(c => c.length > 0);

      if (cnpjs.length === 0) {
        showError('Digite pelo menos um CNPJ (14 dígitos cada).');
        if (cnpjTextarea) cnpjTextarea.focus();
        return;
      }

      const invalid = cnpjs.filter(c => c.length !== 14);
      if (invalid.length > 0) {
        showError('CNPJs inválidos (devem ter 14 dígitos): ' + invalid.join(', '));
        return;
      }

      buscarEmpresas(cnpjs);
    });
  }

  // Enter key on textarea (Ctrl+Enter to submit)
  if (cnpjTextarea) {
    cnpjTextarea.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        searchBtn.click();
      }
    });
  }

  /**
   * Busca dados de múltiplas empresas via AJAX
   */
  function buscarEmpresas(cnpjs) {
    showLoading(true, 'Consultando BrasilAPI (' + cnpjs.length + ' CNPJ' + (cnpjs.length > 1 ? 's' : '') + ')...');
    hideError();

    const wkToken = document.getElementById('wk_token')?.value || '';

    fetch('?action=consultar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: new URLSearchParams({
        cnpjs: JSON.stringify(cnpjs),
        wk_token: wkToken,
      })
    })
      .then(response => {
        if (!response.ok) {
          return response.json().then(err => { throw new Error(err.error || 'Erro na consulta'); });
        }
        return response.json();
      })
      .then(data => {
        showLoading(false);
        if (data.success) {
          window.location.href = '?action=editar';
        } else {
          showError(data.error || 'Erro ao consultar CNPJs');
        }
      })
      .catch(err => {
        showLoading(false);
        showError(err.message || 'Erro de conexão com o servidor');
      });
  }

  // =========================================================================
  // COMPANY TABS (editor page)
  // =========================================================================

  document.addEventListener('click', function (e) {
    const tab = e.target.closest('[data-company-tab]');
    if (tab) {
      const index = tab.dataset.companyTab;
      switchCompanyTab(index);
    }
  });

  function switchCompanyTab(index) {
    document.querySelectorAll('[data-company-tab]').forEach(t => {
      t.classList.toggle('active', t.dataset.companyTab === index);
    });

    document.querySelectorAll('[data-company-panel]').forEach(p => {
      p.classList.toggle('active', p.dataset.companyPanel === index);
    });

    const currentField = document.getElementById('current_empresa_index');
    if (currentField) currentField.value = index;
  }

  // =========================================================================
  // ADD MERCADORIA ROW
  // =========================================================================

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-add-mercadoria]');
    if (btn) {
      const panel = btn.closest('[data-company-panel]');
      if (!panel) return;

      const tbody = panel.querySelector('.mercadorias-table tbody');
      if (!tbody) return;

      const rowCount = tbody.querySelectorAll('tr').length;
      if (rowCount >= 10) {
        alert('Máximo de 10 mercadorias por empresa.');
        return;
      }

      const index = panel.dataset.companyPanel;
      const tr = document.createElement('tr');
      tr.style.animation = 'fadeIn 0.3s ease';
      tr.innerHTML = `
        <td><input type="text" name="empresa[${index}][mercadoria_codigo][]" class="form-input" placeholder="NCM" maxlength="20"></td>
        <td><input type="text" name="empresa[${index}][mercadoria_descricao][]" class="form-input" placeholder="Descrição" maxlength="200"></td>
        <td><input type="number" name="empresa[${index}][mercadoria_percentual][]" class="form-input" placeholder="%" min="0" max="100" style="width:80px"></td>
        <td><button type="button" class="btn-icon" onclick="this.closest('tr').remove()" title="Remover">
          <img src="https://img.icons8.com/nolan/64/waste.png" width="18" height="18" alt="Remover" style="vertical-align:middle;">
        </button></td>
      `;
      tbody.appendChild(tr);
    }
  });

  // =========================================================================
  // WK RADAR CONFIG
  // =========================================================================

  const toggleWkBtn = document.getElementById('toggleWkConfig');
  const wkConfig = document.getElementById('wkConfigFields');
  if (toggleWkBtn && wkConfig) {
    toggleWkBtn.addEventListener('click', function () {
      wkConfig.classList.toggle('hidden');
    });
  }

  const testWkBtn = document.getElementById('testWkToken');
  if (testWkBtn) {
    testWkBtn.addEventListener('click', function () {
      const token = document.getElementById('wk_token').value.trim();
      if (!token) {
        alert('Informe o token do WK Radar primeiro.');
        return;
      }

      testWkBtn.disabled = true;
      testWkBtn.innerHTML = '<img src="https://img.icons8.com/nolan/64/synchronize.png" width="18" height="18" alt="Testando" style="vertical-align:middle;margin-right:4px;"> Testando...';

      fetch('?action=testar_wk', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ wk_token: token })
      })
        .then(r => r.json())
        .then(data => {
          testWkBtn.disabled = false;
          testWkBtn.innerHTML = '<img src="https://img.icons8.com/nolan/64/link.png" width="18" height="18" alt="Link" style="vertical-align:middle;margin-right:4px;"> Testar Conexão';
          if (data.success) {
            alert('Conexão com WK Radar estabelecida com sucesso!');
          } else {
            alert('Falha na conexão com WK Radar: ' + (data.error || 'erro desconhecido'));
          }
        })
        .catch(err => {
          testWkBtn.disabled = false;
          testWkBtn.innerHTML = '<img src="https://img.icons8.com/nolan/64/link.png" width="18" height="18" alt="Link" style="vertical-align:middle;margin-right:4px;"> Testar Conexão';
          alert('Erro: ' + err.message);
        });
    });
  }

  // =========================================================================
  // UPLOAD DADOS ECONÔMICOS
  // =========================================================================

  const toggleUploadBtn = document.getElementById('toggleUploadConfig');
  const uploadConfig = document.getElementById('uploadConfigFields');
  if (toggleUploadBtn && uploadConfig) {
    toggleUploadBtn.addEventListener('click', function () {
      uploadConfig.classList.toggle('hidden');
    });
  }

  const uploadEconomicoBtn = document.getElementById('uploadEconomicoBtn');
  const uploadEconomicoInput = document.getElementById('uploadEconomico');
  if (uploadEconomicoBtn && uploadEconomicoInput) {
    uploadEconomicoBtn.addEventListener('click', function () {
      const file = uploadEconomicoInput.files[0];
      if (!file) {
        alert('Selecione um arquivo CSV ou Excel primeiro.');
        return;
      }

      const statusEl = document.getElementById('uploadEconomicoStatus');
      statusEl.innerHTML = '<img src="https://img.icons8.com/nolan/64/synchronize.png" width="18" height="18" alt="Carregando" style="vertical-align:middle;margin-right:4px;"> Enviando arquivo...';
      statusEl.className = 'mt-1';
      uploadEconomicoBtn.disabled = true;

      const formData = new FormData();
      formData.append('arquivo_economico', file);

      fetch('?action=upload_economico', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      })
        .then(r => r.json())
        .then(data => {
          uploadEconomicoBtn.disabled = false;
          if (data.success) {
            statusEl.innerHTML = '<img src="/assets/img/icons8-ok.gif" width="20" height="20" alt="OK" style="vertical-align:middle;margin-right:4px;"> ' + data.data.mensagem;
            statusEl.className = 'mt-1 upload-success';

            if (data.data.codigos_nao_mapeados && data.data.codigos_nao_mapeados.length > 0) {
              statusEl.innerHTML += ' (<img src="/assets/img/icons8-erro.gif" width="16" height="16" alt="" style="vertical-align:middle;"> ' + data.data.codigos_nao_mapeados.length + ' códigos não reconhecidos)';
            }

            window.dadosEconomicosGlobais = data.data.dados;
          } else {
            statusEl.innerHTML = '<img src="/assets/img/icons8-erro.gif" width="20" height="20" alt="Erro" style="vertical-align:middle;margin-right:4px;"> ' + (data.error || 'Erro ao importar arquivo');
            statusEl.className = 'mt-1 upload-error';
          }
        })
        .catch(err => {
          uploadEconomicoBtn.disabled = false;
          statusEl.innerHTML = '<img src="/assets/img/icons8-erro.gif" width="20" height="20" alt="Erro" style="vertical-align:middle;margin-right:4px;"> Erro: ' + err.message;
          statusEl.className = 'mt-1 upload-error';
        });
    });
  }

  // Per-company upload buttons (editor page)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.upload-economico-btn');
    if (btn) {
      const idx = btn.dataset.empresaIdx;
      const input = document.querySelector(`.upload-economico-input[data-empresa-idx="${idx}"]`);
      if (input) input.click();
    }
  });

  // Per-company file input change - atualiza inputs via JS sem reload
  document.addEventListener('change', function (e) {
    const input = e.target.closest('.upload-economico-input');
    if (input) {
      const file = input.files[0];
      if (!file) return;

      const idx = input.dataset.empresaIdx;
      const uploadRow = document.querySelector(`.upload-economico-btn[data-empresa-idx="${idx}"]`)
        ?.closest('.upload-economico-empresa');
      const statusEl = uploadRow?.querySelector('.upload-economico-status');

      if (statusEl) {
        statusEl.innerHTML = '<img src="https://img.icons8.com/nolan/64/synchronize.png" width="18" height="18" alt="Carregando" style="vertical-align:middle;margin-right:4px;"> Enviando...';
        statusEl.className = 'upload-economico-status';
      }

      const formData = new FormData();
      formData.append('arquivo_economico', file);
      formData.append('empresa_idx', idx);

      fetch('?action=upload_economico', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      })
        .then(r => r.json())
        .then(data => {
          if (data.success && data.data.dados) {
            const dados = data.data.dados;
            const panel = document.querySelector(`[data-company-panel="${idx}"]`);
            let updated = 0;

            if (panel) {
              Object.entries(dados).forEach(([chave, valor]) => {
                const inputEl = panel.querySelector(`input[name="empresa[${idx}][${chave}]"]`);
                if (inputEl) {
                  inputEl.value = valor;
                  updated++;
                  inputEl.style.transition = 'background 0.8s ease';
                  inputEl.style.background = '#e6f4ea';
                  setTimeout(() => { inputEl.style.background = ''; }, 2500);
                }
              });
            }

            if (statusEl) {
              statusEl.innerHTML = '<img src="/assets/img/icons8-ok.gif" alt="ok" width="20" height="20" style="vertical-align:middle;margin-right:4px;"> ' + updated + ' campos atualizados (de ' + Object.keys(dados).length + ' extraídos).';
              statusEl.className = 'upload-economico-status upload-success';
            }

            if (data.data.codigos_nao_mapeados && data.data.codigos_nao_mapeados.length > 0) {
              if (statusEl) {
                statusEl.innerHTML += ' <img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20" style="vertical-align:middle;"> ' + data.data.codigos_nao_mapeados.length + ' código(s) não reconhecido(s)';
              }
            }
          } else {
            if (statusEl) {
              statusEl.innerHTML = '<img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20" style="vertical-align:middle;margin-right:4px;"> ' + (data.error || 'Erro ao importar arquivo');
              statusEl.className = 'upload-economico-status upload-error';
            }
          }
        })
        .catch(err => {
          if (statusEl) {
            statusEl.innerHTML = '<img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20" style="vertical-align:middle;margin-right:4px;"> Erro: ' + err.message;
            statusEl.className = 'upload-economico-status upload-error';
          }
        });
    }
  });

  // Apply global economic data to specific company (fetched from server session)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.upload-economico-global-btn');
    if (btn) {
      const idx = btn.dataset.empresaIdx;
      const statusEl = btn.closest('.upload-economico-empresa')
        ?.querySelector('.upload-economico-status');

      if (statusEl) {
        statusEl.innerHTML = '<img src="https://img.icons8.com/nolan/64/synchronize.png" width="18" height="18" alt="Carregando" style="vertical-align:middle;margin-right:4px;"> Buscando dados globais...';
        statusEl.className = 'upload-economico-status';
      }
      btn.disabled = true;

      fetch('?action=get_economico_data', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
        .then(r => r.json())
        .then(data => {
          btn.disabled = false;
          if (data.success && data.data.dados) {
            const dadosGlobais = data.data.dados;
            const panel = document.querySelector(`[data-company-panel="${idx}"]`);
            if (panel) {
              let count = 0;
              Object.entries(dadosGlobais).forEach(([chave, valor]) => {
                const input = panel.querySelector(`input[name="empresa[${idx}][${chave}]"]`);
                if (input && input.value === '') {
                  input.value = valor;
                  count++;
                  input.style.transition = 'background 0.5s ease';
                  input.style.background = '#e6f4ea';
                  setTimeout(() => { input.style.background = ''; }, 2000);
                }
              });

              if (statusEl) {
                statusEl.innerHTML = '<img src="/assets/img/icons8-ok.gif" alt="ok" width="20" height="20" style="vertical-align:middle;margin-right:4px;"> ' + count + ' campos preenchidos dos dados globais.';
                statusEl.className = 'upload-economico-status upload-success';
              }
            }
          } else {
            if (statusEl) {
              statusEl.innerHTML = '<img src="/assets/img/icons8-erro.gif" alt="Aviso" width="20" height="20" style="vertical-align:middle;margin-right:4px;"> ' + (data.error || 'Nenhum dado global importado. Faça upload primeiro.');
              statusEl.className = 'upload-economico-status upload-error';
            }
          }
        })
        .catch(err => {
          btn.disabled = false;
          if (statusEl) {
            statusEl.innerHTML = '<img src="/assets/img/icons8-erro.gif" alt="Erro" width="20" height="20" style="vertical-align:middle;margin-right:4px;"> Erro ao buscar dados: ' + err.message;
            statusEl.className = 'upload-economico-status upload-error';
          }
        });
    }
  });

  // =========================================================================
  // COLLAPSIBLE SECTIONS
  // =========================================================================

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-toggle-economico]');
    if (btn) {
      const idx = btn.dataset.toggleEconomico;
      const wrapper = document.querySelector(`[data-economico-wrapper="${idx}"]`);
      if (wrapper) {
        wrapper.classList.toggle('collapsed');
        const grid = document.getElementById(`economico-grid-${idx}`);
        if (grid) {
          grid.classList.toggle('visible');
        }
        const icon = '<img width="20" height="20" src="https://img.icons8.com/nolan/64/ophthalmology.png" alt="ophthalmology" style="vertical-align: middle; margin-right:6px;">';

        btn.innerHTML = wrapper.classList.contains('collapsed')
          ? `${icon}Mostrar tabela de códigos (${btn.closest('.economico-wrapper')?.querySelectorAll('.economico-table tbody tr').length} campos)`
          : `${icon}Ocultar tabela de códigos`;
      }
    }
  });

  // =========================================================================
  // PREVIEW CSV MODAL
  // =========================================================================

  const previewBtn = document.getElementById('previewCSVBtn');
  const previewModal = document.getElementById('previewModal');
  const previewModalClose = document.getElementById('previewModalClose');
  const previewModalCancel = document.getElementById('previewModalCancel');
  const previewModalConfirm = document.getElementById('previewModalConfirm');
  const formMultiEmpresa = document.getElementById('formMultiEmpresa');

  if (previewBtn && previewModal && formMultiEmpresa) {
    previewBtn.addEventListener('click', function () {
      const formData = new FormData(formMultiEmpresa);

      previewModal.classList.remove('hidden');
      const tbody = document.getElementById('previewTableBody');
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400);">' +
        '<img src="https://img.icons8.com/nolan/64/synchronize.png" width="18" height="18" alt="Carregando" style="vertical-align:middle;margin-right:4px;"> Carregando preview...</td></tr>';
      previewBtn.disabled = true;
      previewBtn.innerHTML = '<img src="https://img.icons8.com/nolan/64/synchronize.png" width="18" height="18" alt="Gerando" style="vertical-align:middle;margin-right:4px;"> Gerando preview...';

      fetch('?action=preview_csv', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
      })
        .then(r => r.json())
        .then(data => {
          previewBtn.disabled = false;
          previewBtn.innerHTML = '<img src="https://img.icons8.com/nolan/64/ophthalmology.png" width="20" height="20" alt="Preview" style="vertical-align:middle;margin-right:6px;"> Preview CSV';

          if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--danger);">' +
              '<img src="/assets/img/icons8-erro.gif" width="20" height="20" alt="Erro" style="vertical-align:middle;margin-right:4px;"> ' + (data.error || 'Erro ao gerar preview') + '</td></tr>';
            return;
          }

          const d = data.data;

          document.getElementById('previewTotal').textContent = d.total;
          document.getElementById('previewValidas').textContent = d.validas;
          document.getElementById('previewErros').textContent = d.erros;
          document.getElementById('previewAno').textContent = d.ano;
          document.getElementById('previewTotal2').textContent = d.total;

          const errorsContainer = document.getElementById('previewErrorsContainer');
          const errorsList = document.getElementById('previewErrorsList');
          if (d.erros_detalhados && d.erros_detalhados.length > 0) {
            errorsContainer.classList.remove('hidden');
            errorsList.innerHTML = d.erros_detalhados.map(e => {
              return '<div style="margin-top:0.5rem;padding:0.5rem;background:var(--danger-light);border-radius:var(--radius-xs);">' +
                '<strong>' + escapeHtml(e.empresa) + '</strong> (' + escapeHtml(e.cnpj) + '):<br>' +
                e.erros.map(err => '<span style="font-size:0.82rem;color:#a50e0e;">' +
                  '<img src="/assets/img/icons8-erro.gif" width="14" height="14" alt="Erro" style="vertical-align:middle;margin-right:2px;"> ' + escapeHtml(err) + '</span><br>').join('') +
                '</div>';
            }).join('');
          } else {
            errorsContainer.classList.add('hidden');
          }

          tbody.innerHTML = d.preview.map(p => {
            const statusClass = p.valida ? '' : 'row-invalid';
            const statusBadge = p.valida
              ? '<span class="badge badge-success"><img src="/assets/img/icons8-ok.gif" width="18" height="18" alt="OK" style="vertical-align:middle;margin-right:2px;"> OK</span>'
              : '<span class="badge badge-warning"><img src="https://img.icons8.com/nolan/64/warning-shield.png" width="18" height="18" alt="Aviso" style="vertical-align:middle;margin-right:2px;"> Pendente</span>';
            return '<tr class="' + statusClass + '">' +
              '<td>' + p.idx + '</td>' +
              '<td><code>' + escapeHtml(p.cnpj) + '</code></td>' +
              '<td>' + escapeHtml(p.razao) + '</td>' +
              '<td>' + escapeHtml(p.uf) + '</td>' +
              '<td><code>' + escapeHtml(p.cnae) + '</code></td>' +
              '<td>' + p.preenchidos + '/' + p.total_campos_obrig + '</td>' +
              '<td>' + (p.econ_preenchidos > 0
                ? '<img src="https://img.icons8.com/nolan/64/money.png" width="18" height="18" alt="Econ" style="vertical-align:middle;margin-right:2px;"> ' + p.econ_preenchidos
                : '—') + '</td>' +
              '<td>' + statusBadge + '</td>' +
              '</tr>';
          }).join('');
        })
        .catch(err => {
          previewBtn.disabled = false;
          previewBtn.innerHTML = '<img src="https://img.icons8.com/nolan/64/ophthalmology.png" width="20" height="20" alt="Preview" style="vertical-align:middle;margin-right:6px;"> Preview CSV';
          tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--danger);">' +
            '<img src="/assets/img/icons8-erro.gif" width="20" height="20" alt="Erro" style="vertical-align:middle;margin-right:4px;"> Erro: ' + err.message + '</td></tr>';
        });
    });

    function closeModal() {
      previewModal.classList.add('hidden');
    }

    previewModalClose.addEventListener('click', closeModal);
    previewModalCancel.addEventListener('click', closeModal);

    previewModal.addEventListener('click', function (e) {
      if (e.target === previewModal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !previewModal.classList.contains('hidden')) {
        closeModal();
      }
    });

    previewModalConfirm.addEventListener('click', function () {
      formMultiEmpresa.submit();
    });
  }

  function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // =========================================================================
  // UTILITÁRIOS
  // =========================================================================

  function showLoading(show, text) {
    if (loadingOverlay) {
      loadingOverlay.classList.toggle('active', show);
    }
    if (loadingText && text) {
      loadingText.textContent = text;
    }
  }

  function showError(msg) {
    if (errorSection && errorMessage) {
      errorMessage.textContent = msg;
      errorSection.classList.remove('hidden');
      errorSection.classList.add('fade-in');
    }
  }

  function hideError() {
    if (errorSection) {
      errorSection.classList.add('hidden');
    }
  }

  document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
      alert.style.transition = 'opacity 0.5s ease';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 500);
    }, 8000);
  });

})();

// =========================================================================
// TOAST NOTIFICATION SYSTEM
// =========================================================================

window.showToast = function (message, type) {
  type = type || 'success';
  var container = document.getElementById('toast-container');
  if (!container) return;

  var toast = document.createElement('div');
  toast.className = 'toast toast-' + type;

  var icon = type === 'success'
    ? '<img src="/assets/img/icons8-ok.gif" width="20" height="20" alt="OK">'
    : '<img src="/assets/img/icons8-erro.gif" width="20" height="20" alt="Erro">';

  var closeBtn = document.createElement('button');
  closeBtn.className = 'toast-close';
  closeBtn.innerHTML = '&times;';
  closeBtn.onclick = function () {
    toast.classList.add('removing');
    setTimeout(function () {
      if (toast.parentNode) toast.remove();
    }, 300);
  };

  var iconSpan = document.createElement('span');
  iconSpan.className = 'toast-icon';
  iconSpan.innerHTML = icon;
  var msgSpan = document.createElement('span');
  msgSpan.className = 'toast-message';
  msgSpan.textContent = message;

  toast.appendChild(iconSpan);
  toast.appendChild(msgSpan);
  toast.appendChild(closeBtn);
  container.appendChild(toast);

  setTimeout(function () {
    if (toast.parentNode) {
      toast.classList.add('removing');
      setTimeout(function () {
        if (toast.parentNode) toast.remove();
      }, 300);
    }
  }, 4000);
};
