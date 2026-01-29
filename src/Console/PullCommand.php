<?php

declare(strict_types=1);

namespace Lettr\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Lettr\Dto\Template\ListTemplatesFilter;
use Lettr\Dto\Template\Template;
use Lettr\Dto\Template\TemplateDetail;
use Lettr\Laravel\LettrManager;

use function Laravel\Prompts\progress;

class PullCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lettr:pull
                            {--with-mailables : Also generate Mailable classes for each template}
                            {--dry-run : Preview what would be downloaded without writing files}
                            {--project= : Pull templates from a specific project ID}
                            {--template= : Pull only a specific template by slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull email templates from Lettr API as Blade files';

    /**
     * @var array<int, array{slug: string, blade_path: string}>
     */
    protected array $downloadedTemplates = [];

    /**
     * @var array<int, array{class: string, path: string}>
     */
    protected array $generatedMailables = [];

    /**
     * @var array<int, string>
     */
    protected array $skippedTemplates = [];

    public function __construct(
        protected readonly LettrManager $lettr,
        protected readonly Filesystem $files,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Pulling templates from Lettr...');

        $projectId = $this->getProjectId();
        /** @var string|null $templateSlug */
        $templateSlug = $this->option('template');
        $dryRun = (bool) $this->option('dry-run');
        $withMailables = (bool) $this->option('with-mailables');

        // Fetch templates
        $templates = $this->fetchTemplates($projectId, $templateSlug);

        if (empty($templates)) {
            $this->components->warn('No templates found.');

            return self::SUCCESS;
        }

        // Process templates with progress bar
        $this->processTemplates($templates, $projectId, $dryRun, $withMailables);

        // Output summary
        $this->outputSummary($dryRun, $withMailables);

        return self::SUCCESS;
    }

    /**
     * Get the project ID to use for fetching templates.
     */
    protected function getProjectId(): ?int
    {
        $projectOption = $this->option('project');

        if ($projectOption !== null) {
            return (int) $projectOption;
        }

        $configProjectId = config('lettr.default_project_id');

        return is_numeric($configProjectId) ? (int) $configProjectId : null;
    }

    /**
     * Fetch templates from the API.
     *
     * @return array<int, Template>
     */
    protected function fetchTemplates(?int $projectId, ?string $templateSlug): array
    {
        $filter = $projectId !== null
            ? new ListTemplatesFilter(projectId: $projectId, perPage: 100)
            : new ListTemplatesFilter(perPage: 100);

        $response = $this->lettr->templates()->list($filter);
        $templates = $response->templates->all();

        // Filter by slug if specified
        if ($templateSlug !== null) {
            $templates = array_filter(
                $templates,
                fn (Template $t): bool => $t->slug === $templateSlug
            );

            if (empty($templates)) {
                $this->components->error("Template with slug '{$templateSlug}' not found.");
            }
        }

        return array_values($templates);
    }

    /**
     * Process all templates.
     *
     * @param  array<int, Template>  $templates
     */
    protected function processTemplates(array $templates, ?int $projectId, bool $dryRun, bool $withMailables): void
    {
        $progress = progress(
            label: 'Downloading templates',
            steps: count($templates),
        );

        $progress->start();

        foreach ($templates as $template) {
            $this->processTemplate($template, $projectId, $dryRun, $withMailables);
            $progress->advance();
        }

        $progress->finish();
    }

    /**
     * Process a single template.
     */
    protected function processTemplate(Template $template, ?int $projectId, bool $dryRun, bool $withMailables): void
    {
        // Fetch full template details to get the HTML
        $detail = $this->lettr->templates()->get($template->slug, $projectId);

        // Skip templates without HTML
        if (empty($detail->html)) {
            $this->skippedTemplates[] = $detail->slug;

            return;
        }

        // Save blade file
        $bladePath = $this->saveBlade($detail, $dryRun);
        $this->downloadedTemplates[] = [
            'slug' => $detail->slug,
            'blade_path' => $bladePath,
        ];

        // Generate Mailable if requested
        if ($withMailables) {
            $mailable = $this->generateMailable($detail, $dryRun);
            $this->generatedMailables[] = $mailable;
        }
    }

    /**
     * Save the template HTML as a Blade file.
     */
    protected function saveBlade(TemplateDetail $template, bool $dryRun): string
    {
        $bladePath = config('lettr.templates.blade_path');
        $filename = $template->slug.'.blade.php';
        $fullPath = $bladePath.'/'.$filename;
        $relativePath = str_replace(base_path().'/', '', $fullPath);

        if (! $dryRun) {
            $this->ensureDirectoryExists($bladePath);
            $this->files->put($fullPath, (string) $template->html);
        }

        return $relativePath;
    }

    /**
     * Generate a Mailable class for the template.
     *
     * @return array{class: string, path: string}
     */
    protected function generateMailable(TemplateDetail $template, bool $dryRun): array
    {
        $mailablePath = config('lettr.templates.mailable_path');
        $namespace = config('lettr.templates.mailable_namespace');

        $className = $this->slugToClassName($template->slug);
        $filename = $className.'.php';
        $fullPath = $mailablePath.'/'.$filename;
        $relativePath = str_replace(base_path().'/', '', $fullPath);
        $fullyQualifiedClass = $namespace.'\\'.$className;

        if (! $dryRun) {
            $this->ensureDirectoryExists($mailablePath);
            $stub = $this->getMailableStub($namespace, $className, $template);
            $this->files->put($fullPath, $stub);
        }

        return [
            'class' => $fullyQualifiedClass,
            'path' => $relativePath,
        ];
    }

    /**
     * Get the populated mailable stub content.
     */
    protected function getMailableStub(string $namespace, string $className, TemplateDetail $template): string
    {
        $stubPath = __DIR__.'/../../stubs/mailable.stub';
        $stub = $this->files->get($stubPath);

        // Convert template name to a readable subject
        $subject = Str::headline($template->name);

        return str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ slug }}', '{{ subject }}'],
            [$namespace, $className, $template->slug, $subject],
            $stub
        );
    }

    /**
     * Convert a slug to a class name.
     */
    protected function slugToClassName(string $slug): string
    {
        return Str::studly($slug);
    }

    /**
     * Ensure a directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    /**
     * Output the summary of downloaded templates and generated mailables.
     */
    protected function outputSummary(bool $dryRun, bool $withMailables): void
    {
        $this->newLine();

        $prefix = $dryRun ? 'Would download' : 'Downloaded';
        $this->components->twoColumnDetail("<fg=gray>{$prefix}:</>");

        foreach ($this->downloadedTemplates as $template) {
            $this->components->twoColumnDetail(
                "  <fg=green>✓</> {$template['slug']}",
                $template['blade_path']
            );
        }

        if ($withMailables && ! empty($this->generatedMailables)) {
            $this->newLine();
            $mailablePrefix = $dryRun ? 'Would generate Mailables' : 'Generated Mailables';
            $this->components->twoColumnDetail("<fg=gray>{$mailablePrefix}:</>");

            foreach ($this->generatedMailables as $mailable) {
                $this->components->twoColumnDetail(
                    "  <fg=green>✓</> {$mailable['class']}",
                    ''
                );
            }
        }

        if (! empty($this->skippedTemplates)) {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=gray>Skipped (no HTML):</>');

            foreach ($this->skippedTemplates as $slug) {
                $this->components->twoColumnDetail(
                    "  <fg=yellow>⊘</> {$slug}",
                    ''
                );
            }
        }

        $this->newLine();

        $count = count($this->downloadedTemplates);
        $action = $dryRun ? 'Would download' : 'Downloaded';
        $this->components->info("Done! {$action} {$count} template(s).");
    }
}
