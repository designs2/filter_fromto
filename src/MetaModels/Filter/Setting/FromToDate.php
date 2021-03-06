<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package    MetaModels
 * @subpackage FilterFromTo
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     David Molineus <mail@netzmacht.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Attribute\IAttribute;
use MetaModels\Filter\Rules\FromToDate as FromToRule;

/**
 * Filter "value from x to y" for FE-filtering, based on filters by the meta models team.
 */
class FromToDate extends AbstractFromTo
{
    /**
     * {@inheritDoc}
     */
    protected function getFilterWidgetParameters(IAttribute $attribute, $currentValue, $ids)
    {
        $parameters               = parent::getFilterWidgetParameters($attribute, $currentValue, $ids);
        $parameters['timetype']   = $this->get('timetype');
        $parameters['dateformat'] = $this->get('dateformat');

        return $parameters;
    }

    /**
     * {@inheritDoc}
     */
    protected function formatValue($value)
    {
        // Try to make a date from a string.
        $date = \DateTime::createFromFormat($this->get('dateformat'), $value);

        // Check if we have a date, if not return a empty string.
        if ($date === false) {
            return '';
        }

        // Make a unix timestamp from the string.
        return $date->getTimestamp();
    }

    /**
     * {@inheritDoc}
     */
    protected function buildFromToRule($attribute)
    {
        $rule = new FromToRule($attribute);
        $rule->setDateType($this->get('timetype'));

        return $rule;
    }
}
