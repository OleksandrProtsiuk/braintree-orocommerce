<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

class UnitLabelFormatter extends AbstractUnitFormatter implements UnitLabelFormatterInterface
{
    /**
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return string
     */
    public function format($code, $isShort = false, $isPlural = false)
    {
        if (!$code) {
            return $this->translator->trans('N/A');
        }

        return $this->translator->trans(
            sprintf(
                '%s.%s.label.%s%s',
                $this->getTranslationPrefix(),
                $code,
                $isShort ? 'short' : 'full',
                $isPlural ? '_plural' : ''
            )
        );
    }

    /**
     * @param array|MeasureUnitInterface[] $units
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return array
     */
    public function formatChoices(array $units, $isShort = false, $isPlural = false)
    {
        $result = [];
        foreach ($units as $unit) {
            $result[$unit->getCode()] = $this->format($unit->getCode(), $isShort, $isPlural);
        }

        return $result;
    }
}
