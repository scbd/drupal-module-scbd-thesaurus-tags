<?php

namespace Drupal\scbd_field\Utility;

/**
 * Helper for determining when biosafety-specific behaviour should apply.
 */
class BiosafetyContext
{
    /**
     * Determine if the current widget context should be treated as biosafety.
     *
     * This checks both the global Bioland biosafety flag and whether the
     * widget's configured domains include biosafety-specific domains used on
     * BCH sites.
     *
     * @param bool $is_biosafety_site
     *   Global biosafety flag from bioland.settings (is_biosafety_land).
     * @param array $domain_order
     *   Ordered list of domain identifiers configured for the widget.
     *
     * @return bool
     *   TRUE if biosafety behaviour (e.g. auto-adding GBF Target 17 and
     *   country codes) should be applied for this widget.
     */
    public static function isBiosafetyContext($is_biosafety_site, array $domain_order)
    {
        $biosafety_domains = ['bchSubjects', 'bchSubjectGroups'];

        return (bool) ($is_biosafety_site || array_intersect($domain_order, $biosafety_domains));
    }
}
