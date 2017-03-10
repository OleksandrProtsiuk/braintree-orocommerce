<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

class UnitValueFormatter extends AbstractUnitFormatter implements UnitValueFormatterInterface
{
    /**
     * @param null|float|integer $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function format($value, MeasureUnitInterface $unit = null)
    {
        return $this->formatCode($value, $unit ? $unit->getCode() : null);
    }

    /**
     * @param null|float|integer $value
     * @param MeasureUnitInterface $unit
     *
     * @return string
     */
    public function formatShort($value, MeasureUnitInterface $unit = null)
    {
        return $this->formatCode($value, $unit ? $unit->getCode() : null, true);
    }

    /**
     * @param float|integer $value
     * @param string $unitCode
     * @param boolean $isShort
     *
     * @return string
     */
    public function formatCode($value, $unitCode, $isShort = false)
    {
        if (!is_numeric($value) || !$unitCode) {
            return $this->translator->trans('N/A');
        }

        return $this->translator->transChoice(
            sprintf('%s.%s.value.%s', $this->getTranslationPrefix(), $unitCode, $isShort ? 'short' : 'full'),
            $value,
            [
                '%count%' => $value
            ]
        );
    }
}
