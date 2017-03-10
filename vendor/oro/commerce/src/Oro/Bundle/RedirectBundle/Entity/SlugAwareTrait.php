<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait SlugAwareTrait
{
    /**
     * @var Collection|Slug[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\RedirectBundle\Entity\Slug",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     */
    protected $slugs;

    /**
     * @return Collection|Slug[]
     */
    public function getSlugs()
    {
        return $this->slugs;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function addSlug(Slug $slug)
    {
        if (!$this->hasSlug($slug)) {
            $this->slugs->add($slug);
        }
        return $this;
    }

    /**
     * @param Slug $slug
     * @return $this
     */
    public function removeSlug(Slug $slug)
    {
        if ($this->hasSlug($slug)) {
            $this->slugs->removeElement($slug);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function resetSlugs()
    {
        $this->slugs->clear();

        return $this;
    }

    /**
     * @param Slug $slug
     * @return bool
     */
    public function hasSlug(Slug $slug)
    {
        return $this->slugs->contains($slug);
    }
}
