<?php

declare(strict_types=1);

namespace Lettr\Laravel\Services;

use Lettr\Dto\Template\ListTemplatesFilter;
use Lettr\Dto\Template\TemplateDetail;
use Lettr\Responses\ListTemplatesResponse;
use Lettr\Services\TemplateService;

/**
 * Wrapper for the SDK's TemplateService that auto-injects the default project ID.
 */
class TemplateServiceWrapper
{
    public function __construct(
        private readonly TemplateService $templateService,
        private readonly ?int $defaultProjectId = null,
    ) {}

    /**
     * List templates with optional filtering.
     *
     * If no project ID is provided and a default is configured, uses the default.
     */
    public function list(?ListTemplatesFilter $filter = null): ListTemplatesResponse
    {
        $filter = $this->applyDefaultProjectId($filter);

        return $this->templateService->list($filter);
    }

    /**
     * Get template details by slug.
     *
     * If no project ID is provided and a default is configured, uses the default.
     */
    public function get(string $slug, ?int $projectId = null): TemplateDetail
    {
        $projectId = $projectId ?? $this->defaultProjectId;

        return $this->templateService->get($slug, $projectId);
    }

    /**
     * Apply the default project ID to the filter if not already set.
     */
    private function applyDefaultProjectId(?ListTemplatesFilter $filter): ?ListTemplatesFilter
    {
        if ($this->defaultProjectId === null) {
            return $filter;
        }

        if ($filter === null) {
            return new ListTemplatesFilter(projectId: $this->defaultProjectId);
        }

        if ($filter->projectId !== null) {
            return $filter;
        }

        return new ListTemplatesFilter(
            projectId: $this->defaultProjectId,
            perPage: $filter->perPage,
            page: $filter->page,
        );
    }
}
