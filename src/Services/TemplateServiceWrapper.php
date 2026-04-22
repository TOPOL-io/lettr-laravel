<?php

declare(strict_types=1);

namespace Lettr\Laravel\Services;

use Lettr\Dto\Template\CreatedTemplate;
use Lettr\Dto\Template\CreateTemplateData;
use Lettr\Dto\Template\ListTemplatesFilter;
use Lettr\Dto\Template\TemplateDetail;
use Lettr\Dto\Template\UpdatedTemplate;
use Lettr\Dto\Template\UpdateTemplateData;
use Lettr\Responses\GetMergeTagsResponse;
use Lettr\Responses\GetTemplateHtmlResponse;
use Lettr\Responses\ListTemplatesResponse;
use Lettr\Services\TemplateService;

/**
 * Wrapper for the SDK's TemplateService.
 */
class TemplateServiceWrapper
{
    public function __construct(
        private readonly TemplateService $templateService,
    ) {}

    /**
     * List templates with optional filtering.
     */
    public function list(?ListTemplatesFilter $filter = null): ListTemplatesResponse
    {
        return $this->templateService->list($filter);
    }

    /**
     * Get template details by slug.
     */
    public function get(string $slug, ?int $projectId = null): TemplateDetail
    {
        return $this->templateService->get($slug, $projectId);
    }

    /**
     * Get merge tags for a template.
     */
    public function getMergeTags(string $slug, ?int $projectId = null, ?int $version = null): GetMergeTagsResponse
    {
        return $this->templateService->getMergeTags($slug, $projectId, $version);
    }

    /**
     * Create a new template.
     */
    public function create(CreateTemplateData $data): CreatedTemplate
    {
        return $this->templateService->create($data);
    }

    /**
     * Update an existing template by slug.
     */
    public function update(string $slug, UpdateTemplateData $data): UpdatedTemplate
    {
        return $this->templateService->update($slug, $data);
    }

    /**
     * Fetch the rendered HTML of a template.
     */
    public function getHtml(int $projectId, string $slug): GetTemplateHtmlResponse
    {
        return $this->templateService->getHtml($projectId, $slug);
    }
}
