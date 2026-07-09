@php
    $tinymceSelector = $tinymceSelector ?? '#content';
    $tinymceHeight = $tinymceHeight ?? 600;
    $tinymceFormId = $tinymceFormId ?? null;
    $tinymceRequiredMessage = $tinymceRequiredMessage ?? 'يرجى إدخال المحتوى';
    $tinymceUploadUrl = $tinymceUploadUrl ?? url('/upload');
@endphp
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js"></script>
<script>
(function () {
    const selector = @json($tinymceSelector);
    const formId = @json($tinymceFormId);
    const requiredMessage = @json($tinymceRequiredMessage);

    function fieldIdFromSelector() {
        return selector.replace(/^#/, '');
    }

    function initTinyMCE() {
        if (typeof tinymce === 'undefined') {
            setTimeout(initTinyMCE, 100);
            return;
        }

        if (tinymce.get(fieldIdFromSelector())) {
            return;
        }

        tinymce.init({
            selector: selector,
            height: {{ (int) $tinymceHeight }},
            directionality: 'rtl',
            language: 'ar',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@latest/langs6/ar.js',
            promotion: false,
            branding: false,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code codesample fullscreen insertdatetime media table help wordcount emoticons directionality',
            toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image media table | codesample code | fullscreen | help',
            menubar: 'file edit view insert format tools table help',
            menu: {
                file: { title: 'ملف', items: 'newdocument restoredraft | preview | print' },
                edit: { title: 'تحرير', items: 'undo redo | cut copy paste | selectall | searchreplace' },
                view: { title: 'عرض', items: 'code | visualaid visualchars visualblocks | preview fullscreen' },
                insert: { title: 'إدراج', items: 'image link media codesample | charmap emoticons hr | pagebreak nonbreaking anchor | insertdatetime' },
                format: { title: 'تنسيق', items: 'bold italic underline strikethrough | formats blockformats fontformats fontsizes align | forecolor backcolor | removeformat' },
                tools: { title: 'أدوات', items: 'code wordcount' },
                table: { title: 'جدول', items: 'inserttable | cell row column | tableprops deletetable' },
                help: { title: 'تعليمات', items: 'help' }
            },
            content_style: 'body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; font-size: 14px; direction: rtl; }',
            elementpath: true,
            resize: true,
            contextmenu: 'link image table',
            paste_as_text: false,
            paste_data_images: true,
            relative_urls: false,
            remove_script_host: false,
            image_advtab: true,
            image_uploadtab: true,
            automatic_uploads: true,
            images_upload_url: @json($tinymceUploadUrl),
            media_live_embeds: true,
            codesample_languages: [
                { text: 'HTML/XML', value: 'markup' },
                { text: 'JavaScript', value: 'javascript' },
                { text: 'CSS', value: 'css' },
                { text: 'PHP', value: 'php' },
                { text: 'Python', value: 'python' },
                { text: 'SQL', value: 'sql' },
                { text: 'JSON', value: 'json' }
            ],
            setup: function (editor) {
                editor.on('init', function () {
                    window.dispatchEvent(new CustomEvent('tinymce:ready', { detail: { id: editor.id } }));
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(initTinyMCE, 200);

        if (formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', function (e) {
                    const editor = tinymce.get(fieldIdFromSelector());
                    if (editor) {
                        editor.save();
                        const field = document.getElementById(fieldIdFromSelector());
                        if (field && !field.value.trim()) {
                            e.preventDefault();
                            alert(requiredMessage);
                            editor.focus();
                        }
                    }
                });
            }
        }
    });
})();
</script>
