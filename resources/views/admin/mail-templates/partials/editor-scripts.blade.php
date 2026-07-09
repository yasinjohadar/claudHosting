@include('admin.partials.tinymce-editor', [
    'tinymceSelector' => '#body_html',
    'tinymceHeight' => 550,
    'tinymceFormId' => 'mailTemplateForm',
    'tinymceRequiredMessage' => 'يرجى إدخال محتوى HTML للقالب',
])

<script>
document.addEventListener('DOMContentLoaded', function () {
    function wrapVariable(name) {
        return '{' + '{' + name + '}' + '}';
    }

    function bindVariableButton(btn) {
        btn.addEventListener('click', function () {
            const varName = this.getAttribute('data-variable');
            const variable = wrapVariable(varName);
            const editor = typeof tinymce !== 'undefined' ? tinymce.get('body_html') : null;
            const subjectInput = document.querySelector('input[name="subject"]');

            if (document.activeElement === subjectInput) {
                const start = subjectInput.selectionStart ?? subjectInput.value.length;
                const end = subjectInput.selectionEnd ?? subjectInput.value.length;
                subjectInput.value = subjectInput.value.substring(0, start) + variable + subjectInput.value.substring(end);
                subjectInput.focus();
                subjectInput.setSelectionRange(start + variable.length, start + variable.length);
            } else if (editor) {
                editor.insertContent(variable);
                editor.focus();
            }
        });
    }

    document.querySelectorAll('.insert-variable').forEach(bindVariableButton);

    const variablesInput = document.querySelector('input[name="available_variables"]');
    const variableButtons = document.getElementById('variableButtons');
    if (variablesInput && variableButtons) {
        variablesInput.addEventListener('change', function () {
            const vars = this.value.split(',').map(function (v) { return v.trim(); }).filter(Boolean);
            variableButtons.innerHTML = '';
            vars.forEach(function (name) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-primary insert-variable';
                btn.setAttribute('data-variable', name);
                btn.textContent = wrapVariable(name);
                bindVariableButton(btn);
                variableButtons.appendChild(btn);
            });
        });
    }
});
</script>
