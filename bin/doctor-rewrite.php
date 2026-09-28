#!/usr/bin/env php
<?php

/**
 * The content-model doctor: the rewrite slug collision check.
 *
 * This file is a dev-time boundary, not analysed source. It composes
 * mahout-devtools' Doctor with this package's rewrite-collision check -- the
 * seam Contracts\Check exists for: a consumer implements Check and passes the
 * instance to the doctor; the doctor itself never grows a branch.
 *
 * An optional argument names a PHP file returning the declared content model
 * (a list of PostType and Taxonomy definitions). Without one, the package
 * declares no content model and the check passes on that statement, so
 * `composer doctor` reports both the installation and the content model.
 */

declare(strict_types=1);

// Installed as a dependency this file sits at vendor/iniznet/mahout-content/bin, so the
// consuming project's autoloader is three levels up, not beside the source: a fixed
// dirname(__DIR__) lookup only ever finds this package's own development vendor.
$candidates = [
    dirname(__DIR__, 3) . '/autoload.php',
    getcwd() . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

$loaded = null;

foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $loaded = $candidate;

        break;
    }
}

if (null === $loaded) {
    fwrite(STDERR, 'FAIL: vendor/autoload.php is missing; run composer install'.PHP_EOL);
    exit(1);
}

require_once $loaded;

$definitions = [];
$model = $argv[1] ?? null;

if (is_string($model) && '' !== $model) {
    if (!is_file($model)) {
        fwrite(STDERR, sprintf('FAIL: the content model file does not exist: %s%s', $model, PHP_EOL));
        exit(1);
    }

    $loaded = require $model;

    if (!is_array($loaded)) {
        fwrite(STDERR, 'FAIL: the content model file must return a list of definitions'.PHP_EOL);
        exit(1);
    }

    $definitions = $loaded;
}

$check = new class($definitions) implements Check
{
    /**
     * @param list<PostType|Taxonomy> $definitions
     */
    public function __construct(private readonly array $definitions)
    {
    }

    public function name(): string
    {
        return 'Rewrite slug collision';
    }

    public function examine(): CheckResult
    {
        if ([] === $this->definitions) {
            return CheckResult::pass($this->name(), 'no content model declared');
        }

        $collisions = (new RewriteSlugs($this->definitions))->collisions();

        if ([] === $collisions) {
            return CheckResult::pass($this->name(), sprintf('%d definitions, every rewrite slug unique', count($this->definitions)));
        }

        $details = [];

        foreach ($collisions as $collision) {
            $details[] = sprintf('"%s" claimed by %s', $collision->slug, implode(', ', $collision->declaredBy));
        }

        return CheckResult::fail($this->name(), implode('; ', $details));
    }
};

$report = (new Doctor([$check]))->examine();

echo $report->toTable();

exit($report->exitCode());
