<?php

declare(strict_types=1);

namespace phpClub\Service;

class Breadcrumbs
{
    private array $breadcrumbs = [];

    public function addCrumb(string $title, string $url): self
    {
        if (!$this->isHas($title)) {
            $this->breadcrumbs[$title] = $url;

            return $this;
        }

        throw new \InvalidArgumentException('Crumb already added');
    }

    public function getAllBreadCrumbs(): array
    {
        return $this->breadcrumbs;
    }

    private function isHas(string $title): bool
    {
        return array_key_exists($title, $this->breadcrumbs);
    }
}
