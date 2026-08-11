<?php

declare(strict_types=1);

use Deptrac\Deptrac\Contract\Config\Collector\DirectoryConfig;
use Deptrac\Deptrac\Contract\Config\DeptracConfig;
use Deptrac\Deptrac\Contract\Config\Layer;
use Deptrac\Deptrac\Contract\Config\Ruleset;

return static function (DeptracConfig $config): void {
    $config
        ->paths('./src')
        ->layers(
            $ui = Layer::withName('UI')->collectors(
                DirectoryConfig::create('src/Module/.*/UI/.*'),
            ),
            $application = Layer::withName('Application')->collectors(
                DirectoryConfig::create('src/Module/.*/Application/.*'),
            ),
            $domain = Layer::withName('Domain')->collectors(
                DirectoryConfig::create('src/Module/.*/Domain/.*'),
                DirectoryConfig::create('src/Domain/.*'),
            ),
            $infrastructure = Layer::withName('Infrastructure')->collectors(
                DirectoryConfig::create('src/Module/.*/Infrastructure/.*'),
                DirectoryConfig::create('src/Infrastructure/.*'),
            ),
        )
        ->rulesets(
            Ruleset::forLayer($ui)->accesses($application, $domain, $infrastructure),
            Ruleset::forLayer($application)->accesses($domain),
            Ruleset::forLayer($domain)->accesses(),
            Ruleset::forLayer($infrastructure)->accesses($application, $domain),
        )
    ;
};
