
import { useEffect, useRef } from 'react';

import { sanitizeHtml } from '../lib/sanitize';

export default function MarkdownViewer({ content }) {
    const root = useRef(null);

    useEffect(() => {
        let cancelled = false;
        let viewer = null;

        async function loadViewer() {
            try {
                const [module] = await Promise.all([
                    import(
                        '@toast-ui/editor/dist/toastui-editor-viewer'
                    ),

                    import(
                        '@toast-ui/editor/dist/toastui-editor-viewer.css'
                    ),
                ]);

                if (cancelled || !root.current) {
                    return;
                }

                const Viewer = module.default;

                viewer = new Viewer({
                    el: root.current,

                    initialValue: content ?? '',

                    usageStatistics: false,

                    customHTMLSanitizer: sanitizeHtml,
                });

            } catch (error) {
                console.error(
                    'Gagal memuat Markdown Viewer:',
                    error
                );
            }
        }

        loadViewer();

        return () => {
            cancelled = true;

            viewer?.destroy();
        };

    }, [content]);

    return (
        <div
            ref={root}
            data-testid="markdown-viewer"
            className="reading"
        />
    );
}
