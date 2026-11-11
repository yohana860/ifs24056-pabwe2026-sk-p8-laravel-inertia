import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';
import { useEffect, useRef } from 'react';
import { sanitizeHtml } from '../lib/sanitize';

/** Editor Markdown TOAST UI (@toast-ui/editor) untuk membuat & mengubah artikel. */
export default function MarkdownEditor({ value = '', onChange, height = '460px' }) {
    const root = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        const editor = new Editor({
            el: root.current,
            height,
            initialEditType: 'markdown',
            previewStyle: 'vertical',
            initialValue: value,
            usageStatistics: false,
            placeholder: 'Tulis artikel dalam format Markdown...',
            customHTMLSanitizer: sanitizeHtml,
            events: {
                change: () => onChangeRef.current(editor.getMarkdown()),
            },
        });

        return () => editor.destroy();
        // Editor hanya dibuat sekali per mount; nilai awal dibaca saat mount.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return <div ref={root} data-testid="markdown-editor" className="rounded-lg bg-white" />;
}
