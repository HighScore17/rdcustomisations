<?php

namespace AcVendor\DI\Definition\Helper;

/**
 * Helps defining container entries.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface DefinitionHelper
{
    /**
     * @param string $entryName Container entry name
     * @return \AcVendor\DI\Definition\Definition
     */
    public function getDefinition($entryName);
}
