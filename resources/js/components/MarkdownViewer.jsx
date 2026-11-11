import Viewer from '@toast-ui/editor/dist/toastui-editor-viewer';
import '@toast-ui/editor/dist/toastui-editor-viewer.css';
import { useEffect, useRef } from 'react';
import { sanitizeHtml } from '../lib/sanitize';

/** Render Markdown dengan TOAST UI Viewer (halaman baca publik & detail artikel penulis). */
export default function MarkdownViewer({ content }) {
    const root = useRef(null);

    useEffect(() => {
        const viewer = new Viewer({
            el: root.current,
            initialValue: content ?? '',
            usageStatistics: false,
            customHTMLSanitizer: sanitizeHtml,
        });

        return () => viewer.destroy();
    }, [content]);

    return <div ref={root} data-testid="markdown-viewer" className="reading" />;
}
