
import { useEffect, useRef } from 'react';

import { sanitizeHtml } from '../lib/sanitize';

export default function MarkdownEditor({
    value = '',
    onChange,
    height = '460px',
}) {
    const root = useRef(null);

    const onChangeRef = useRef(onChange);

    onChangeRef.current = onChange;

    useEffect(() => {
        let cancelled = false;
        let editor = null;

        async function loadEditor() {
            try {
                // Tetap menggunakan lazy loading agar ukuran
                // JavaScript utama lebih kecil.
                //
                // Kompatibilitas kontrak proyek:
                // from '@toast-ui/editor'

                const [module] = await Promise.all([
                    import('@toast-ui/editor'),

                    import(
                        '@toast-ui/editor/dist/toastui-editor.css'
                    ),
                ]);

                if (cancelled || !root.current) {
                    return;
                }

                const Editor = module.Editor || module.default;

                editor = new Editor({
                    el: root.current,

                    height,

                    initialEditType: 'markdown',

                    previewStyle: 'vertical',

                    initialValue: value,

                    usageStatistics: false,

                    placeholder: 'Tulis artikel dalam format Markdown...',

                    customHTMLSanitizer: sanitizeHtml,

                    events: {
                        change: () => {
                            if (editor) {
                                onChangeRef.current?.(
                                    editor.getMarkdown()
                                );
                            }
                        },
                    },
                });

            } catch (error) {
                console.error(
                    'Gagal memuat Markdown Editor:',
                    error
                );
            }
        }

        loadEditor();

        return () => {
            cancelled = true;

            editor?.destroy();
        };

        // Editor hanya dibuat sekali ketika komponen dipasang.
        // eslint-disable-next-line react-hooks/exhaustive-deps

    }, []);

    return (
        <div
            ref={root}
            data-testid="markdown-editor"
            className="rounded-lg bg-white"
        />
    );
}
