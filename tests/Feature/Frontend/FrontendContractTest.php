<?php

namespace Tests\Feature\Frontend;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Kontrak antarmuka (modul 3.6 & 3.7). Halaman dirender di sisi klien oleh React,
 * sehingga di sisi PHP kita memastikan elemen wajib benar-benar ada di sumber JSX
 * yang di-bundle oleh Vite.
 */
class FrontendContractTest extends TestCase
{
    private function js(string $path): string
    {
        $file = resource_path('js/'.$path);
        $this->assertFileExists($file);

        return (string) file_get_contents($file);
    }

    public function test_login_form_has_required_ids_on_rendered_elements(): void
    {
        $login = $this->js('Pages/Auth/Login.jsx');

        $this->assertMatchesRegularExpression('/<input\s+id="login-username"/', $login);
        $this->assertMatchesRegularExpression('/<input\s+id="login-password"/', $login);
        $this->assertMatchesRegularExpression('/<button\s+id="login-submit"/', $login);
        $this->assertSame(1, substr_count($login, 'id="login-username"'));
        $this->assertSame(1, substr_count($login, 'id="login-password"'));
        $this->assertSame(1, substr_count($login, 'id="login-submit"'));
    }

    /** @return array<string, array{string}> */
    public static function publicViews(): array
    {
        return [
            'layout (navbar + footer)' => ['Layouts/PublicLayout.jsx'],
            'beranda (hero banner)' => ['Pages/Public/PostIndex.jsx'],
            'halaman baca (kartu ajakan)' => ['Pages/Public/PostShow.jsx'],
        ];
    }

    #[DataProvider('publicViews')]
    public function test_public_views_link_to_login_and_register(string $path): void
    {
        $source = $this->js($path);

        $this->assertStringContainsString('href="/login"', $source);
        $this->assertStringContainsString('href="/register"', $source);
    }

    public function test_public_layout_has_sticky_navbar_footer_and_no_sidebar(): void
    {
        $layout = $this->js('Layouts/PublicLayout.jsx');

        $this->assertStringContainsString('sticky top-0', $layout);
        $this->assertStringContainsString('Beranda Publik', $layout);
        $this->assertStringContainsString('Halaman Login', $layout);
        $this->assertStringContainsString('Halaman Register', $layout);
        $this->assertStringContainsString('<footer', $layout);
        $this->assertStringNotContainsString('<aside', $layout);
    }

    public function test_modal_is_fullscreen_with_contrasting_header_and_footer_and_right_aligned_actions(): void
    {
        $modal = $this->js('components/FullScreenModal.jsx');

        $this->assertStringContainsString('fixed inset-0', $modal);
        $this->assertStringContainsString('h-dvh w-screen', $modal);
        $this->assertStringContainsString('bg-indigo-700', $modal); // header
        $this->assertStringContainsString('bg-slate-800', $modal); // footer
        $this->assertMatchesRegularExpression('/<footer[^>]*justify-end/', $modal);
        $this->assertLessThan(strpos($modal, '{submitLabel}'), strpos($modal, 'Batal'), 'Batal berada sebelum Simpan (keduanya di kanan footer)');
    }

    public function test_add_edit_and_ai_use_the_fullscreen_modal(): void
    {
        $this->assertStringContainsString('<FullScreenModal', $this->js('components/PostFormModal.jsx'));
        $this->assertStringContainsString('<FullScreenModal', $this->js('components/AiGeneratorModal.jsx'));
    }

    public function test_post_list_uses_list_view_kebab_menu_and_stat_cards(): void
    {
        $index = $this->js('Pages/Post/Index.jsx');
        $kebab = $this->js('components/KebabMenu.jsx');

        $this->assertStringContainsString('data-testid="post-list"', $index);
        $this->assertStringContainsString('<ul', $index);
        $this->assertStringContainsString('Total Artikel', $index);
        $this->assertStringContainsString('Total Selesai', $index);
        $this->assertStringContainsString('Belum Selesai', $index);
        $this->assertStringContainsString("label: 'Detail', icon: EyeIcon, tone: 'primary'", $index);
        $this->assertStringContainsString("label: 'Ubah', icon: PencilIcon, tone: 'primary'", $index);
        $this->assertStringContainsString("label: 'Hapus', icon: TrashIcon, tone: 'danger'", $index);
        $this->assertStringContainsString("'Jadikan Draf' : 'Publikasikan'", $index);
        $this->assertStringContainsString('text-red-600', $kebab);
        $this->assertStringContainsString('text-primary', $kebab);
        $this->assertStringContainsString('DotsVerticalIcon', $kebab);
    }

    public function test_ai_generator_uses_number_input_with_bounds_and_loading_state(): void
    {
        $ai = $this->js('components/AiGeneratorModal.jsx');

        $this->assertStringContainsString('type="number" min={limits.min} max={limits.max}', $ai);
        $this->assertStringContainsString('<Spinner', $ai);
        $this->assertStringContainsString('Generate AI', $ai);
        $this->assertStringNotContainsString('OPENAI', $ai);
        $this->assertStringNotContainsString('api.openai.com', $ai);
    }

    public function test_markdown_editor_and_viewer_use_toast_ui(): void
    {
        $this->assertStringContainsString("from '@toast-ui/editor'", $this->js('components/MarkdownEditor.jsx'));
        $this->assertStringContainsString("@toast-ui/editor/dist/toastui-editor-viewer", $this->js('components/MarkdownViewer.jsx'));
        $this->assertStringContainsString('<MarkdownEditor', $this->js('components/PostFormModal.jsx'));
        $this->assertStringContainsString('<MarkdownViewer', $this->js('Pages/Post/Show.jsx'));
        $this->assertStringContainsString('<MarkdownViewer', $this->js('Pages/Public/PostShow.jsx'));
    }

    public function test_loading_indicators_exist(): void
    {
        $loading = $this->js('components/LoadingIndicator.jsx');

        $this->assertStringContainsString('Memproses tindakan...', $loading);
        $this->assertStringContainsString('top-progress-bar', $loading);
        $this->assertStringContainsString("router.on('start'", $loading);
        $this->assertStringContainsString('<LoadingIndicator', $this->js('Layouts/AppLayout.jsx'));
        $this->assertStringContainsString('<LoadingIndicator', $this->js('Layouts/PublicLayout.jsx'));
        $this->assertStringContainsString('cursor: pointer', (string) file_get_contents(resource_path('css/app.css')));
    }

    public function test_package_dependencies_match_the_module(): void
    {
        $package = json_decode((string) file_get_contents(base_path('package.json')), true);
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

        foreach (['@inertiajs/react', '@toast-ui/editor', 'react', 'react-dom'] as $dependency) {
            $this->assertArrayHasKey($dependency, $package['dependencies']);
        }
        $this->assertArrayHasKey('@vitejs/plugin-react', $package['devDependencies']);

        foreach (['inertiajs/inertia-laravel', 'phpoffice/phpspreadsheet', 'laravel/framework'] as $dependency) {
            $this->assertArrayHasKey($dependency, $composer['require']);
        }
        $this->assertSame('^8.4', $composer['require']['php']);
    }
}
