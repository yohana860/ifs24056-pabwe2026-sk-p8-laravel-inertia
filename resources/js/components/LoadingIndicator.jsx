import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import Spinner from './Spinner';

/**
 * Top progress bar + floating status pill "Memproses tindakan...".
 * Aktif saat navigasi/request Inertia, dan saat event `app:busy` (mis. request fetch ke OpenAI).
 */
export default function LoadingIndicator() {
    const [active, setActive] = useState(false);
    const [progress, setProgress] = useState(0);
    const pending = useRef(0);
    const timer = useRef(null);

    useEffect(() => {
        const begin = () => {
            pending.current += 1;
            if (pending.current > 1) {
                return;
            }
            clearInterval(timer.current);
            setActive(true);
            setProgress(8);
            timer.current = setInterval(() => setProgress((p) => (p < 90 ? p + (90 - p) * 0.12 : p)), 200);
        };

        const end = () => {
            pending.current = Math.max(0, pending.current - 1);
            if (pending.current > 0) {
                return;
            }
            clearInterval(timer.current);
            setProgress(100);
            timer.current = setTimeout(() => {
                setActive(false);
                setProgress(0);
            }, 350);
        };

        const onBusy = (event) => (event.detail ? begin() : end());

        const offStart = router.on('start', begin);
        const offFinish = router.on('finish', end);
        window.addEventListener('app:busy', onBusy);

        return () => {
            offStart();
            offFinish();
            window.removeEventListener('app:busy', onBusy);
            clearInterval(timer.current);
            clearTimeout(timer.current);
        };
    }, []);

    return (
        <>
            <div
                data-testid="top-progress-bar"
                className="pointer-events-none fixed inset-x-0 top-0 z-[100] h-1"
                style={{ opacity: active ? 1 : 0, transition: 'opacity 250ms' }}
            >
                <div className="h-full bg-primary shadow-[0_0_8px_rgba(79,70,229,0.7)] transition-[width] duration-200 ease-out" style={{ width: `${progress}%` }} />
            </div>

            {active && (
                <div
                    data-testid="status-pill"
                    role="status"
                    aria-live="polite"
                    className="pointer-events-none fixed bottom-5 left-1/2 z-[100] flex -translate-x-1/2 items-center gap-2 rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-lg"
                >
                    <Spinner />
                    Memproses tindakan...
                </div>
            )}
        </>
    );
}
