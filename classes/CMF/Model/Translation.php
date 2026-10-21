<?php

namespace CMF\Model;

use Doctrine\ORM\Mapping as ORM,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * Gedmo\Translatable\Entity\Translation
 */
#[ORM\Entity(repositoryClass: \Gedmo\Translatable\Entity\Repository\TranslationRepository::class)]
#[ORM\Table(name: 'ext_translations')]
#[ORM\Index(name: 'translations_lookup_idx', columns: ['locale', 'object_class', 'foreign_key'])]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['locale', 'object_class', 'field', 'foreign_key'])]
#[ORM\AttributeOverrides([new ORM\AttributeOverride(name: 'objectClass', column: new ORM\Column(name: 'object_class', type: 'string', length: 255))])]
class Translation extends \Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}