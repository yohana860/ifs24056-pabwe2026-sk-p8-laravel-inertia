import DOMPurify from 'dompurify';

/**
 * TOAST UI membawa salinan DOMPurify bawaan; sanitizer ini memastikan versi
 * DOMPurify yang sudah ditambal (dependency langsung) yang dipakai saat render.
 */
export const sanitizeHtml = (html) => DOMPurify.sanitize(html);
