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
            $controller = Layer::withName('Controller')->collectors(
                DirectoryConfig::create('src/Module/.*/Controller/.*'),
            ),
            $application = Layer::withName('Application')->collectors(
                DirectoryConfig::create('src/Module/.*/(Service|DTO|Dto|Message|Exception)/.*'),
            ),
            $persistence = Layer::withName('Persistence')->collectors(
                DirectoryConfig::create('src/Module/.*/Repository/.*'),
            ),
            $domain = Layer::withName('Domain')->collectors(
                DirectoryConfig::create('src/Module/.*/Entity/.*'),
            ),
            $infrastructure = Layer::withName('Infrastructure')->collectors(
                DirectoryConfig::create('src/Module/.*/(Command|EventSubscriber|Http|MessageHandler|Security)/.*'),
            ),
            $shared = Layer::withName('Shared')->collectors(
                DirectoryConfig::create('src/Shared/.*'),
            ),
        )
        ->rulesets(
            Ruleset::forLayer($controller)->accesses($application, $persistence, $domain, $infrastructure, $shared),
            Ruleset::forLayer($application)->accesses($persistence, $domain, $infrastructure, $shared),
            Ruleset::forLayer($persistence)->accesses($domain, $shared),
            // Doctrine's repositoryClass metadata makes entities reference their repository.
            Ruleset::forLayer($domain)->accesses($persistence, $shared),
            Ruleset::forLayer($infrastructure)->accesses($application, $persistence, $domain, $shared),
            Ruleset::forLayer($shared),
        )
    ;
};
