(function () {
  'use strict';

  const text = (key) => (window.Joomla && Joomla.Text ? Joomla.Text._(key) : key);

  const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const formatBytes = (bytes) => {
    if (!bytes) {
      return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

    return `${(bytes / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
  };

  const getEditorValue = () => {
    if (window.JoomlaEditor && JoomlaEditor.get) {
      const editor = JoomlaEditor.get('jform_description');

      if (editor) {
        return editor.getValue();
      }
    }

    if (window.Joomla && Joomla.editors && Joomla.editors.instances && Joomla.editors.instances.jform_description) {
      return Joomla.editors.instances.jform_description.getValue();
    }

    const textarea = document.getElementById('jform_description');

    return textarea ? textarea.value : '';
  };

  const parseSaveResponse = async (response) => {
    const raw = await response.text();
    const getPayloadError = (payload) => {
      if (!payload) {
        return '';
      }

      if (payload.error) {
        return payload.error;
      }

      if (payload.message) {
        return payload.message;
      }

      if (payload.messages) {
        return Object.values(payload.messages).flat().filter(Boolean).join(' ');
      }

      return '';
    };

    if (!response.ok) {
      return {
        success: false,
        error: raw || response.statusText,
      };
    }

    try {
      let parsed = JSON.parse(raw);

      if (typeof parsed.data === 'string') {
        parsed.data = JSON.parse(parsed.data);
      }

      return {
        success: Boolean(parsed.success && parsed.data && parsed.data.success),
        error: getPayloadError(parsed.data) || getPayloadError(parsed),
        raw: parsed,
      };
    } catch (error) {
      const jsonStart = raw.indexOf('{"success"');

      if (jsonStart > -1) {
        try {
          const parsed = JSON.parse(raw.slice(jsonStart));
          const data = typeof parsed.data === 'string' ? JSON.parse(parsed.data) : parsed.data;

          return {
            success: Boolean(parsed.success && data && data.success),
            error: raw.slice(0, jsonStart) || getPayloadError(data) || getPayloadError(parsed),
            raw: parsed,
          };
        } catch (innerError) {
          return {
            success: false,
            error: raw,
          };
        }
      }

      return {
        success: false,
        error: raw,
      };
    }
  };

  const parseListResponse = async (response) => {
    const raw = await response.text();

    try {
      return JSON.parse(raw);
    } catch (error) {
      const jsonStart = raw.indexOf('{"success"');

      if (jsonStart > -1) {
        return JSON.parse(raw.slice(jsonStart));
      }

      const textOnly = raw
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/?[^>]+(>|$)/g, '')
        .trim();

      throw new Error(textOnly || text('COM_JOOMGALLERY_FTP_IMPORT_LOAD_FAILED'));
    }
  };

  const callback = () => {
    const area = document.getElementById('ftp-import-area');

    if (!area) {
      return;
    }

    const form = document.getElementById('adminForm');
    const tbody = document.getElementById('ftp-import-files');
    const status = document.getElementById('ftp-import-status');
    const path = document.getElementById('ftp-import-path');
    const action = document.getElementById('ftp-import-action');
    const progress = document.getElementById('ftp-import-progress');
    const refresh = document.getElementById('ftp-import-refresh');
    const selectAll = document.getElementById('ftp-import-select-all');
    const selectNone = document.getElementById('ftp-import-select-none');
    const start = document.getElementById('ftp-import-start');
    const uploader = document.getElementById('jform_uploader');
    const ftpFile = document.getElementById('jform_ftp_file');
    const ftpAction = document.getElementById('jform_ftp_action');

    let files = [];
    let busy = false;

    const setProgress = (done, total) => {
      const percent = total ? Math.round((done / total) * 100) : 0;
      progress.style.width = `${percent}%`;
      progress.textContent = `${percent}%`;
    };

    const renderRows = () => {
      if (!files.length) {
        tbody.innerHTML = `<tr><td colspan="5">${text('COM_JOOMGALLERY_FTP_IMPORT_NO_FILES')}</td></tr>`;
        return;
      }

      tbody.innerHTML = files.map((file, index) => {
        const date = new Date(file.mtime * 1000).toLocaleString();
        const filename = escapeHtml(file.name);

        return `
          <tr data-file="${filename}">
            <td><input type="checkbox" class="form-check-input ftp-import-check" value="${filename}" id="ftp-file-${index}"></td>
            <td><label for="ftp-file-${index}">${filename}</label></td>
            <td>${formatBytes(file.size)}</td>
            <td>${date}</td>
            <td class="ftp-import-row-status"></td>
          </tr>
        `;
      }).join('');
    };

    const loadFiles = async () => {
      if (busy) {
        return;
      }

      status.textContent = text('COM_JOOMGALLERY_FTP_IMPORT_LOADING');
      setProgress(0, 0);

      try {
        const response = await fetch(area.dataset.listUrl, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
          },
        });
        const payload = await parseListResponse(response);
        const data = payload.data || payload;

        if (!data.success) {
          throw new Error(data.error || text('COM_JOOMGALLERY_FTP_IMPORT_LOAD_FAILED'));
        }

        files = data.files || [];
        path.textContent = data.path ? `${text('COM_JOOMGALLERY_FTP_IMPORT_DIRECTORY')}: ${data.path}` : '';
        status.textContent = '';
        renderRows();
      } catch (error) {
        files = [];
        tbody.innerHTML = `<tr><td colspan="5">${error.message}</td></tr>`;
        status.textContent = text('COM_JOOMGALLERY_FTP_IMPORT_LOAD_FAILED');
      }
    };

    const selectedRows = () => Array.from(tbody.querySelectorAll('.ftp-import-check:checked'))
      .map((checkbox) => checkbox.closest('tr'));

    const saveFile = async (row, fileName, filecounter) => {
      const rowStatus = row.querySelector('.ftp-import-row-status');
      const formData = new FormData(form);

      rowStatus.textContent = text('COM_JOOMGALLERY_FTP_IMPORT_PROCESSING');
      uploader.value = 'ftp';
      ftpFile.value = fileName;
      ftpAction.value = action.value;

      formData.set('jform[uploader]', 'ftp');
      formData.set('jform[ftp_file]', fileName);
      formData.set('jform[ftp_action]', action.value);
      formData.set('jform[filecounter]', filecounter);
      formData.set('filecounter', filecounter);

      if (formData.has('jform[description]') && formData.get('jform[description]').trim().length === 0) {
        formData.set('jform[description]', getEditorValue());
      }

      const response = await fetch(area.dataset.saveUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      });
      const result = await parseSaveResponse(response);

      rowStatus.textContent = result.success ? text('COM_JOOMGALLERY_FTP_IMPORT_DONE') : text('COM_JOOMGALLERY_FTP_IMPORT_FAILED');

      if (!result.success) {
        throw new Error(result.error || text('COM_JOOMGALLERY_FTP_IMPORT_FAILED'));
      }
    };

    const importSelected = async () => {
      if (busy) {
        return;
      }

      form.classList.remove('was-validated');

      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        Joomla.renderMessages({ error: [`${text('JGLOBAL_VALIDATION_FORM_FAILED')}. ${text('COM_JOOMGALLERY_ERROR_FILL_REQUIRED_FIELDS')}`] });
        window.scrollTo(0, 0);
        return;
      }

      const rows = selectedRows();

      if (!rows.length) {
        Joomla.renderMessages({ warning: [text('COM_JOOMGALLERY_FTP_IMPORT_NO_FILES_SELECTED')] });
        return;
      }

      busy = true;
      start.disabled = true;
      refresh.disabled = true;
      setProgress(0, rows.length);
      status.textContent = `${text('COM_JOOMGALLERY_FTP_IMPORT_PROCESSING')} 0/${rows.length}`;

      let nmbStart = 1;
      const nmbStartInput = document.getElementById('jform_nmb_start');

      if (nmbStartInput) {
        nmbStart = parseInt(nmbStartInput.value, 10) || 1;
      }

      let done = 0;
      let failed = 0;

      for (let i = 0; i < rows.length; i += 1) {
        const row = rows[i];
        const fileName = row.dataset.file;

        try {
          await saveFile(row, fileName, nmbStart + i);
        } catch (error) {
          failed += 1;
          row.querySelector('.ftp-import-row-status').textContent = error.message;
        }

        done += 1;
        setProgress(done, rows.length);
        status.textContent = `${text('COM_JOOMGALLERY_FTP_IMPORT_PROCESSING')} ${done}/${rows.length}`;
      }

      uploader.value = 'ftp';
      ftpFile.value = '';
      ftpAction.value = 'keep';
      busy = false;
      start.disabled = false;
      refresh.disabled = false;
      const summary = failed
        ? `${text('COM_JOOMGALLERY_FTP_IMPORT_DONE')}: ${done - failed}. ${text('COM_JOOMGALLERY_FTP_IMPORT_FAILED')}: ${failed}.`
        : text('COM_JOOMGALLERY_FTP_IMPORT_DONE');

      if (!failed) {
        await loadFiles();
      }

      status.textContent = summary;
    };

    refresh.addEventListener('click', loadFiles);
    selectAll.addEventListener('click', () => {
      tbody.querySelectorAll('.ftp-import-check').forEach((checkbox) => {
        checkbox.checked = true;
      });
    });
    selectNone.addEventListener('click', () => {
      tbody.querySelectorAll('.ftp-import-check').forEach((checkbox) => {
        checkbox.checked = false;
      });
    });
    start.addEventListener('click', importSelected);

    loadFiles();
  };

  if (document.readyState === 'complete' || (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
    callback();
  } else {
    document.addEventListener('DOMContentLoaded', callback);
  }
}());
