(function () {
  'use strict';

  document.addEventListener('click', event => {
    const opener = event.target.closest('[data-izzy-dialog-open]');
    if (opener) {
      const dialog = document.getElementById(opener.dataset.izzyDialogOpen);
      if (dialog instanceof HTMLDialogElement && !dialog.open) dialog.showModal();
    }

    const closer = event.target.closest('[data-izzy-dialog-close]');
    const dialog = closer && closer.closest('dialog.izzy-dialog[open]');
    if (dialog) dialog.close();
  });

  // Evita que el modal Bootstrap que abrió el diálogo nativo le quite el foco.
  document.querySelectorAll('dialog.izzy-dialog').forEach(dialog => {
    dialog.addEventListener('focusin', event => event.stopPropagation());
  });

  const tools = [
    ['bold', 'Negrita', 'bold'],
    ['italic', 'Cursiva', 'italic'],
    ['insertUnorderedList', 'Lista con viñetas', 'list-ul'],
    ['insertOrderedList', 'Lista numerada', 'list-ol']
  ];
  const allowed = new Set(['B', 'STRONG', 'I', 'EM', 'UL', 'OL', 'LI', 'P', 'DIV', 'BR']);

  function cleanHtml(value) {
    const template = document.createElement('template');
    template.innerHTML = value;
    template.content.querySelectorAll('script, style, template').forEach(node => node.remove());
    Array.from(template.content.querySelectorAll('*')).reverse().forEach(node => {
      if (!allowed.has(node.tagName)) {
        node.replaceWith(...node.childNodes);
        return;
      }
      Array.from(node.attributes).forEach(attribute => node.removeAttribute(attribute.name));
    });
    return template.innerHTML;
  }

  document.querySelectorAll('textarea[data-izzy-editor]').forEach(textarea => {
    const container = document.createElement('div');
    container.className = 'izzy-editor';
    const toolbar = document.createElement('div');
    toolbar.className = 'izzy-editor__toolbar';
    toolbar.setAttribute('role', 'group');
    toolbar.setAttribute('aria-label', 'Formato de texto');
    const content = document.createElement('div');
    content.className = 'izzy-editor__content';
    content.contentEditable = 'true';
    content.setAttribute('role', 'textbox');
    content.setAttribute('aria-multiline', 'true');
    content.setAttribute('aria-required', String(textarea.required));
    content.setAttribute('aria-label', textarea.getAttribute('aria-label') ||
      Array.from(textarea.labels).map(label => label.textContent.trim()).join(' ') || 'Texto enriquecido');
    if (textarea.hasAttribute('aria-describedby')) {
      content.setAttribute('aria-describedby', textarea.getAttribute('aria-describedby'));
    }

    function sync() {
      textarea.value = content.textContent.trim() ? cleanHtml(content.innerHTML) : '';
      if (textarea.validity.valid) content.removeAttribute('aria-invalid');
    }

    if (textarea.readOnly || textarea.disabled) {
      content.contentEditable = 'false';
      content.setAttribute('aria-readonly', 'true');
      toolbar.hidden = true;
    }

    let selection;
    document.addEventListener('selectionchange', () => {
      const selected = window.getSelection();
      if (selected.rangeCount && content.contains(selected.getRangeAt(0).commonAncestorContainer)) {
        selection = selected.getRangeAt(0).cloneRange();
      }
    });

    for (const [command, label, icon] of tools) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'izzy-button izzy-editor__tool';
      button.title = label;
      button.setAttribute('aria-label', label);
      const symbol = document.createElement('i');
      symbol.className = 'fas fa-' + icon;
      symbol.setAttribute('aria-hidden', 'true');
      button.append(symbol);
      button.addEventListener('mousedown', event => event.preventDefault());
      button.addEventListener('click', () => {
        content.focus();
        if (selection && content.contains(selection.commonAncestorContainer)) {
          const selected = window.getSelection();
          selected.removeAllRanges();
          selected.addRange(selection);
        }
        document.execCommand(command);
        sync();
      });
      toolbar.append(button);
    }

    content.innerHTML = cleanHtml(textarea.value);
    sync();
    content.addEventListener('input', sync);
    content.addEventListener('paste', event => {
      event.preventDefault();
      document.execCommand('insertText', false, event.clipboardData.getData('text/plain'));
    });
    content.addEventListener('drop', event => event.preventDefault());
    textarea.addEventListener('invalid', event => {
      event.preventDefault();
      content.setAttribute('aria-invalid', 'true');
      content.focus();
    });
    Array.from(textarea.labels).forEach(label => label.addEventListener('click', event => {
      if (event.target !== content && !content.contains(event.target)) {
        event.preventDefault();
        content.focus();
      }
    }));
    if (textarea.form) {
      textarea.form.addEventListener('reset', () => setTimeout(() => {
        content.innerHTML = cleanHtml(textarea.value);
        sync();
        content.removeAttribute('aria-invalid');
      }, 0));
    }

    container.append(toolbar, content);
    textarea.after(container);
    textarea.hidden = true;
    textarea.classList.add('d-none');
  });
})();
