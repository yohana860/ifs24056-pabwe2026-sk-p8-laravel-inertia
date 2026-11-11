import { Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { DotsVerticalIcon } from './Icons';

/**
 * Menu tindakan kebab (⋮). Setiap item: { label, icon: Icon, tone: 'primary' | 'danger', href? , onClick? }.
 */
export default function KebabMenu({ items }) {
    const [open, setOpen] = useState(false);
    const wrapper = useRef(null);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        const onDown = (event) => {
            if (wrapper.current && !wrapper.current.contains(event.target)) {
                setOpen(false);
            }
        };
        const onKey = (event) => event.key === 'Escape' && setOpen(false);

        document.addEventListener('mousedown', onDown);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onDown);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    return (
        <div ref={wrapper} className="relative">
            <button
                type="button"
                aria-haspopup="menu"
                aria-expanded={open}
                aria-label="Menu tindakan"
                onClick={() => setOpen((value) => !value)}
                className="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800"
            >
                <DotsVerticalIcon />
            </button>

            {open && (
                <div role="menu" className="absolute right-0 top-full z-30 mt-1 w-44 rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                    {items.map(({ label, icon: Icon, tone, href, onClick }) => {
                        const className = `flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm font-medium hover:bg-slate-50 ${tone === 'danger' ? 'text-red-600' : 'text-primary'}`;
                        const content = (
                            <>
                                <Icon className="h-4 w-4" />
                                {label}
                            </>
                        );

                        return href ? (
                            <Link key={label} role="menuitem" href={href} className={className}>
                                {content}
                            </Link>
                        ) : (
                            <button
                                key={label}
                                type="button"
                                role="menuitem"
                                className={className}
                                onClick={() => {
                                    setOpen(false);
                                    onClick();
                                }}
                            >
                                {content}
                            </button>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
